<?php

namespace Tests\Feature\RecruitmentManager;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InquiriesTest extends TestCase
{
    use RefreshDatabase;

    private function candidateInquiry(): Inquiry
    {
        Storage::fake('local');
        Storage::disk('local')->put('inquiries/candidate/resume.pdf', 'pdf');

        return Inquiry::query()->create([
            'type' => 'candidate',
            'data' => [
                'first_name' => 'Nanuka',
                'last_name' => 'Thapa',
                'email' => 'nanuka@example.com',
                'phone' => '+919832581198',
                'city' => 'Gangtok',
                'resume' => 'inquiries/candidate/resume.pdf',
            ],
        ]);
    }

    public function test_recruitment_manager_sees_candidate_inquiries_with_documents(): void
    {
        $inquiry = $this->candidateInquiry();
        Inquiry::query()->create(['type' => 'recruiter', 'data' => ['company_name' => 'Acme', 'contact_name' => 'Jane']]);

        $this->actingAs(User::factory()->recruitmentManager()->create())
            ->get('/recruitment-manager/inquiries')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/recruitment-manager/inquiries/index')
                ->where('type', 'candidate')
                ->where('counts', ['candidate' => 1, 'recruiter' => 1])
                ->has('inquiries.data', 1)
                ->where('inquiries.data.0.name', 'Nanuka Thapa')
                ->where('inquiries.data.0.is_new', true)
                ->has('inquiries.data.0.documents', 1)
                ->where('inquiries.data.0.documents.0.label', 'Resume / CV'));

        $this->get("/recruitment-manager/inquiries/{$inquiry->id}/documents/resume")->assertOk();
    }

    public function test_recruiter_tab_lists_company_inquiries(): void
    {
        Inquiry::query()->create(['type' => 'recruiter', 'data' => ['company_name' => 'Acme', 'contact_name' => 'Jane', 'email' => 'j@acme.test']]);

        $this->actingAs(User::factory()->recruitmentManager()->create())
            ->get('/recruitment-manager/inquiries?type=recruiter')
            ->assertInertia(fn (Assert $page) => $page
                ->where('type', 'recruiter')
                ->where('inquiries.data.0.name', 'Acme')
                ->where('inquiries.data.0.subtitle', 'Jane')
                ->where('inquiries.data.0.documents', []));
    }

    public function test_other_roles_cannot_see_inquiries_or_documents(): void
    {
        $inquiry = $this->candidateInquiry();

        foreach ([User::factory()->student()->paid()->create(), User::factory()->recruiter()->create(), User::factory()->teacher()->create()] as $user) {
            $this->actingAs($user)->get('/recruitment-manager/inquiries')->assertForbidden();
            $this->actingAs($user)->get("/recruitment-manager/inquiries/{$inquiry->id}/documents/resume")->assertForbidden();
        }

        auth()->logout();
        $this->get('/recruitment-manager/inquiries')->assertRedirect();
    }

    public function test_dashboard_shows_recent_inquiry_count(): void
    {
        $this->candidateInquiry();
        $old = Inquiry::query()->create(['type' => 'recruiter', 'data' => []]);
        $old->forceFill(['created_at' => now()->subDays(30)])->save();

        $this->actingAs(User::factory()->recruitmentManager()->create())
            ->get('/recruitment-manager/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('newInquiries', 1));
    }
}
