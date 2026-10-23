<?php

namespace App\Services;

use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Notifications\StudentRequirementsRequested;
use App\Notifications\StudentShortlisted;
use App\Support\StudentNotificationChannels;
use Illuminate\Support\Collection;

class ProjectStudentMessagingService
{
    public function defaultCongratulation(RecruiterProject $project): string
    {
        return 'You have been shortlisted for "'.$project->name.'" ('.$project->sector.', '.$project->country.'). We will call you soon.';
    }

    public function defaultRequirementsIntro(RecruiterProject $project): string
    {
        return 'To move forward with "'.$project->name.'", please get the following documents ready:';
    }

    /**
     * @param  Collection<int, RecruiterProjectCandidate>  $entries
     * @return array{sent: int, without_whatsapp: int, skipped: int}
     */
    public function congratulate(Collection $entries, string $message): array
    {
        return $this->send($entries, 'congratulated_at', false, fn (RecruiterProjectCandidate $entry) => new StudentShortlisted($entry, $message));
    }

    /**
     * @param  Collection<int, RecruiterProjectCandidate>  $entries
     * @return array{sent: int, without_whatsapp: int, skipped: int}
     */
    public function sendRequirements(Collection $entries, string $message): array
    {
        return $this->send($entries, 'requirements_sent_at', true, fn (RecruiterProjectCandidate $entry) => new StudentRequirementsRequested($entry, $message));
    }

    /**
     * @param  Collection<int, RecruiterProjectCandidate>  $entries
     * @return array{sent: int, without_whatsapp: int, skipped: int}
     */
    private function send(Collection $entries, string $stamp, bool $needsRequirements, callable $notification): array
    {
        $entries->loadMissing(['requirements', 'studentProfile.user']);
        $result = ['sent' => 0, 'without_whatsapp' => 0, 'skipped' => 0];

        foreach ($entries as $entry) {
            $user = $entry->studentProfile?->user;

            $skip = $user === null
                || ($entry->studentProfile->isHired() && ! $entry->isApproved())
                || ($needsRequirements && $entry->requirements->isEmpty());

            if ($skip) {
                $result['skipped']++;

                continue;
            }

            $user->notify($notification($entry));
            $entry->forceFill([$stamp => now()])->save();
            $result['sent']++;

            if (! StudentNotificationChannels::shouldSendWhatsApp($user)) {
                $result['without_whatsapp']++;
            }
        }

        return $result;
    }
}
