<?php

namespace Tests\Feature\Candidates;

use App\Enums\RegistrationFeePaidVia;
use App\Enums\SpecialId;
use App\Enums\UserRole;
use App\Models\ActivationCode;
use App\Models\User;
use App\Notifications\RegistrationActivated;
use App\Notifications\WelcomeWithCredentials;
use App\Services\BulkActivationCodeService;
use App\Services\CandidateImportService;
use App\Services\CandidateImportTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CandidateImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_unpaid_candidates_with_sheet_year(): void
    {
        $path = $this->writeSpreadsheet([
            [
                'Thapa',
                'Nanuka',
                'nanuka@import.test',
                'Sikkim',
                'COI',
                '2024',
            ],
        ]);

        $result = app(CandidateImportService::class)->import($path);

        $this->assertCount(1, $result['created']);
        $this->assertSame('SK-COI-2024-01', $result['created'][0]['candidate_id']);
        $this->assertNotEmpty($result['created'][0]['temp_password']);

        $user = User::query()->where('email', 'nanuka@import.test')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Student, $user->role);
        $this->assertNull($user->registration_paid_at);
        $this->assertSame('SK', $user->state_code);
        $this->assertSame(SpecialId::Coi, $user->special_id);
        $this->assertSame(2024, $user->registration_year);
        $this->assertSame('SK-COI-2024-01', $user->student_number);
        $this->assertNotNull($user->studentProfile);
        $this->assertNull($user->studentProfile->mobile_whatsapp);
    }

    public function test_import_accepts_optional_profile_columns_when_present(): void
    {
        $path = $this->writeSpreadsheetWithHeaders(
            [
                ...CandidateImportService::REQUIRED_HEADERS,
                ...CandidateImportService::OPTIONAL_HEADERS,
            ],
            [
                [
                    'Thapa',
                    'Nanuka',
                    'optional@import.test',
                    'Sikkim',
                    'COI',
                    '2024',
                    '+919832581198',
                    '1988-06-02',
                    'Gangtok, Sikkim',
                    'Unmarried',
                    'Female',
                ],
            ],
        );

        $result = app(CandidateImportService::class)->import($path);

        $this->assertCount(1, $result['created']);
        $user = User::query()->where('email', 'optional@import.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('+919832581198', $user->studentProfile?->mobile_whatsapp);
        $this->assertSame('1988-06-02', $user->studentProfile?->date_of_birth?->format('Y-m-d'));
        $this->assertSame('Gangtok, Sikkim', $user->studentProfile?->place_of_birth);
    }

    public function test_import_skips_duplicate_email(): void
    {
        User::factory()->unpaid()->create(['email' => 'exists@import.test']);

        $path = $this->writeSpreadsheet([
            [
                'Doe',
                'Jane',
                'exists@import.test',
                'Sikkim',
                'COI',
                '2024',
            ],
        ]);

        $result = app(CandidateImportService::class)->import($path);

        $this->assertCount(0, $result['created']);
        $this->assertCount(1, $result['skipped']);
    }

    public function test_import_rejects_invalid_state(): void
    {
        $path = $this->writeSpreadsheet([
            [
                'Doe',
                'Jane',
                'badstate@import.test',
                'Narnia',
                'COI',
                '2024',
            ],
        ]);

        $result = app(CandidateImportService::class)->import($path);

        $this->assertCount(0, $result['created']);
        $this->assertCount(1, $result['failed']);
    }

    public function test_bulk_activation_codes_and_redeem_unlock_dashboard(): void
    {
        $path = $this->writeSpreadsheet([
            [
                'Lepcha',
                'Wilbur',
                'wilbur@import.test',
                'Sikkim',
                'RC',
                '2023',
            ],
        ]);

        app(CandidateImportService::class)->import($path);

        $user = User::query()->where('email', 'wilbur@import.test')->firstOrFail();

        $codes = app(BulkActivationCodeService::class)->generateForUnpaidStudents([$user->id]);

        $this->assertCount(1, $codes);
        $this->assertSame('SK-RC-2023-01', $codes[0]['candidate_id']);
        $this->assertMatchesRegularExpression('/^BV-26-[A-Z2-9]{4}$/', $codes[0]['registration_code']);

        $this->actingAs($user)
            ->post(route('activation-codes.redeem'), [
                'code' => $codes[0]['registration_code'],
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertTrue($user->hasPaidRegistration());
        $this->assertTrue($user->canAccessDashboard());
        $this->assertNotNull(ActivationCode::query()->where('code', $codes[0]['registration_code'])->value('used_at'));
    }

    public function test_template_download_contains_required_headers_and_sample_row(): void
    {
        $response = app(CandidateImportTemplateService::class)->download();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'candidate-import-template.xlsx',
            $response->headers->get('Content-Disposition') ?? '',
        );

        ob_start();
        $response->sendContent();
        $binary = ob_get_clean();

        $this->assertNotFalse($binary);
        $this->assertNotSame('', $binary);

        $tmp = storage_path('framework/testing/candidate-import-template-'.uniqid('', true).'.xlsx');
        file_put_contents($tmp, $binary);

        try {
            $sheet = IOFactory::load($tmp)->getActiveSheet()->toArray(null, true, true, false);
            $this->assertSame([...CandidateImportService::REQUIRED_HEADERS, ...CandidateImportService::PAYMENT_HEADERS], $sheet[0] ?? null);
            $this->assertSame(CandidateImportTemplateService::SAMPLE_ROW, array_slice($sheet[1] ?? [], 0, count(CandidateImportService::REQUIRED_HEADERS)));
            $this->assertSame('dolma@example.com', $sheet[2][2] ?? null);
            $this->assertSame('cash', $sheet[2][8] ?? null);
            $this->assertNotContains('mobile_whatsapp', $sheet[0] ?? []);
            $this->assertNotContains('date_of_birth', $sheet[0] ?? []);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_import_rejects_phone_without_country_code_and_allows_blank(): void
    {
        $invalidPath = $this->writeSpreadsheetWithHeaders(
            [
                ...CandidateImportService::REQUIRED_HEADERS,
                'mobile_whatsapp',
            ],
            [
                [
                    'Thapa',
                    'Nanuka',
                    'invalid-phone@import.test',
                    'Sikkim',
                    'COI',
                    '2024',
                    '9832581198',
                ],
            ],
        );

        $invalidResult = app(CandidateImportService::class)->import($invalidPath);

        $this->assertCount(0, $invalidResult['created']);
        $this->assertCount(1, $invalidResult['failed']);
        $this->assertStringContainsString('country code', strtolower($invalidResult['failed'][0]['errors']));

        $blankPath = $this->writeSpreadsheet([
            [
                'Thapa',
                'Nanuka',
                'blank-phone@import.test',
                'Sikkim',
                'COI',
                '2024',
            ],
        ]);

        $blankResult = app(CandidateImportService::class)->import($blankPath);

        $this->assertCount(1, $blankResult['created']);
        $user = User::query()->where('email', 'blank-phone@import.test')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->studentProfile?->mobile_whatsapp);
    }

    /**
     * @param  list<list<string>>  $dataRows
     */
    private function writeSpreadsheet(array $dataRows): string
    {
        return $this->writeSpreadsheetWithHeaders(CandidateImportService::REQUIRED_HEADERS, $dataRows);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $dataRows
     */
    public function test_rows_with_payment_columns_create_a_paid_unlocked_candidate_and_leave_others_unpaid(): void
    {
        Notification::fake();
        $staff = User::factory()->salesTeam()->create();

        $path = $this->writeSpreadsheetWithHeaders(
            [...CandidateImportService::REQUIRED_HEADERS, ...CandidateImportService::PAYMENT_HEADERS],
            [
                ['Thapa', 'Nanuka', 'unpaid@import.test', 'Sikkim', 'COI', '2025', '', '', '', '', '', ''],
                ['Lepcha', 'Dolma', 'paid@import.test', 'Sikkim', 'COI', '2025', '2025-06-15', '25000', 'cash', '12', '', 'RCPT-1'],
            ],
        );

        $result = app(CandidateImportService::class)->import($path, $staff);

        $this->assertCount(2, $result['created']);
        $this->assertNull($result['created'][0]['paid_on']);
        $this->assertSame('2025-06-15', $result['created'][1]['paid_on']);
        $this->assertSame(now()->addMonths(12)->toDateString(), $result['created'][1]['expires_at']);

        $unpaid = User::query()->where('email', 'unpaid@import.test')->firstOrFail();
        $paid = User::query()->where('email', 'paid@import.test')->firstOrFail();
        $this->assertFalse($unpaid->hasActiveRegistration());
        $this->assertTrue($paid->hasActiveRegistration());
        $this->assertSame(RegistrationFeePaidVia::Mapped, $paid->registration_fee_paid_via);
        $this->assertDatabaseHas('payments', ['user_id' => $paid->id, 'channel' => 'legacy', 'amount' => 2500000, 'payment_mode' => 'cash', 'payment_mode_detail' => 'RCPT-1', 'collected_by_user_id' => $staff->id]);
        $this->assertDatabaseMissing('payments', ['user_id' => $unpaid->id]);
        Notification::assertSentTo($paid, WelcomeWithCredentials::class);
        Notification::assertNotSentTo($paid, RegistrationActivated::class);

        // Registration codes are only generated for the unpaid candidate.
        $codes = app(BulkActivationCodeService::class)->generateForUnpaidStudents(User::query()->whereIn('email', ['unpaid@import.test', 'paid@import.test'])->pluck('id')->all());
        $this->assertSame(['unpaid@import.test'], array_column($codes, 'email'));
    }

    public function test_existing_students_with_payment_columns_are_mapped_silently_and_others_skipped(): void
    {
        Notification::fake();
        $staff = User::factory()->salesTeam()->create();
        $existing = User::factory()->student()->unpaid()->create(['email' => 'old@import.test']);
        $plain = User::factory()->student()->unpaid()->create(['email' => 'plain@import.test']);
        $active = User::factory()->student()->paid()->create(['email' => 'active@import.test']);
        $teacher = User::factory()->teacher()->create(['email' => 'teacher@import.test']);

        $row = fn (string $email, array $payment): array => ['Doe', 'Jane', $email, 'Sikkim', 'COI', '2025', ...$payment];
        $path = $this->writeSpreadsheetWithHeaders(
            [...CandidateImportService::REQUIRED_HEADERS, ...CandidateImportService::PAYMENT_HEADERS],
            [
                $row('old@import.test', ['2025-06-15', '9000', 'upi', '', '', 'UTR1']),
                $row('plain@import.test', ['', '', '', '', '', '']),
                $row('active@import.test', ['2025-06-15', '9000', 'cash', '', '', '']),
                $row('teacher@import.test', ['2025-06-15', '9000', 'cash', '', '', '']),
            ],
        );

        $result = app(CandidateImportService::class)->import($path, $staff);

        $this->assertCount(1, $result['mapped']);
        $this->assertSame('old@import.test', $result['mapped'][0]['email']);
        $this->assertSame(9000, $result['mapped'][0]['amount']);
        $this->assertSame(now()->addMonths(24)->toDateString(), $result['mapped'][0]['expires_at']);
        $this->assertTrue($existing->fresh()->hasActiveRegistration());
        $this->assertFalse($plain->fresh()->hasActiveRegistration());
        $this->assertSame(
            [['row' => 3, 'email' => 'plain@import.test', 'reason' => 'Email already exists.'], ['row' => 4, 'email' => 'active@import.test', 'reason' => 'Already has active access.']],
            $result['skipped'],
        );
        $this->assertCount(1, $result['failed']);
        $this->assertStringContainsString('non-student', $result['failed'][0]['errors']);
        Notification::assertNothingSent();
    }

    public function test_bad_payment_columns_fail_the_row_without_creating_the_account(): void
    {
        $staff = User::factory()->salesTeam()->create();
        $headers = [...CandidateImportService::REQUIRED_HEADERS, ...CandidateImportService::PAYMENT_HEADERS];
        $rows = [
            ['A', 'One', 'no.amount@import.test', 'Sikkim', 'COI', '2025', '2025-06-15', '', 'cash', '', '', ''],
            ['A', 'Two', 'bad.method@import.test', 'Sikkim', 'COI', '2025', '2025-06-15', '100', 'barter', '', '', ''],
            ['A', 'Three', 'future@import.test', 'Sikkim', 'COI', '2025', now()->addDays(4)->toDateString(), '100', 'cash', '', '', ''],
            ['A', 'Four', 'zero@import.test', 'Sikkim', 'COI', '2025', '2025-06-15', '0', 'cash', '', '', ''],
        ];

        $result = app(CandidateImportService::class)->import($this->writeSpreadsheetWithHeaders($headers, $rows), $staff);

        $this->assertCount(0, $result['created']);
        $this->assertSame([2, 3, 4, 5], array_column($result['failed'], 'row'));
        $this->assertSame(0, User::query()->whereIn('email', array_column($rows, 2))->count());
    }

    public function test_payment_columns_need_a_signed_in_staff_member(): void
    {
        $path = $this->writeSpreadsheetWithHeaders(
            [...CandidateImportService::REQUIRED_HEADERS, ...CandidateImportService::PAYMENT_HEADERS],
            [['A', 'One', 'nostaff@import.test', 'Sikkim', 'COI', '2025', '2025-06-15', '100', 'cash', '', '', '']],
        );

        $result = app(CandidateImportService::class)->import($path);

        $this->assertCount(1, $result['failed']);
        $this->assertSame(0, User::query()->where('email', 'nostaff@import.test')->count());
    }

    public function test_excel_serial_dates_are_understood(): void
    {
        $staff = User::factory()->salesTeam()->create();
        $serial = (string) Date::PHPToExcel(now()->subMonths(2));
        $path = $this->writeSpreadsheetWithHeaders(
            [...CandidateImportService::REQUIRED_HEADERS, ...CandidateImportService::PAYMENT_HEADERS],
            [['A', 'Serial', 'serial@import.test', 'Sikkim', 'COI', '2025', $serial, '500', 'cash', '', '', '']],
        );

        $result = app(CandidateImportService::class)->import($path, $staff);

        $this->assertCount(1, $result['created']);
        $this->assertSame(now()->subMonths(2)->toDateString(), $result['created'][0]['paid_on']);
    }

    private function writeSpreadsheetWithHeaders(array $headers, array $dataRows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([$headers]);

        foreach ($dataRows as $rowIndex => $dataRow) {
            foreach ($dataRow as $columnIndex => $value) {
                $sheet->setCellValueExplicit(
                    [$columnIndex + 1, $rowIndex + 2],
                    (string) $value,
                    DataType::TYPE_STRING,
                );
            }
        }

        $path = storage_path('framework/testing/candidate-import-'.uniqid('', true).'.xlsx');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
