<?php

namespace Tests\Feature\Recruiter;

use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RecruiterDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RecruiterDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_candidate_is_listed_with_documents_and_is_idempotent(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $this->seed(RecruiterDemoSeeder::class);
        $this->seed(RecruiterDemoSeeder::class);

        $profile = StudentProfile::query()->whereHas('user', fn ($q) => $q->where('email', RecruiterDemoSeeder::EMAIL))->sole();
        Storage::disk('public')->assertExists($profile->passport_photo_path);
        Storage::disk('public')->assertExists($profile->vitae_data['licenses'][0]['certificate_path']);

        $this->actingAs(User::factory()->recruiter()->create())
            ->get(route('recruiter.students.index', ['profession' => 'nurse_registration', 'experience' => 'experienced', 'level' => 'A1']))
            ->assertInertia(fn (Assert $page) => $page->has('profiles.data', 2));

        $this->get(route('recruiter.students.show', $profile))
            ->assertInertia(fn (Assert $page) => $page
                ->where('restricted', false)
                ->has('cv.vitae.licenses', 1)
                ->has('language.course'));
    }

    public function test_dominique_is_completed_without_overwriting_what_he_entered(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $user = User::factory()->student()->paid()->create(['email' => RecruiterDemoSeeder::DOMINIQUE_EMAIL]);
        StudentProfile::factory()->forPlacement()->create(['user_id' => $user->id, 'introduction' => 'My own introduction', 'skills' => ['trial']]);

        $this->seed(RecruiterDemoSeeder::class);

        $profile = $user->studentProfile()->first();
        $this->assertSame('My own introduction', $profile->introduction);
        $this->assertSame('Paediatric nursing', $profile->skills[0]);
        $this->assertCount(3, $profile->vitae_data['experiences']);
        $this->assertCount(4, $profile->vitae_data['qualifications']);
        Storage::disk('public')->assertExists($profile->vitae_data['licenses'][0]['certificate_path']);
        Storage::disk('local')->assertExists($profile->passport_document_path);
        Storage::disk('public')->assertMissing($profile->passport_document_path);
        $this->assertSame(['nurse_registration'], $profile->professions);

        $manager = User::query()->where('email', RecruiterDemoSeeder::RECRUITMENT_MANAGER_EMAIL)->get();
        $this->assertCount(1, $manager);
        $this->assertTrue($manager->first()->isRecruitmentManager());
    }
}
