<?php

namespace Tests\Feature\Batches;

use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\User;
use App\Services\BatchEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchEnrollmentManyTest extends TestCase
{
    use RefreshDatabase;

    public function test_enroll_many_enrolls_multiple_unenrolled_candidates(): void
    {
        $batch = Batch::factory()->create();
        $first = User::factory()->paid()->create(['role' => UserRole::Student]);
        $second = User::factory()->paid()->create(['role' => UserRole::Student]);

        $result = app(BatchEnrollmentService::class)->enrollMany($batch, [
            $first->id,
            $second->id,
        ]);

        $this->assertSame(2, $result['enrolled']);
        $this->assertSame([], $result['failed']);
        $this->assertDatabaseHas('batch_enrollments', [
            'batch_id' => $batch->id,
            'student_id' => $first->id,
        ]);
        $this->assertDatabaseHas('batch_enrollments', [
            'batch_id' => $batch->id,
            'student_id' => $second->id,
        ]);
    }

    public function test_enroll_many_continues_when_one_candidate_is_already_in_another_batch(): void
    {
        $batch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $available = User::factory()->paid()->create(['role' => UserRole::Student]);
        $blocked = User::factory()->paid()->create(['role' => UserRole::Student]);

        BatchEnrollment::factory()->create([
            'batch_id' => $otherBatch->id,
            'student_id' => $blocked->id,
        ]);

        $result = app(BatchEnrollmentService::class)->enrollMany($batch, [
            $available->id,
            $blocked->id,
        ]);

        $this->assertSame(1, $result['enrolled']);
        $this->assertCount(1, $result['failed']);
        $this->assertStringContainsString($blocked->email, $result['failed'][0]);
        $this->assertDatabaseHas('batch_enrollments', [
            'batch_id' => $batch->id,
            'student_id' => $available->id,
        ]);
        $this->assertDatabaseMissing('batch_enrollments', [
            'batch_id' => $batch->id,
            'student_id' => $blocked->id,
        ]);
    }
}
