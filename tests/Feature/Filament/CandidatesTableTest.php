<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ActivationCodes\Pages\ListActivationCodes;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Models\ActivationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CandidatesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_name_email_and_unlocked_show_until_more_columns_are_chosen(): void
    {
        Livewire::actingAs(User::factory()->salesTeam()->create())->test(ListCandidates::class)
            ->assertCanRenderTableColumn('name')
            ->assertCanRenderTableColumn('email')
            ->assertCanRenderTableColumn('is_unlocked')
            ->assertCanNotRenderTableColumn('student_number')
            ->assertCanNotRenderTableColumn('studentProfile.registration_purpose')
            ->assertCanNotRenderTableColumn('studentProfile.placement_status')
            ->assertCanNotRenderTableColumn('registrationPlan.name')
            ->assertCanNotRenderTableColumn('registration_expires_at')
            ->assertCanNotRenderTableColumn('registration_fee_paid_via')
            ->assertCanNotRenderTableColumn('created_at');
    }

    public function test_hidden_columns_are_still_searchable_and_the_row_actions_remain(): void
    {
        $match = User::factory()->student()->unpaid()->create(['name' => 'Dolma', 'student_number' => 'SK-COI-2026-777']);
        $other = User::factory()->student()->unpaid()->create(['name' => 'Karma', 'student_number' => 'SK-COI-2026-888']);

        Livewire::actingAs(User::factory()->admin()->create())->test(ListCandidates::class)
            ->searchTable('SK-COI-2026-777')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other])
            ->assertTableActionVisible('markAsAlreadyPaid', $match);
    }

    public function test_columns_remembered_from_before_the_clean_up_do_not_override_the_new_defaults(): void
    {
        session()->put('tables.'.md5(ListCandidates::class).'_columns', [[
            'type' => 'column', 'name' => 'student_number', 'label' => 'Candidate ID',
            'isHidden' => false, 'isToggled' => true, 'isToggleable' => true, 'isToggledHiddenByDefault' => false,
        ]]);

        Livewire::actingAs(User::factory()->salesTeam()->create())->test(ListCandidates::class)
            ->assertCanNotRenderTableColumn('student_number')
            ->assertCanRenderTableColumn('name');
    }

    public function test_registration_codes_show_only_student_code_and_email_by_default(): void
    {
        $student = User::factory()->student()->unpaid()->create(['name' => 'Dolma', 'email' => 'dolma@example.com']);
        $code = ActivationCode::query()->create(['code' => 'BV-26-TEST', 'email' => $student->email, 'assigned_user_id' => $student->id, 'student_number' => 'SK-COI-2026-777']);
        session()->put('tables.'.md5(ListActivationCodes::class).'_columns', [[
            'type' => 'column', 'name' => 'used_at', 'label' => 'Used',
            'isHidden' => false, 'isToggled' => true, 'isToggleable' => false, 'isToggledHiddenByDefault' => false,
        ]]);

        Livewire::actingAs(User::factory()->salesTeam()->create())->test(ListActivationCodes::class)
            ->assertCanRenderTableColumn('assignedUser.name')
            ->assertCanRenderTableColumn('code')
            ->assertCanRenderTableColumn('email')
            ->assertCanNotRenderTableColumn('student_number')
            ->assertCanNotRenderTableColumn('used_at')
            ->assertCanNotRenderTableColumn('usedBy.name')
            ->assertCanNotRenderTableColumn('created_at')
            ->searchTable('SK-COI-2026-777')
            ->assertCanSeeTableRecords([$code]);
    }
}
