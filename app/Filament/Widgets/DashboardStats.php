<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentChannel;
use App\Enums\PlacementStatus;
use App\Enums\RegistrationPurpose;
use App\Enums\UserRole;
use App\Filament\Widgets\Concerns\FormatsMoney;
use App\Filament\Widgets\Concerns\UsesDashboardRange;
use App\Models\CourseFeedback;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;

class DashboardStats extends Widget
{
    use FormatsMoney;
    use UsesDashboardRange;

    protected string $view = 'filament.widgets.kpi-cards';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{cards: list<array{label: string, value: HtmlString|string, icon: string, tint: string, footer: string}>}
     */
    protected function getViewData(): array
    {
        $byChannel = $this->inRange(Payment::query()->collected(), $this->paidAtColumn())
            ->selectRaw('channel, SUM(amount) as total')->groupBy('channel')->toBase()->pluck('total', 'channel');
        $online = (int) ($byChannel[PaymentChannel::Online->value] ?? 0);
        $total = (int) $byChannel->sum();

        $profiles = $this->inRange(StudentProfile::query(), 'created_at')
            ->selectRaw('registration_purpose, COUNT(*) as total')->groupBy('registration_purpose')->toBase()->pluck('total', 'registration_purpose');
        $training = (int) ($profiles[RegistrationPurpose::Training->value] ?? 0);
        $withPlacement = (int) ($profiles[RegistrationPurpose::TrainingAndPlacement->value] ?? 0);

        $students = $this->inRange(User::query()->where('role', UserRole::Student), 'created_at')->count();

        $placement = StudentProfile::query()->registeredForPlacement();
        $placed = (clone $placement)->where('placement_status', PlacementStatus::Hired)->count();
        $unplaced = $placement->count() - $placed;

        $ratingColumns = implode(' + ', array_keys(CourseFeedback::RATING_QUESTIONS));
        $rating = $this->inRange(CourseFeedback::query(), 'created_at')
            ->selectRaw("AVG(({$ratingColumns}) / ".count(CourseFeedback::RATING_QUESTIONS).'.0) as average')
            ->value('average');

        return ['cards' => [
            ['label' => 'Collected', 'value' => $this->bigMoney($total), 'icon' => 'heroicon-o-banknotes', 'tint' => 'violet',
                'footer' => 'Online '.$this->money($online).' · Offline '.$this->money($total - $online)],
            ['label' => 'Students registered', 'value' => (string) $students, 'icon' => 'heroicon-o-academic-cap', 'tint' => 'blue',
                'footer' => "Training {$training} · Training + Placement {$withPlacement}"],
            ['label' => 'Placed', 'value' => (string) $placed, 'icon' => 'heroicon-o-check-badge', 'tint' => 'green',
                'footer' => 'Training + Placement'],
            ['label' => 'Unplaced', 'value' => (string) $unplaced, 'icon' => 'heroicon-o-clock', 'tint' => 'amber',
                'footer' => 'Still open for work'],
            ['label' => 'Feedback rating', 'value' => $rating === null ? '—' : number_format((float) $rating, 1).' / 5', 'icon' => 'heroicon-o-star', 'tint' => 'orange',
                'footer' => 'Average across forms'],
        ]];
    }
}
