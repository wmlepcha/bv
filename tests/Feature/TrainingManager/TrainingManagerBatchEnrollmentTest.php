<?php

namespace Tests\Feature\TrainingManager;

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class TrainingManagerBatchEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_can_enroll_and_remove_students(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $student = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'student_number' => 'SK-TM-0001',
        ]);

        $this->actingAs($manager)
            ->post(route('training-manager.batches.enrollments.store', $batch), [
                'student_ids' => [$student->id],
            ])
            ->assertRedirect(route('training-manager.batches.students', $batch));

        $enrollment = BatchEnrollment::query()
            ->where('batch_id', $batch->id)
            ->where('student_id', $student->id)
            ->first();

        $this->assertNotNull($enrollment);

        $this->actingAs($manager)
            ->get(route('training-manager.batches.students', $batch))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('app/training-manager/batches/students')
                ->has('enrollments', 1)
                ->where('enrollments.0.student_id', $student->id)
            );

        $this->actingAs($manager)
            ->delete(route('training-manager.batches.enrollments.destroy', [
                'batch' => $batch,
                'enrollment' => $enrollment,
            ]))
            ->assertRedirect(route('training-manager.batches.students', $batch));

        $this->assertDatabaseMissing('batch_enrollments', [
            'id' => $enrollment->id,
        ]);
    }

    public function test_enrolling_student_already_in_another_batch_reports_failure(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $otherBatch = Batch::factory()->create();
        $targetBatch = Batch::factory()->create();
        $student = User::factory()->paid()->create([
            'role' => UserRole::Student,
        ]);

        BatchEnrollment::factory()->create([
            'batch_id' => $otherBatch->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($manager)
            ->post(route('training-manager.batches.enrollments.store', $targetBatch), [
                'student_ids' => [$student->id],
            ])
            ->assertRedirect(route('training-manager.batches.students', $targetBatch))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('batch_enrollments', [
            'batch_id' => $targetBatch->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_training_manager_can_import_enrollments(): void
    {
        $manager = User::factory()->trainingManager()->create();
        $batch = Batch::factory()->create();
        $student = User::factory()->paid()->create([
            'role' => UserRole::Student,
            'student_number' => 'SK-TM-IMPORT-01',
            'name' => 'Import Candidate',
        ]);

        $path = $this->writeSpreadsheet([
            ['SK-TM-IMPORT-01'],
        ]);

        $upload = new UploadedFile(
            $path,
            'enrollments.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $this->actingAs($manager)
            ->post(route('training-manager.batches.enrollments.import', $batch), [
                'spreadsheet' => $upload,
            ])
            ->assertRedirect(route('training-manager.batches.students', $batch))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('batch_enrollments', [
            'batch_id' => $batch->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_non_training_manager_cannot_manage_enrollments(): void
    {
        $teacher = User::factory()->teacher()->create();
        $batch = Batch::factory()->create();

        $this->actingAs($teacher)
            ->get(route('training-manager.batches.students', $batch))
            ->assertForbidden();
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function writeSpreadsheet(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue([1, 1], 'candidate_id');

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValue([$columnIndex + 1, $rowIndex + 2], $value);
            }
        }

        $path = storage_path('framework/testing/tm-batch-enrollment-'.uniqid('', true).'.xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
