<?php

namespace App\Services;

use App\Enums\CivilStatus;
use App\Enums\Gender;
use App\Enums\OfflinePaymentMethod;
use App\Enums\SpecialId;
use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\WelcomeWithCredentials;
use App\Support\IndianStates;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class CandidateImportService
{
    /**
     * Columns required to create the account and candidate ID.
     *
     * @var list<string>
     */
    public const REQUIRED_HEADERS = [
        'last_name',
        'first_name',
        'email',
        'state',
        'special_id',
        'registration_year',
    ];

    /**
     * Optional columns (students can complete these in their CV).
     * Accepted if present in the sheet; not required in the template.
     *
     * @var list<string>
     */
    public const OPTIONAL_HEADERS = [
        'mobile_whatsapp',
        'date_of_birth',
        'place_of_birth',
        'civil_status',
        'gender',
    ];

    /**
     * Optional columns for older students who already paid: filling paid_on, amount and payment_method
     * records the payment and unlocks them from today (no registration code needed).
     *
     * @var list<string>
     */
    public const PAYMENT_HEADERS = [
        'paid_on',
        'amount',
        'payment_method',
        'duration_months',
        'access_until',
        'reference',
    ];

    public function __construct(
        private readonly StudentNumberGenerator $studentNumberGenerator,
        private readonly PaidStudentMappingService $payments,
    ) {}

    /**
     * Creates new candidates; rows with payment columns also record the earlier payment and unlock the student.
     * An email that already exists is skipped, unless the row has payment columns: then that student is mapped as paid.
     *
     * @return array{
     *     created: list<array{email: string, candidate_id: string, temp_password: string, paid_on: string|null, expires_at: string|null}>,
     *     mapped: list<array{row: int, email: string, candidate_id: string|null, paid_on: string, amount: int, expires_at: string|null}>,
     *     skipped: list<array{row: int, email: string, reason: string}>,
     *     failed: list<array{row: int, email: string|null, errors: string}>
     * }
     */
    public function import(string $absolutePath, ?User $by = null): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            throw new \InvalidArgumentException('The spreadsheet is empty.');
        }

        $headerRow = array_shift($rows);
        $headers = $this->normalizeHeaders($headerRow);
        $this->assertHeaders($headers);

        $created = [];
        $mapped = [];
        $skipped = [];
        $failed = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $data = $this->mapRow($headers, $row);
            $email = isset($data['email']) ? trim((string) $data['email']) : null;

            if (array_key_exists('mobile_whatsapp', $data)) {
                $data['mobile_whatsapp'] = PhoneNumber::normalize(
                    is_string($data['mobile_whatsapp'] ?? null) ? $data['mobile_whatsapp'] : null,
                );
            }

            try {
                $payment = $this->paymentFromRow($data, $by);
                $existing = filled($email) ? User::query()->where('email', $email)->first() : null;

                if ($existing !== null) {
                    if ($payment === null) {
                        $skipped[] = ['row' => $rowNumber, 'email' => $email, 'reason' => 'Email already exists.'];

                        continue;
                    }

                    if (! $existing->isStudent()) {
                        throw new \InvalidArgumentException('This email belongs to a non-student account.');
                    }

                    if ($existing->hasActiveRegistration()) {
                        $skipped[] = ['row' => $rowNumber, 'email' => $email, 'reason' => 'Already has active access.'];

                        continue;
                    }

                    $record = $this->mapStudent($existing, $payment, $by);
                    $existing = $existing->fresh();

                    $mapped[] = [
                        'row' => $rowNumber,
                        'email' => $existing->email,
                        'candidate_id' => $existing->student_number,
                        'paid_on' => $record->paid_at->toDateString(),
                        'amount' => (int) ($record->amount / 100),
                        'expires_at' => $existing->registration_expires_at?->toDateString(),
                    ];

                    continue;
                }

                $validator = Validator::make($data, $this->rowRules());

                if ($validator->fails()) {
                    $failed[] = [
                        'row' => $rowNumber,
                        'email' => $email,
                        'errors' => $validator->errors()->all() !== []
                            ? implode('; ', $validator->errors()->all())
                            : 'Validation failed.',
                    ];

                    continue;
                }

                $created[] = DB::transaction(function () use ($validator, $payment, $by): array {
                    $result = $this->createCandidate($validator->validated());
                    $record = null;

                    if ($payment !== null) {
                        $student = User::query()->where('email', $result['email'])->firstOrFail();
                        $record = $this->mapStudent($student, $payment, $by);
                        $student->refresh();
                    }

                    return [
                        ...$result,
                        'paid_on' => $record?->paid_at->toDateString(),
                        'expires_at' => isset($student) ? $student->registration_expires_at?->toDateString() : null,
                    ];
                });
            } catch (Throwable $exception) {
                $failed[] = [
                    'row' => $rowNumber,
                    'email' => $email,
                    'errors' => $exception->getMessage(),
                ];
            }
        }

        return compact('created', 'mapped', 'skipped', 'failed');
    }

    /**
     * The row's payment details, or null when the row has none (an ordinary unpaid candidate).
     *
     * @param  array<string, mixed>  $data
     * @return array{paid_on: string, amount: int, payment_method: string, duration_months: int, access_until: string|null, reference: string|null}|null
     *
     * @throws \InvalidArgumentException
     */
    private function paymentFromRow(array $data, ?User $by): ?array
    {
        if (blank($data['paid_on'] ?? null) && blank($data['amount'] ?? null) && blank($data['payment_method'] ?? null)) {
            return null;
        }

        if ($by === null) {
            throw new \InvalidArgumentException('A signed-in staff member is needed to record payments.');
        }

        $data['paid_on'] = $this->parseDate($data['paid_on'] ?? null);
        $data['access_until'] = $this->parseDate($data['access_until'] ?? null);

        $validator = Validator::make($data, [
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in(array_column(OfflinePaymentMethod::cases(), 'value'))],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'access_until' => ['nullable', 'date', 'after:today'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Payment columns: '.implode('; ', $validator->errors()->all()));
        }

        $payment = $validator->validated();

        return [
            'paid_on' => $payment['paid_on'],
            'amount' => (int) round((float) $payment['amount']),
            'payment_method' => $payment['payment_method'],
            'duration_months' => (int) ($payment['duration_months'] ?? PaidStudentMappingService::DEFAULT_DURATION_MONTHS),
            'access_until' => $payment['access_until'] ?? null,
            'reference' => $payment['reference'] ?? null,
        ];
    }

    /**
     * @param  array{paid_on: string, amount: int, payment_method: string, duration_months: int, access_until: string|null, reference: string|null}  $payment
     */
    private function mapStudent(User $student, array $payment, User $by): Payment
    {
        return $this->payments->map(
            $student,
            Plan::query()->active()->where('duration_months', $payment['duration_months'])->ordered()->first(),
            $payment['duration_months'],
            $payment['amount'],
            CarbonImmutable::parse($payment['paid_on']),
            OfflinePaymentMethod::from($payment['payment_method']),
            $payment['reference'],
            $payment['access_until'] !== null ? CarbonImmutable::parse($payment['access_until'])->endOfDay() : null,
            $by,
        );
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        try {
            return CarbonImmutable::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return (string) $value;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{email: string, candidate_id: string, temp_password: string}
     */
    public function createCandidate(array $data): array
    {
        $stateCode = IndianStates::codeForName($data['state']);

        if ($stateCode === null) {
            throw new \InvalidArgumentException('Unknown state: '.$data['state']);
        }

        $specialId = SpecialId::from(strtoupper(trim($data['special_id'])));
        $registrationYear = (int) $data['registration_year'];
        $tempPassword = Str::password(12);
        $firstName = trim($data['first_name']);
        $lastName = trim($data['last_name']);

        return DB::transaction(function () use (
            $data,
            $stateCode,
            $specialId,
            $registrationYear,
            $tempPassword,
            $firstName,
            $lastName,
        ): array {
            $candidateId = $this->studentNumberGenerator->generate(
                $stateCode,
                $specialId,
                $registrationYear,
            );

            $user = User::query()->create([
                'name' => trim("{$firstName} {$lastName}"),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $data['email'],
                'password' => Hash::make($tempPassword),
            ]);

            $user->forceFill([
                'role' => UserRole::Student,
                'state_code' => $stateCode,
                'special_id' => $specialId,
                'registration_year' => $registrationYear,
                'student_number' => $candidateId,
                'registration_paid_at' => null,
                'email_verified_at' => now(),
            ])->save();

            StudentProfile::query()->create([
                'user_id' => $user->id,
                'mobile_whatsapp' => filled($data['mobile_whatsapp'] ?? null) ? $data['mobile_whatsapp'] : null,
                'date_of_birth' => filled($data['date_of_birth'] ?? null) ? $data['date_of_birth'] : null,
                'place_of_birth' => filled($data['place_of_birth'] ?? null) ? $data['place_of_birth'] : null,
                'civil_status' => filled($data['civil_status'] ?? null) ? $data['civil_status'] : null,
                'gender' => filled($data['gender'] ?? null) ? $data['gender'] : null,
            ]);

            $user->notify((new WelcomeWithCredentials(
                plainPassword: $tempPassword,
                role: UserRole::Student,
            ))->afterCommit());

            return [
                'email' => $user->email,
                'candidate_id' => $candidateId,
                'temp_password' => $tempPassword,
            ];
        });
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rowRules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mobile_whatsapp' => ['nullable', 'string', 'max:50', PhoneNumber::rule()],
            'state' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! IndianStates::isValidName(is_string($value) ? $value : null)) {
                    $fail('The state must be a valid Indian state or union territory name.');
                }
            }],
            'special_id' => ['required', 'string', Rule::in(SpecialId::values())],
            'registration_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'civil_status' => ['nullable', 'string', Rule::in(CivilStatus::values())],
            'gender' => ['nullable', 'string', Rule::in(Gender::values())],
        ];
    }

    /**
     * @param  list<mixed>|null  $headerRow
     * @return list<string>
     */
    public function normalizeHeaders(?array $headerRow): array
    {
        if ($headerRow === null) {
            return [];
        }

        return array_map(
            static fn (mixed $header): string => Str::of((string) $header)->trim()->lower()->toString(),
            $headerRow,
        );
    }

    /**
     * @param  list<string>  $headers
     */
    private function assertHeaders(array $headers): void
    {
        foreach (self::REQUIRED_HEADERS as $required) {
            if (! in_array($required, $headers, true)) {
                throw new \InvalidArgumentException(
                    "Missing required column: {$required}. Expected headers: ".implode(', ', self::REQUIRED_HEADERS)
                );
            }
        }
    }

    /**
     * @param  list<string>  $headers
     * @param  list<mixed>  $row
     * @return array<string, mixed>
     */
    public function mapRow(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $value = $row[$index] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($header === 'special_id' && is_string($value)) {
                $value = strtoupper($value);
            }

            if ($header === 'registration_year' && is_numeric($value)) {
                $value = (int) $value;
            }

            if ($header === 'date_of_birth' && is_numeric($value)) {
                // Excel serial date — PhpSpreadsheet with formatData may already convert;
                // leave as-is if string; if float, skip conversion here and fail validation.
                $value = null;
            }

            $data[$header] = $value === '' ? null : $value;
        }

        return $data;
    }

    /**
     * @param  list<mixed>  $row
     */
    public function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
