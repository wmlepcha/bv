<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\RecruiterProjects\Pages\ListRecruiterProjects;
use App\Filament\Resources\RecruiterProjects\Pages\ViewRecruiterProject;
use App\Filament\Resources\RecruiterProjects\RelationManagers\StudentsRelationManager;
use App\Models\ProjectRequirement;
use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class RecruiterProjectResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private RecruiterProject $project;

    private RecruiterProjectCandidate $entry;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ProjectRequirement::DISK);
        Notification::fake();

        $this->admin = User::factory()->admin()->create();
        $this->project = RecruiterProject::factory()->create(['finalized_at' => now(), 'recruiter_updated_at' => now()]);
        $profile = StudentProfile::factory()->enrolled()->create(['mobile_whatsapp' => '+919800000009']);
        $this->entry = RecruiterProjectCandidate::create(['recruiter_project_id' => $this->project->id, 'student_profile_id' => $profile->id]);
        $this->entry->requirements()->createMany([['title' => 'Passport copy'], ['title' => 'Degree']]);
    }

    public function test_admin_sees_only_finalized_projects_and_viewing_clears_the_updated_flag(): void
    {
        $draft = RecruiterProject::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(ListRecruiterProjects::class)
            ->assertCanSeeTableRecords([$this->project])
            ->assertCanNotSeeTableRecords([$draft]);

        Livewire::actingAs($this->admin)
            ->test(ViewRecruiterProject::class, ['record' => $this->project->getKey()])
            ->assertSuccessful();

        $this->assertNull($this->project->fresh()->recruiter_updated_at);
    }

    public function test_students_table_shows_contact_details_and_progress(): void
    {
        Livewire::actingAs($this->admin)
            ->test(StudentsRelationManager::class, ['ownerRecord' => $this->project, 'pageClass' => ViewRecruiterProject::class])
            ->assertCanSeeTableRecords([$this->entry])
            ->assertSee('+919800000009')
            ->assertSee('0 / 2');
    }

    public function test_admin_uploads_documents_then_sends_to_recruiter(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(StudentsRelationManager::class, ['ownerRecord' => $this->project, 'pageClass' => ViewRecruiterProject::class]);

        $component->assertTableActionHidden('sendToRecruiter', $this->entry);

        $requirements = $this->entry->requirements;
        $paths = [];
        foreach ($requirements as $requirement) {
            $paths[$requirement->id] = "project-documents/{$this->project->id}/{$this->entry->id}/{$requirement->id}.pdf";
            Storage::disk(ProjectRequirement::DISK)->put($paths[$requirement->id], 'pdf');
        }

        $component->mountTableAction('documents', $this->entry)
            ->setTableActionData(['requirements' => $requirements->map(fn (ProjectRequirement $requirement): array => [
                'id' => $requirement->id,
                'title' => $requirement->title,
                'original_name' => $requirement->title.'.pdf',
                'file' => [(string) Str::uuid() => $paths[$requirement->id]],
            ])->all()])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->entry->refresh()->load('requirements');
        $this->assertTrue($this->entry->isReadyForApproval());
        $this->assertSame(2, $this->entry->uploadedCount());
        Storage::disk(ProjectRequirement::DISK)->assertExists($this->entry->requirements[0]->file_path);

        $component->assertTableActionVisible('sendToRecruiter', $this->entry)
            ->callTableAction('sendToRecruiter', $this->entry);

        $this->assertTrue($this->entry->fresh()->isSentToRecruiter());
        $this->assertFalse($this->entry->fresh()->isApproved());
        $this->assertFalse($this->entry->studentProfile->fresh()->isHired());
    }
}
