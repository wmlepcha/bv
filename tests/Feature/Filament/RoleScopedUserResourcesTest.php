<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\Admins\AdminResource;
use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Candidates\CandidateResource;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\Recruiters\Pages\CreateRecruiter;
use App\Filament\Resources\Recruiters\RecruiterResource;
use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\TeacherResource;
use App\Filament\Resources\TrainingManagers\Pages\CreateTrainingManager;
use App\Filament\Resources\TrainingManagers\TrainingManagerResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleScopedUserResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_query_only_includes_students(): void
    {
        $candidate = User::factory()->student()->create();
        $recruiter = User::factory()->recruiter()->create();
        $employee = User::factory()->employee()->create();
        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();

        $ids = CandidateResource::getEloquentQuery()->pluck('id')->all();

        $this->assertSame([$candidate->id], $ids);
        $this->assertNotContains($recruiter->id, $ids);
        $this->assertNotContains($employee->id, $ids);
        $this->assertNotContains($teacher->id, $ids);
        $this->assertNotContains($admin->id, $ids);
    }

    public function test_recruiter_teacher_and_admin_queries_are_role_scoped(): void
    {
        $candidate = User::factory()->student()->create();
        $recruiter = User::factory()->recruiter()->create();
        $employee = User::factory()->employee()->create();
        $teacher = User::factory()->teacher()->create();
        $trainingManager = User::factory()->trainingManager()->create();
        $admin = User::factory()->admin()->create();

        $this->assertSame([$recruiter->id], RecruiterResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$teacher->id], TeacherResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$trainingManager->id], TrainingManagerResource::getEloquentQuery()->pluck('id')->all());
        $this->assertSame([$admin->id], AdminResource::getEloquentQuery()->pluck('id')->all());

        $this->assertNotContains($employee->id, RecruiterResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($employee->id, TeacherResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($employee->id, AdminResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($candidate->id, RecruiterResource::getEloquentQuery()->pluck('id')->all());
        $this->assertNotContains($trainingManager->id, TeacherResource::getEloquentQuery()->pluck('id')->all());
    }

    public function test_candidates_cannot_be_created_via_resource(): void
    {
        $this->assertFalse(CandidateResource::canCreate());
    }

    public function test_creating_recruiter_sets_role_and_unlocks_registration(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateRecruiter::class)
            ->fillForm([
                'name' => 'Recruiter User',
                'email' => 'recruiter@example.com',
                'password' => 'password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'recruiter@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Recruiter, $user->role);
        $this->assertNotNull($user->registration_paid_at);
    }

    public function test_creating_teacher_sets_role_and_unlocks_registration(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateTeacher::class)
            ->fillForm([
                'name' => 'Teacher User',
                'email' => 'teacher@example.com',
                'password' => 'password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'teacher@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Teacher, $user->role);
        $this->assertNotNull($user->registration_paid_at);
    }

    public function test_creating_training_manager_sets_role_and_unlocks_registration(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateTrainingManager::class)
            ->fillForm([
                'name' => 'Training Manager User',
                'email' => 'training-manager@example.com',
                'password' => 'password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'training-manager@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::TrainingManager, $user->role);
        $this->assertNotNull($user->registration_paid_at);
    }

    public function test_creating_admin_sets_role_and_unlocks_registration(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateAdmin::class)
            ->fillForm([
                'name' => 'Admin User',
                'email' => 'new-admin@example.com',
                'password' => 'password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'new-admin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertNotNull($user->registration_paid_at);
    }

    public function test_candidate_list_page_is_accessible_to_admin(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->student()->create(['name' => 'Visible Candidate']);

        Livewire::actingAs($admin)
            ->test(ListCandidates::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(
                User::query()->where('role', UserRole::Student)->get()
            );
    }

    public function test_editing_wrong_role_via_candidate_resource_returns_404(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($admin)
            ->get(route('filament.bv-admin.candidates.resources.candidates.edit', ['record' => $teacher]))
            ->assertNotFound();
    }

    public function test_candidate_edit_page_loads_for_student(): void
    {
        $admin = User::factory()->admin()->create();
        $candidate = User::factory()->student()->create();

        Livewire::actingAs($admin)
            ->test(EditCandidate::class, ['record' => $candidate->getRouteKey()])
            ->assertSuccessful();
    }
}
