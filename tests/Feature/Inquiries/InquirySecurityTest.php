<?php

namespace Tests\Feature\Inquiries;

use App\Models\EducationLevel;
use App\Models\Inquiry;
use App\Models\User;
use App\Notifications\InquirySubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InquirySecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EducationLevel::query()->create([
            'name' => 'Graduate',
            'slug' => 'graduate',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function candidate(array $overrides = []): array
    {
        return array_merge([
            'type' => 'candidate',
            'first_name' => 'Nanuka',
            'last_name' => 'Thapa',
            'date_of_birth' => '1990-01-01',
            'email' => 'nanuka@example.com',
            'phone' => '+91 98325 81198',
            'city' => 'Gangtok',
            'country' => 'India',
            'education_level' => 'graduate',
            'years_of_experience' => '1-3',
            'languages' => 'English',
            'qualification_certificate' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
            'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function recruiter(array $overrides = []): array
    {
        return array_merge([
            'type' => 'recruiter',
            'company_name' => 'Acme Hiring',
            'industry' => 'Hospitality',
            'contact_name' => 'Jane Doe',
            'designation' => 'HR Manager',
            'email' => 'jane@example.com',
            'phone' => '+49 151 2345 6789',
            'roles_hiring' => 'Nurses',
            'positions_count' => '1-5',
            'required_skills' => 'German B1, nursing diploma',
        ], $overrides);
    }

    public function test_candidate_submission_is_stored_and_documents_are_private(): void
    {
        $this->post('/inquiries', $this->candidate())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $inquiry = Inquiry::query()->sole();

        Storage::disk('local')->assertExists($inquiry->data['resume']);
        Storage::disk('public')->assertMissing($inquiry->data['resume']);
        $this->assertArrayNotHasKey('fax_number', $inquiry->data);
        $this->assertArrayNotHasKey('cf-turnstile-response', $inquiry->data);
        Notification::assertSentOnDemand(InquirySubmitted::class);
    }

    public function test_recruiter_submission_is_stored(): void
    {
        $this->post('/inquiries', $this->recruiter())
            ->assertSessionHasNoErrors();

        $this->assertSame('Acme Hiring', Inquiry::query()->sole()->data['company_name']);
    }

    public function test_filled_honeypot_is_rejected(): void
    {
        $this->post('/inquiries', $this->recruiter(['fax_number' => '12345']))
            ->assertSessionHasErrors('fax_number');

        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_short_values_are_rejected(): void
    {
        $this->post('/inquiries', $this->candidate(['first_name' => 'A', 'languages' => 'E', 'career_goals' => 'short']))
            ->assertSessionHasErrors(['first_name', 'languages', 'career_goals']);

        $this->post('/inquiries', $this->recruiter(['required_skills' => 'abc']))
            ->assertSessionHasErrors('required_skills');
    }

    public function test_oversized_text_is_rejected(): void
    {
        $this->post('/inquiries', $this->recruiter(['additional_notes' => str_repeat('a', 1001)]))
            ->assertSessionHasErrors('additional_notes');
    }

    public function test_markup_is_stripped_and_script_only_values_are_rejected(): void
    {
        $this->post('/inquiries', $this->recruiter(['company_name' => '<b>Acme</b> Hiring <script>alert(1)</script>']))
            ->assertSessionHasNoErrors();

        $this->assertStringNotContainsString('<', Inquiry::query()->sole()->data['company_name']);

        $this->post('/inquiries', $this->candidate(['first_name' => '<script></script>']))
            ->assertSessionHasErrors('first_name');
    }

    public function test_names_reject_digits_and_symbols(): void
    {
        $this->post('/inquiries', $this->candidate(['last_name' => 'Th4pa$$']))
            ->assertSessionHasErrors('last_name');
    }

    public function test_website_must_be_http_or_https(): void
    {
        $this->post('/inquiries', $this->recruiter(['website' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('website');

        $this->post('/inquiries', $this->recruiter(['website' => 'https://acme.example']))
            ->assertSessionHasNoErrors();
    }

    public function test_documents_must_be_real_allowed_types(): void
    {
        $this->post('/inquiries', $this->candidate([
            'resume' => UploadedFile::fake()->create('resume.php', 10, 'application/x-php'),
        ]))->assertSessionHasErrors('resume');

        $this->post('/inquiries', $this->candidate([
            'qualification_certificate' => UploadedFile::fake()->create('cert.pdf', 6000, 'application/pdf'),
        ]))->assertSessionHasErrors('qualification_certificate');
    }

    public function test_resubmitting_the_same_email_within_an_hour_is_not_stored_twice(): void
    {
        $this->post('/inquiries', $this->recruiter())->assertSessionHasNoErrors();
        $this->post('/inquiries', $this->recruiter())->assertSessionHasNoErrors();

        $this->assertDatabaseCount('inquiries', 1);
        Notification::assertSentOnDemandTimes(InquirySubmitted::class, 1);
    }

    public function test_turnstile_is_required_when_configured(): void
    {
        config(['services.turnstile.secret' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->post('/inquiries', $this->recruiter())
            ->assertSessionHasErrors('cf-turnstile-response');

        $this->post('/inquiries', $this->recruiter(['cf-turnstile-response' => 'bad-token']))
            ->assertSessionHasErrors('cf-turnstile-response');

    }

    public function test_valid_turnstile_token_is_accepted(): void
    {
        config(['services.turnstile.secret' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->post('/inquiries', $this->recruiter(['cf-turnstile-response' => 'good-token']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('inquiries', 1);
    }

    public function test_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/inquiries', $this->recruiter(['email' => "user{$i}@example.com"]));
        }

        $this->post('/inquiries', $this->recruiter(['email' => 'late@example.com']))
            ->assertStatus(429);
    }

    public function test_admin_can_download_private_and_legacy_public_documents_but_guests_cannot(): void
    {
        Storage::disk('local')->put('inquiries/candidate/new.pdf', 'new');
        Storage::disk('public')->put('inquiries/candidate/old.pdf', 'old');

        $new = Inquiry::query()->create(['type' => 'candidate', 'data' => ['resume' => 'inquiries/candidate/new.pdf']]);
        $old = Inquiry::query()->create(['type' => 'candidate', 'data' => ['resume' => 'inquiries/candidate/old.pdf']]);
        $url = fn (Inquiry $inquiry): string => "/bv-admin/inquiries/{$inquiry->id}/documents/resume/download";

        $this->get($url($new))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create());

        $this->get($url($new))->assertOk();
        $this->get($url($old))->assertOk();
        $this->get("/bv-admin/inquiries/{$new->id}/documents/id_document/download")->assertNotFound();
    }

    public function test_dropdown_fields_only_accept_listed_options(): void
    {
        $this->post('/inquiries', $this->recruiter([
            'positions_count' => '999999',
            'company_size' => 'huge',
            'hiring_timeline' => 'whenever',
        ]))->assertSessionHasErrors(['positions_count', 'company_size', 'hiring_timeline']);

        $this->post('/inquiries', $this->candidate(['years_of_experience' => '<script>']))
            ->assertSessionHasErrors('years_of_experience');

        $this->post('/inquiries', $this->recruiter([
            'positions_count' => '6-15',
            'company_size' => '11-50',
            'hiring_timeline' => '1-3-months',
        ]))->assertSessionHasNoErrors();
    }
}
