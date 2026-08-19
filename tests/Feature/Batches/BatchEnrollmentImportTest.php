<?php

namespace Tests\Feature\Batches;

use App\Enums\CourseLanguage;
use App\Enums\CourseLevel;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\BatchEnrollmentImportService;
use App\Services\BatchEnrollmentTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class BatchEnrollmentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_enrolls_matching_candidates(): void
    {
        $batch = Batch::factory()->create([
            'language' => CourseLanguage::German,
            'level' => CourseLevel::B1,
        ]);

        $student = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'student_number' => 'SK-COI-2026-01',
            'name' => 'Dominique Sangbo',
        ]);

        $path = $this->writeSpreadsheet([
            ['SK-COI-2026-01'],
        ]);

        $result = app(BatchEnrollmentImportService::class)->import($batch, $path);

        $this->assertCount(1, $result['enrolled']);
        $this->assertSame('SK-COI-2026-01', $result['enrolled'][0]['candidate_id']);
        $this->assertSame('Dominique Sangbo', $result['enrolled'][0]['name']);
        $this->assertCount(0, $result['skipped']);
        $this->assertCount(0, $result['failed']);

        $this->assertDatabaseHas('batch_enrollments', [
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_unknown_candidate_id_fails(): void
    {
        $batch = Batch::factory()->create();

        $path = $this->writeSpreadsheet([
            ['SK-COI-9999-99'],
        ]);

        $result = app(BatchEnrollmentImportService::class)->import($batch, $path);

        $this->assertCount(0, $result['enrolled']);
        $this->assertCount(1, $result['failed']);
        $this->assertSame('SK-COI-9999-99', $result['failed'][0]['candidate_id']);
        $this->assertStringContainsString('No candidate found', $result['failed'][0]['errors']);
    }

    public function test_already_in_another_batch_fails(): void
    {
        $student = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'student_number' => 'SK-COI-2026-01',
        ]);

        $otherBatch = Batch::factory()->create();
        $targetBatch = Batch::factory()->create();

        BatchEnrollment::factory()->create([
            'batch_id' => $otherBatch->id,
            'student_id' => $student->id,
        ]);

        $path = $this->writeSpreadsheet([
            ['SK-COI-2026-01'],
        ]);

        $result = app(BatchEnrollmentImportService::class)->import($targetBatch, $path);

        $this->assertCount(0, $result['enrolled']);
        $this->assertCount(1, $result['failed']);
        $this->assertStringContainsString('another batch', $result['failed'][0]['errors']);
    }

    public function test_already_in_same_batch_is_skipped(): void
    {
        $student = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'student_number' => 'SK-COI-2026-01',
        ]);

        $batch = Batch::factory()->create();

        BatchEnrollment::factory()->create([
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);

        $path = $this->writeSpreadsheet([
            ['SK-COI-2026-01'],
        ]);

        $result = app(BatchEnrollmentImportService::class)->import($batch, $path);

        $this->assertCount(0, $result['enrolled']);
        $this->assertCount(1, $result['skipped']);
        $this->assertSame('Already enrolled in this batch.', $result['skipped'][0]['reason']);
        $this->assertSame(1, BatchEnrollment::query()->where('student_id', $student->id)->count());
    }

    public function test_blank_rows_are_ignored(): void
    {
        $batch = Batch::factory()->create();

        $student = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'student_number' => 'SK-COI-2026-01',
        ]);

        $path = $this->writeSpreadsheet([
            [''],
            ['SK-COI-2026-01'],
            [null],
        ]);

        $result = app(BatchEnrollmentImportService::class)->import($batch, $path);

        $this->assertCount(1, $result['enrolled']);
        $this->assertSame($student->id, BatchEnrollment::query()->where('batch_id', $batch->id)->value('student_id'));
    }

    public function test_template_download_lists_unenrolled_candidates_with_names(): void
    {
        $available = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'name' => 'Dominique Sangbo',
            'email' => 'dominique@example.com',
            'student_number' => 'SK-COI-2026-01',
        ]);

        StudentProfile::query()->create([
            'user_id' => $available->id,
            'mobile_whatsapp' => '+919832581198',
        ]);

        $enrolledElsewhere = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'name' => 'Already Enrolled',
            'student_number' => 'SK-COI-2026-99',
        ]);

        BatchEnrollment::factory()->create([
            'student_id' => $enrolledElsewhere->id,
        ]);

        $response = app(BatchEnrollmentTemplateService::class)->download();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'batch-enrollment-template.xlsx',
            $response->headers->get('Content-Disposition') ?? '',
        );

        ob_start();
        $response->sendContent();
        $binary = ob_get_clean();

        $this->assertNotFalse($binary);
        $this->assertNotSame('', $binary);

        $tmp = storage_path('framework/testing/batch-enrollment-template-'.uniqid('', true).'.xlsx');
        file_put_contents($tmp, $binary);

        try {
            $sheet = IOFactory::load($tmp)->getActiveSheet()->toArray(null, true, true, false);
            $this->assertSame('candidate_id', $sheet[0][0] ?? null);
            $this->assertSame('name', $sheet[0][1] ?? null);
            $this->assertSame('email', $sheet[0][2] ?? null);
            $this->assertSame('mobile_whatsapp', $sheet[0][3] ?? null);

            $ids = collect($sheet)->skip(1)->pluck(0)->filter()->values();
            $this->assertTrue($ids->contains('SK-COI-2026-01'));
            $this->assertFalse($ids->contains('SK-COI-2026-99'));

            $dominiqueRow = collect($sheet)->first(
                fn (array $row): bool => ($row[0] ?? null) === 'SK-COI-2026-01',
            );

            $this->assertNotNull($dominiqueRow);
            $this->assertSame('Dominique Sangbo', $dominiqueRow[1] ?? null);
            $this->assertSame('dominique@example.com', $dominiqueRow[2] ?? null);
            $this->assertSame('+919832581198', $dominiqueRow[3] ?? null);
            $this->assertSame($available->student_number, $dominiqueRow[0]);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_import_ignores_name_and_email_helper_columns(): void
    {
        $batch = Batch::factory()->create();

        $student = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'student_number' => 'SK-COI-2026-01',
            'name' => 'Real Name',
        ]);

        $path = $this->writeSpreadsheetWithHeaders(
            ['candidate_id', 'name', 'email', 'mobile_whatsapp'],
            [
                ['SK-COI-2026-01', 'Wrong Name', 'wrong@example.com', '+910000000000'],
            ],
        );

        $result = app(BatchEnrollmentImportService::class)->import($batch, $path);

        $this->assertCount(1, $result['enrolled']);
        $this->assertSame('Real Name', $result['enrolled'][0]['name']);
        $this->assertDatabaseHas('batch_enrollments', [
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_missing_header_throws(): void
    {
        $batch = Batch::factory()->create();

        $path = $this->writeSpreadsheetWithHeaders(['email'], [
            ['someone@example.com'],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required column: candidate_id');

        app(BatchEnrollmentImportService::class)->import($batch, $path);
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function writeSpreadsheet(array $rows): string
    {
        return $this->writeSpreadsheetWithHeaders(['candidate_id'], $rows);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<mixed>>  $rows
     */
    private function writeSpreadsheetWithHeaders(array $headers, array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $columnIndex => $header) {
            $sheet->setCellValue([$columnIndex + 1, 1], $header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValue([$columnIndex + 1, $rowIndex + 2], $value);
            }
        }

        $path = storage_path('framework/testing/batch-enrollment-import-'.uniqid('', true).'.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
