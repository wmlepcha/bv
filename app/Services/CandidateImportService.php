<?php

namespace App\Services;

use App\Enums\CivilStatus;
use App\Enums\Gender;
use App\Enums\SpecialId;
use App\Enums\UserRole;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\WelcomeWithCredentials;
use App\Support\IndianStates;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function __construct(
        private readonly StudentNumberGenerator $studentNumberGenerator,
    ) {}

    /**
     * @return array{
     *     created: list<array{email: string, candidate_id: string, temp_password: string}>,
     *     skipped: list<array{row: int, email: string, reason: string}>,
     *     failed: list<array{row: int, email: string|null, errors: string}>
     * }
     */
    public function import(string $absolutePath): array
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

            $validated = $validator->validated();

            if (User::query()->where('email', $validated['email'])->exists()) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'email' => $validated['email'],
                    'reason' => 'Email already exists.',
                ];

                continue;
            }

            try {
                $result = $this->createCandidate($validated);
                $created[] = $result;
            } catch (Throwable $exception) {
                $failed[] = [
                    'row' => $rowNumber,
                    'email' => $email,
                    'errors' => $exception->getMessage(),
                ];
            }
        }

        return compact('created', 'skipped', 'failed');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{email: string, candidate_id: string, temp_password: string}
     */
    private function createCandidate(array $data): array
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
    private function rowRules(): array
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
    private function normalizeHeaders(?array $headerRow): array
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
    private function mapRow(array $headers, array $row): array
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
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
