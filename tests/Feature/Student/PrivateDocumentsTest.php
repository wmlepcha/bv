<?php

namespace Tests\Feature\Student;

use App\Models\BatchEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\CvProfilePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private StudentProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->profile = StudentProfile::factory()->enrolled()->forPlacement()->create([
            'passport_document_path' => 'student-documents/1/passport-document/p.pdf',
            'drivers_license_document_path' => 'student-documents/1/drivers-license-document/l.pdf',
        ]);
        Storage::disk('local')->put($this->profile->passport_document_path, '%PDF passport');
        Storage::disk('local')->put($this->profile->drivers_license_document_path, '%PDF licence');
    }

    private function url(string $kind = 'passport', ?StudentProfile $profile = null): string
    {
        return route('student-documents.show', [$profile ?? $this->profile, $kind]);
    }

    public function test_upload_goes_to_private_storage_and_the_cv_links_to_the_protected_route(): void
    {
        $student = User::factory()->paid()->student()->create(['email_verified_at' => now()]);
        $fresh = StudentProfile::factory()->create(['user_id' => $student->id]);

        $this->actingAs($student)->post(route('student.cv.update'), [
            'first_name' => 'A', 'last_name' => 'B', 'vitae' => ['is_newbie' => true],
            'passport_document' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $fresh->refresh();
        Storage::disk('local')->assertExists($fresh->passport_document_path);
        Storage::disk('public')->assertMissing($fresh->passport_document_path);

        $cv = CvProfilePresenter::present($student, $fresh);
        $this->assertSame('/student-documents/'.$fresh->id.'/passport', $cv['passport_document_url']);
        $this->assertNull($cv['drivers_license_document_url']);
        $this->assertStringNotContainsString('/storage/', (string) $cv['passport_document_url']);
    }

    public function test_replacing_a_document_removes_the_old_private_file(): void
    {
        $old = $this->profile->passport_document_path;

        $this->actingAs($this->profile->user)->post(route('student.cv.update'), [
            'first_name' => 'A', 'last_name' => 'B', 'vitae' => ['is_newbie' => true],
            'passport_document' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        Storage::disk('local')->assertMissing($old);
        $this->assertNotSame($old, $this->profile->fresh()->passport_document_path);
    }

    public function test_the_student_staff_trainer_and_placement_recruiters_can_open_documents(): void
    {
        $trainer = User::factory()->teacher()->create();
        BatchEnrollment::query()->where('student_id', $this->profile->user_id)->firstOrFail()->batch->update(['teacher_id' => $trainer->id]);

        foreach ([$this->profile->user, User::factory()->admin()->create(), User::factory()->recruitmentManager()->create(), $trainer, User::factory()->recruiter()->create(), User::factory()->employee()->create()] as $viewer) {
            $this->actingAs($viewer)->get($this->url())->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        }

        $this->get($this->url('drivers-license'))->assertOk();
    }

    public function test_everyone_else_is_refused_and_guests_are_sent_to_login(): void
    {
        $otherTrainer = User::factory()->teacher()->create();
        $trainingOnly = StudentProfile::factory()->enrolled()->create(['passport_document_path' => 'student-documents/2/p.pdf']);
        Storage::disk('local')->put($trainingOnly->passport_document_path, 'x');

        foreach ([User::factory()->student()->create(), $otherTrainer, User::factory()->trainingManager()->create()] as $viewer) {
            $this->actingAs($viewer)->get($this->url())->assertNotFound();
        }

        $recruiter = User::factory()->recruiter()->create();
        $this->actingAs($recruiter)->get($this->url('passport', $trainingOnly))->assertNotFound();

        $this->profile->update(['placement_status' => 'hired']);
        $this->get($this->url())->assertNotFound();

        auth()->logout();
        $this->get($this->url())->assertRedirect(route('login'));
    }

    public function test_unknown_kinds_and_missing_documents_are_not_found(): void
    {
        $this->actingAs($this->profile->user);

        $this->get('/student-documents/'.$this->profile->id.'/secrets')->assertNotFound();

        $this->profile->update(['drivers_license_document_path' => null]);
        $this->get($this->url('drivers-license'))->assertNotFound();

        Storage::disk('local')->delete($this->profile->passport_document_path);
        $this->get($this->url())->assertNotFound();
    }

    public function test_legacy_public_files_are_still_served_only_through_the_route_until_moved(): void
    {
        $this->profile->update(['passport_document_path' => 'cv/9/passport-document/legacy.pdf']);
        Storage::disk('public')->put('cv/9/passport-document/legacy.pdf', '%PDF legacy');

        $this->actingAs($this->profile->user)->get($this->url())->assertOk();
        $this->actingAs(User::factory()->student()->create())->get($this->url())->assertNotFound();
    }

    public function test_command_moves_public_documents_to_private_storage_and_is_repeatable(): void
    {
        $this->profile->update([
            'passport_document_path' => 'cv/9/passport-document/legacy.pdf',
            'drivers_license_document_path' => 'cv/9/drivers-license-document/legacy.jpg',
        ]);
        Storage::disk('public')->put('cv/9/passport-document/legacy.pdf', '%PDF legacy');
        Storage::disk('public')->put('cv/9/drivers-license-document/legacy.jpg', 'jpg');
        Storage::disk('public')->put('cv/9/passport/photo.jpg', 'photo');

        $this->artisan('student-documents:make-private')->expectsOutputToContain('Moved 2 document(s)')->assertSuccessful();

        Storage::disk('local')->assertExists('cv/9/passport-document/legacy.pdf');
        Storage::disk('local')->assertExists('cv/9/drivers-license-document/legacy.jpg');
        Storage::disk('public')->assertMissing('cv/9/passport-document/legacy.pdf');
        Storage::disk('public')->assertMissing('cv/9/drivers-license-document/legacy.jpg');
        Storage::disk('public')->assertExists('cv/9/passport/photo.jpg');
        $this->assertSame('%PDF legacy', Storage::disk('local')->get('cv/9/passport-document/legacy.pdf'));

        $this->artisan('student-documents:make-private')->expectsOutputToContain('Moved 0 document(s)')->assertSuccessful();
    }
}
