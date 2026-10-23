<?php

namespace Tests\Feature\Recruiter;

use App\Models\ProjectRequirement;
use App\Models\RecruiterProject;
use App\Models\RecruiterProjectCandidate;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectDocumentViewTest extends TestCase
{
    use RefreshDatabase;

    private User $recruiter;

    private RecruiterProjectCandidate $entry;

    private ProjectRequirement $pdf;

    private ProjectRequirement $word;

    private ProjectRequirement $png;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ProjectRequirement::DISK);

        $this->recruiter = User::factory()->recruiter()->create();
        $project = RecruiterProject::factory()->create(['user_id' => $this->recruiter->id, 'finalized_at' => now()]);
        $this->entry = RecruiterProjectCandidate::create([
            'recruiter_project_id' => $project->id,
            'student_profile_id' => StudentProfile::factory()->enrolled()->forPlacement()->create()->id,
            'sent_to_recruiter_at' => now(),
        ]);

        $this->pdf = $this->requirement('Passport', 'passport.pdf', UploadedFile::fake()->createWithContent('passport.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF")->getContent());
        $this->word = $this->requirement('Contract', 'contract.docx', 'PK'.str_repeat("\0", 30));
        $this->png = $this->requirement('Photo', 'photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    }

    private function requirement(string $title, string $name, string $content): ProjectRequirement
    {
        $path = "project-documents/{$this->entry->recruiter_project_id}/{$this->entry->id}/{$name}";
        Storage::disk(ProjectRequirement::DISK)->put($path, $content);

        return $this->entry->requirements()->create(['title' => $title, 'file_path' => $path, 'original_name' => $name]);
    }

    public function test_documents_open_inline_for_the_owner_the_manager_and_admins(): void
    {
        foreach ([$this->recruiter, User::factory()->recruitmentManager()->create(), User::factory()->admin()->create()] as $viewer) {
            $this->actingAs($viewer)->get(route('project-documents.view', $this->pdf))
                ->assertOk()
                ->assertHeader('Content-Type', 'application/pdf')
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
            $this->assertStringContainsString('inline', (string) $this->get(route('project-documents.view', $this->png))->headers->get('Content-Disposition'));
        }
    }

    public function test_download_still_forces_an_attachment(): void
    {
        $this->actingAs($this->recruiter)->get(route('project-documents.download', $this->pdf))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=passport.pdf');
    }

    public function test_types_that_cannot_be_previewed_are_refused_inline(): void
    {
        $this->actingAs($this->recruiter)->get(route('project-documents.view', $this->word))->assertStatus(415);
        $this->get(route('project-documents.download', $this->word))->assertOk();
    }

    public function test_the_same_access_rules_apply_to_previews(): void
    {
        $this->actingAs(User::factory()->recruiter()->create())->get(route('project-documents.view', $this->pdf))->assertNotFound();
        $this->actingAs(User::factory()->student()->create())->get(route('project-documents.view', $this->pdf))->assertNotFound();

        $this->entry->update(['sent_to_recruiter_at' => null]);
        $this->actingAs($this->recruiter)->get(route('project-documents.view', $this->pdf))->assertNotFound();
        $this->actingAs(User::factory()->recruitmentManager()->create())->get(route('project-documents.view', $this->pdf))->assertOk();

        auth()->logout();
        $this->get(route('project-documents.view', $this->pdf))->assertRedirect(route('login'));
    }

    public function test_sample_previews_follow_the_project_owner_rules(): void
    {
        $path = "project-samples/{$this->entry->recruiter_project_id}/sample.png";
        Storage::disk(ProjectRequirement::DISK)->put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
        $this->pdf->update(['sample_path' => $path, 'sample_name' => 'sample.png']);

        $this->actingAs($this->recruiter)->get(route('project-samples.view', $this->pdf))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs(User::factory()->recruiter()->create())->get(route('project-samples.view', $this->pdf))->assertNotFound();
        $this->get(route('project-samples.view', $this->word))->assertNotFound();
    }

    public function test_pages_hand_the_preview_links_to_the_front_end(): void
    {
        $this->actingAs($this->recruiter)->get(route('recruiter.projects.show', $this->entry->recruiter_project_id))
            ->assertInertia(fn (Assert $page) => $page
                ->where('candidates.0.entry.requirements.0.view_url', route('project-documents.view', $this->pdf))
                ->where('candidates.0.entry.requirements.0.sample_view_url', null));

        $this->actingAs(User::factory()->recruitmentManager()->create())->get(route('recruitment-manager.projects.show', $this->entry->recruiter_project_id))
            ->assertInertia(fn (Assert $page) => $page->where('entries.0.requirements.0.view_url', route('project-documents.view', $this->pdf)));
    }
}
