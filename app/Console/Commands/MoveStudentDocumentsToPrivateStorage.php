<?php

namespace App\Console\Commands;

use App\Models\StudentProfile;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('student-documents:make-private')]
#[Description('Move students\' passport and licence documents from the public disk to private storage')]
class MoveStudentDocumentsToPrivateStorage extends Command
{
    public function handle(): int
    {
        $private = Storage::disk(StudentProfile::PRIVATE_DISK);
        $public = Storage::disk('public');
        $moved = 0;
        $missing = 0;

        foreach (StudentProfile::PRIVATE_DOCUMENTS as $column) {
            StudentProfile::query()->whereNotNull($column)->each(function (StudentProfile $profile) use ($column, $private, $public, &$moved, &$missing): void {
                $path = $profile->{$column};

                if ($public->exists($path)) {
                    if (! $private->exists($path)) {
                        $private->writeStream($path, $public->readStream($path));
                    }

                    $public->delete($path);
                    $moved++;
                } elseif (! $private->exists($path)) {
                    $missing++;
                }
            });
        }

        $this->info("Moved {$moved} document(s) to private storage.");

        if ($missing > 0) {
            $this->warn("{$missing} document(s) listed on a profile were not found on either disk.");
        }

        return self::SUCCESS;
    }
}
