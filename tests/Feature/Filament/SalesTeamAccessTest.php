<?php

namespace Tests\Feature\Filament;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Clusters\CandidatesCluster;
use App\Filament\Clusters\PaymentsCluster;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ImportCandidates;
use App\Filament\Resources\ActivationCodes\ActivationCodeResource;
use App\Filament\Resources\Candidates\CandidateResource;
use App\Filament\Resources\Centers\CenterResource;
use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\SalesTeam\Pages\CreateSalesTeamMember;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\WelcomeWithCredentials;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class SalesTeamAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The only panel resources and pages the sales team may open. Anything else must be admin-only.
     */
    private const ALLOWED = [
        CenterResource::class,
        LeaveRequestResource::class,
        CandidateResource::class,
        ActivationCodeResource::class,
        PaymentResource::class,
        ImportCandidates::class,
        Dashboard::class,
        CandidatesCluster::class,
        PaymentsCluster::class,
    ];

    private function accessibleFor(User $user): array
    {
        $this->actingAs($user);
        Filament::setCurrentPanel('bv-admin');

        return collect([...Filament::getResources(), ...Filament::getPages(), ...Filament::getPanel('bv-admin')->getClusters()])
            ->unique()
            ->filter(fn (string $component): bool => $component::canAccess())
            ->values()
            ->all();
    }

    public function test_sales_team_sees_only_the_dashboard_centers_leave_requests_and_candidates(): void
    {
        $accessible = $this->accessibleFor(User::factory()->salesTeam()->create());

        $this->assertEqualsCanonicalizing(self::ALLOWED, $accessible, 'Every new Filament resource or page needs the AdminOnly trait unless the sales team should see it.');
    }

    public function test_admins_still_reach_everything(): void
    {
        $accessible = $this->accessibleFor(User::factory()->admin()->create());

        foreach ([...Filament::getResources(), ...Filament::getPages(), ...Filament::getPanel('bv-admin')->getClusters()] as $component) {
            $this->assertContains($component, $accessible, "{$component} should be open to admins");
        }
    }

    public function test_sales_team_can_open_their_pages_and_gets_403_everywhere_else(): void
    {
        $this->actingAs(User::factory()->salesTeam()->create());

        foreach (['/bv-admin', '/bv-admin/centers', '/bv-admin/leave-requests', '/bv-admin/candidates/candidates', '/bv-admin/candidates/activation-codes', '/bv-admin/candidates/activation-codes/create', '/bv-admin/candidates/import-candidates', '/bv-admin/payments/payments'] as $url) {
            $this->get($url)->assertOk();
        }

        foreach ([
            '/bv-admin/batches',
            '/bv-admin/admins',
            '/bv-admin/recruiters',
            '/bv-admin/sales-team',
            '/bv-admin/recruiter-projects',
            '/bv-admin/frontend-edits/header-settings',
            '/bv-admin/payments/payment-settings',
            '/bv-admin/projects-page-settings',
            '/bv-admin/inquiries',
        ] as $url) {
            $this->assertSame(403, $this->get($url)->getStatusCode(), "{$url} should be forbidden for the sales team");
        }
    }

    public function test_sales_team_navigation_only_lists_their_entries(): void
    {
        $this->actingAs(User::factory()->salesTeam()->create());

        $this->get('/bv-admin')
            ->assertOk()
            ->assertSee('Centers')
            ->assertSee('Leave requests')
            ->assertSee('Candidates')
            ->assertSee('Payments')
            ->assertDontSee('Batches')
            ->assertDontSee('Recruiters')
            ->assertDontSee('FrontendEdits');
    }

    public function test_only_admins_and_the_sales_team_can_enter_the_panel(): void
    {
        $panel = Filament::getPanel('bv-admin');

        $this->assertTrue(User::factory()->admin()->create()->canAccessPanel($panel));
        $this->assertTrue(User::factory()->salesTeam()->create()->canAccessPanel($panel));

        foreach ([User::factory()->recruiter()->create(), User::factory()->recruitmentManager()->create(), User::factory()->trainingManager()->create(), User::factory()->teacher()->create(), User::factory()->student()->create()] as $user) {
            $this->assertFalse($user->canAccessPanel($panel));
        }
    }

    public function test_creating_a_sales_team_member_sends_the_welcome_notification(): void
    {
        Notification::fake();

        Livewire::actingAs(User::factory()->admin()->create())->test(CreateSalesTeamMember::class)
            ->fillForm(['name' => 'Sam Sales', 'email' => 'sam@example.com', 'whatsapp_number' => '917872012752', 'password' => 'Str0ng-Passw0rd!'])
            ->call('create')
            ->assertHasNoFormErrors();

        Notification::assertSentTo(
            User::query()->where('email', 'sam@example.com')->firstOrFail(),
            WelcomeWithCredentials::class,
        );
    }

    public function test_admins_create_sales_team_members_and_the_account_cannot_use_the_portals(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())->test(CreateSalesTeamMember::class)
            ->fillForm(['name' => 'Sam Sales', 'email' => 'sam@example.com', 'password' => 'Str0ng-Passw0rd!'])
            ->call('create')
            ->assertHasNoFormErrors();

        $member = User::query()->where('email', 'sam@example.com')->firstOrFail();
        $this->assertSame(UserRole::SalesTeam, $member->role);
        $this->assertNotNull($member->email_verified_at);

        auth()->logout();

        foreach (['student', 'staff', 'teacher'] as $portal) {
            $this->post(route('login.store'), ['email' => $member->email, 'password' => 'Str0ng-Passw0rd!', 'portal' => $portal])
                ->assertSessionHasErrors('email');
            $this->assertGuest();
        }

        $this->actingAs($member)->get(route('dashboard'))->assertRedirect('/bv-admin');
    }

    public function test_sales_team_can_view_payments_and_slips_but_not_change_or_delete_them(): void
    {
        $sales = User::factory()->salesTeam()->create();
        $this->actingAs($sales);
        Filament::setCurrentPanel('bv-admin');

        $this->assertTrue(PaymentResource::canViewAny());
        $this->assertTrue(PaymentResource::canCreate(), 'sales can record offline payments');
        $this->assertFalse(PaymentResource::canEdit(new Payment));
        $this->assertFalse(PaymentResource::canDelete(new Payment));
        $this->assertFalse(PaymentResource::canDeleteAny());

        $this->actingAs(User::factory()->admin()->create());
        $this->assertTrue(PaymentResource::canDeleteAny());
    }

    public function test_sales_team_can_download_payment_slips_but_other_roles_cannot(): void
    {
        $plan = Plan::query()->firstOrFail();
        $payment = Payment::create([
            'user_id' => User::factory()->student()->create()->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'duration_months' => $plan->duration_months,
            'gateway_order_id' => 'order_sales_slip',
            'amount' => $plan->priceInPaise(),
            'currency' => 'INR',
            'status' => PaymentStatus::Pending,
        ]);
        $url = route('filament.bv-admin.payments.slip.download', $payment);

        $this->actingAs(User::factory()->recruiter()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->recruitmentManager()->create())->get($url)->assertForbidden();

        $this->assertNotSame(403, $this->actingAs(User::factory()->salesTeam()->create())->get($url)->getStatusCode());
        $this->assertNotSame(403, $this->actingAs(User::factory()->admin()->create())->get($url)->getStatusCode());
    }
}
