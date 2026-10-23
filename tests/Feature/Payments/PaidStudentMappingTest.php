<?php

namespace Tests\Feature\Payments;

use App\Enums\OfflinePaymentMethod;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationFeePaidVia;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\RegistrationActivated;
use App\Services\OfflinePaymentService;
use App\Services\PaidStudentMappingService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PaidStudentMappingTest extends TestCase
{
    use RefreshDatabase;

    private PaidStudentMappingService $service;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PaidStudentMappingService::class);
        $this->staff = User::factory()->salesTeam()->create();
    }

    private function unpaidStudent(array $attributes = []): User
    {
        return User::factory()->student()->unpaid()->create($attributes);
    }

    private function map(User $student, int $months = 24, ?CarbonImmutable $accessUntil = null, OfflinePaymentMethod $method = OfflinePaymentMethod::Cash): Payment
    {
        return $this->service->map($student, null, $months, 12000, CarbonImmutable::now()->subMonths(8), $method, 'OLD-9', $accessUntil, $this->staff);
    }

    public function test_mapping_records_the_historical_payment_and_unlocks_the_student_silently(): void
    {
        Notification::fake();
        $student = $this->unpaidStudent(['state_code' => 'SK', 'special_id' => 'COI', 'registration_year' => 2024, 'student_number' => null]);
        $plan = Plan::query()->where('duration_months', 24)->first();
        $paidOn = CarbonImmutable::now()->subMonths(8);

        $payment = $this->service->map($student, $plan, 24, 12000, $paidOn, OfflinePaymentMethod::Upi, 'UTR55', null, $this->staff);

        $this->assertSame(PaymentChannel::Legacy, $payment->channel);
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(1200000, $payment->amount);
        $this->assertSame('upi', $payment->payment_mode);
        $this->assertSame('UTR55', $payment->payment_mode_detail);
        $this->assertSame($plan?->id, $payment->plan_id);
        $this->assertSame($this->staff->id, $payment->collected_by_user_id);
        $this->assertSame($paidOn->toDateString(), $payment->paid_at->toDateString());
        $this->assertSame('BV/REG-OLD/'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT), $payment->invoice_number);
        $this->assertTrue($payment->isLegacy());
        $this->assertTrue($payment->isRecordedByStaff());

        $fresh = $student->fresh();
        $this->assertTrue($fresh->hasActiveRegistration());
        $this->assertTrue($fresh->canAccessDashboard());
        $this->assertSame(RegistrationFeePaidVia::Mapped, $fresh->registration_fee_paid_via);
        $this->assertSame(now()->addMonths(24)->toDateString(), $fresh->registration_expires_at->toDateString());
        $this->assertNotNull($fresh->dashboard_activated_at);
        $this->assertNotNull($fresh->student_number);
        $this->assertNotNull($fresh->studentProfile);
        Notification::assertNothingSent();
        Notification::assertNotSentTo($student, RegistrationActivated::class);
    }

    public function test_access_length_and_an_explicit_end_date_are_honoured(): void
    {
        $short = $this->unpaidStudent();
        $this->map($short, 12);
        $this->assertSame(now()->addMonths(12)->toDateString(), $short->fresh()->registration_expires_at->toDateString());

        $fixed = $this->unpaidStudent();
        $until = CarbonImmutable::now()->addMonths(5)->endOfDay();
        $this->map($fixed, 24, $until);
        $this->assertSame($until->toDateString(), $fixed->fresh()->registration_expires_at->toDateString());
    }

    public function test_mapping_is_refused_for_the_wrong_people_and_bad_values(): void
    {
        $student = $this->unpaidStudent();
        $now = CarbonImmutable::now();
        $cases = [
            'not a student' => fn () => $this->service->map(User::factory()->recruiter()->create(), null, 24, 100, $now, OfflinePaymentMethod::Cash, null, null, $this->staff),
            'already active' => fn () => $this->service->map(User::factory()->student()->paid()->create(), null, 24, 100, $now, OfflinePaymentMethod::Cash, null, null, $this->staff),
            'zero amount' => fn () => $this->service->map($student, null, 24, 0, $now, OfflinePaymentMethod::Cash, null, null, $this->staff),
            'future paid date' => fn () => $this->service->map($student, null, 24, 100, $now->addDay(), OfflinePaymentMethod::Cash, null, null, $this->staff),
            'bad length' => fn () => $this->service->map($student, null, 0, 100, $now, OfflinePaymentMethod::Cash, null, null, $this->staff),
            'end date in the past' => fn () => $this->service->map($student, null, 24, 100, $now, OfflinePaymentMethod::Cash, null, $now->subDay(), $this->staff),
        ];

        foreach ($cases as $label => $attempt) {
            try {
                $attempt();
                $this->fail("{$label} should have been refused");
            } catch (DomainException) {
                $this->assertFalse($student->fresh()->hasActiveRegistration(), $label);
            }
        }

        $this->assertSame(0, Payment::query()->where('channel', PaymentChannel::Legacy)->count());
    }

    public function test_voiding_a_mapped_payment_takes_the_access_back(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->unpaidStudent();
        $payment = $this->map($student);

        app(OfflinePaymentService::class)->void($payment, 'Mapped the wrong student', $admin);

        $fresh = $student->fresh();
        $this->assertSame(PaymentStatus::Voided, $payment->fresh()->status);
        $this->assertFalse($fresh->hasActiveRegistration());
        $this->assertNull($fresh->registration_fee_paid_via);
        $this->assertNull($fresh->registration_expires_at);
        $this->assertSame(0, Payment::query()->collected()->count());
    }

    public function test_voiding_does_not_touch_access_that_came_from_somewhere_else(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->unpaidStudent();
        $payment = $this->map($student);
        $student->forceFill(['registration_fee_paid_via' => RegistrationFeePaidVia::Cashfree])->save();

        app(OfflinePaymentService::class)->void($payment, 'Duplicate', $admin);

        $this->assertTrue($student->fresh()->hasActiveRegistration());
    }

    public function test_offline_recording_refuses_the_mapping_only_methods(): void
    {
        foreach ([OfflinePaymentMethod::Online, OfflinePaymentMethod::Unknown] as $method) {
            $this->expectException(DomainException::class);
            app(OfflinePaymentService::class)->record($this->unpaidStudent(), null, 100, $method, CarbonImmutable::now(), null, null, $this->staff);
        }
    }

    public function test_the_per_student_action_works_for_admin_and_sales_and_hides_for_active_students(): void
    {
        $student = $this->unpaidStudent();
        $active = User::factory()->student()->paid()->create();

        foreach ([User::factory()->admin()->create(), $this->staff] as $staff) {
            Livewire::actingAs($staff)->test(ListCandidates::class)
                ->assertTableActionHidden('markAsAlreadyPaid', $active)
                ->assertTableActionVisible('markAsAlreadyPaid', $student);
        }

        Livewire::actingAs($this->staff)->test(ListCandidates::class)
            ->callTableAction('markAsAlreadyPaid', $student, [
                'amount' => 9000,
                'paid_on' => now()->subMonths(3)->toDateString(),
                'method' => 'cheque',
                'reference' => 'CHQ-12',
                'duration_months' => 12,
            ])
            ->assertHasNoTableActionErrors();

        $fresh = $student->fresh();
        $this->assertTrue($fresh->hasActiveRegistration());
        $this->assertDatabaseHas('payments', ['user_id' => $student->id, 'channel' => 'legacy', 'payment_mode' => 'cheque', 'amount' => 900000, 'collected_by_user_id' => $this->staff->id]);

        Livewire::actingAs($this->staff)->test(EditCandidate::class, ['record' => $fresh->getKey()])
            ->assertActionHidden('markAsAlreadyPaid');
    }

    public function test_the_action_shows_on_the_candidate_page_and_validates_input(): void
    {
        $student = $this->unpaidStudent();

        Livewire::actingAs($this->staff)->test(EditCandidate::class, ['record' => $student->getKey()])
            ->assertActionVisible('markAsAlreadyPaid')
            ->callAction('markAsAlreadyPaid', ['amount' => 0, 'paid_on' => now()->toDateString(), 'method' => 'cash', 'duration_months' => 24])
            ->assertHasActionErrors(['amount']);

        $this->assertFalse($student->fresh()->hasActiveRegistration());
    }

    public function test_the_payments_screen_has_an_earlier_tab_and_total(): void
    {
        $mapped = $this->map($this->unpaidStudent());
        $offline = app(OfflinePaymentService::class)->record($this->unpaidStudent(), null, 500, OfflinePaymentMethod::Cash, CarbonImmutable::now(), null, null, $this->staff);

        Livewire::actingAs($this->staff)->test(ListPayments::class)
            ->set('activeTab', 'earlier')
            ->assertCanSeeTableRecords([$mapped])
            ->assertCanNotSeeTableRecords([$offline])
            ->set('activeTab', 'offline')
            ->assertCanSeeTableRecords([$offline])
            ->assertCanNotSeeTableRecords([$mapped]);

        Livewire::actingAs(User::factory()->admin()->create())->test(ListPayments::class)
            ->assertTableActionVisible('void', $mapped);
    }

    public function test_the_slip_for_a_mapped_payment_downloads(): void
    {
        $payment = $this->map($this->unpaidStudent(), method: OfflinePaymentMethod::BankTransfer);

        $this->actingAs($this->staff)
            ->get(route('filament.bv-admin.payments.slip.download', $payment))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
