<?php

namespace App\Filament\Resources\RecruiterProjects\Actions;

use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Services\ProjectStudentMessagingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * "Send congratulations" / "Send requirements" for a whole project (header) or one student (table row).
 */
class MessageStudentsAction
{
    public static function congratulate(bool $forOneStudent = false): Action
    {
        return self::make(
            name: $forOneStudent ? 'congratulate' : 'congratulateAll',
            label: $forOneStudent ? 'Congratulate' : 'Send congratulations',
            icon: 'heroicon-o-sparkles',
            sentColumn: 'congratulated_at',
            hint: 'Students get this in their notifications (with a confetti card), on WhatsApp and by email.',
            defaultMessage: fn (RecruiterProject $project): string => app(ProjectStudentMessagingService::class)->defaultCongratulation($project),
            send: fn (Collection $entries, string $message): array => app(ProjectStudentMessagingService::class)->congratulate($entries, $message),
        );
    }

    public static function requirements(bool $forOneStudent = false): Action
    {
        return self::make(
            name: $forOneStudent ? 'sendRequirement' : 'sendRequirements',
            label: $forOneStudent ? 'Send requirements' : 'Send requirements',
            icon: 'heroicon-o-clipboard-document-list',
            sentColumn: 'requirements_sent_at',
            hint: "Each student's own list of required documents is added below your message.",
            defaultMessage: fn (RecruiterProject $project): string => app(ProjectStudentMessagingService::class)->defaultRequirementsIntro($project),
            send: fn (Collection $entries, string $message): array => app(ProjectStudentMessagingService::class)->sendRequirements($entries, $message),
        );
    }

    /**
     * @param  callable(RecruiterProject): string  $defaultMessage
     * @param  callable(Collection<int, RecruiterProjectCandidate>, string): array{sent: int, without_whatsapp: int, skipped: int}  $send
     */
    private static function make(string $name, string $label, string $icon, string $sentColumn, string $hint, callable $defaultMessage, callable $send): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->modalHeading(function (Model $record) use ($label): string {
                $count = self::entries($record)->count();

                return $label.' to '.$count.' '.($count === 1 ? 'student' : 'students').'?';
            })
            ->modalDescription(function (Model $record) use ($sentColumn, $hint): string {
                $already = self::entries($record)->whereNotNull($sentColumn)->count();

                return $hint.($already > 0 ? " {$already} already received this and will receive it again." : '');
            })
            ->modalSubmitActionLabel('Send')
            ->fillForm(fn (Model $record): array => ['message' => $defaultMessage(self::project($record))])
            ->schema([
                Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->rows(4)
                    ->maxLength(600),
            ])
            ->action(function (array $data, Model $record) use ($send): void {
                $result = $send(self::entries($record), $data['message']);

                Notification::make()
                    ->title("Sent to {$result['sent']} ".($result['sent'] === 1 ? 'student' : 'students'))
                    ->body(collect([
                        $result['without_whatsapp'] > 0 ? "{$result['without_whatsapp']} without a WhatsApp number" : null,
                        $result['skipped'] > 0 ? "{$result['skipped']} skipped" : null,
                    ])->filter()->implode(' · ') ?: null)
                    ->success()
                    ->send();
            });
    }

    private static function project(Model $record): RecruiterProject
    {
        return $record instanceof RecruiterProject ? $record : $record->project;
    }

    /**
     * @return Collection<int, RecruiterProjectCandidate>
     */
    private static function entries(Model $record): Collection
    {
        return $record instanceof RecruiterProject
            ? $record->entries()->with(['requirements', 'studentProfile.user'])->get()
            : new Collection([$record]);
    }
}
