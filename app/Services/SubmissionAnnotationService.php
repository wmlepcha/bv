<?php

namespace App\Services;

use App\Enums\SubmissionAnnotationType;
use App\Models\AssignmentSubmission;
use App\Models\SubmissionAnnotation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmissionAnnotationService
{
    /**
     * @param  list<array{type: string, question_id?: int|null, payload: array<string, mixed>}>  $annotations
     */
    public function sync(User $actor, AssignmentSubmission $submission, array $annotations): void
    {
        DB::transaction(function () use ($actor, $submission, $annotations): void {
            $submission->annotations()->delete();

            foreach ($annotations as $annotation) {
                SubmissionAnnotation::query()->create([
                    'submission_id' => $submission->id,
                    'question_id' => $annotation['question_id'] ?? null,
                    'created_by' => $actor->id,
                    'type' => SubmissionAnnotationType::from($annotation['type']),
                    'payload' => $annotation['payload'],
                ]);
            }
        });
    }
}
