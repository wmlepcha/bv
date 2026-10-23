<?php

namespace Tests\Feature\Payments;

use App\Enums\OfflinePaymentMethod;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\Candidates\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Center;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\RegistrationActivated;
use App\Services\OfflinePaymentService;
use Carbon\CarbonImmutable;
use DomainException;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class OfflinePaymentTest extends TestCase
{
    use RefreshDatabase;

    private OfflinePaymentService $service;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OfflinePaymentService::class);
        $this->student = User::factory()->student()->unpaid()->create(['name' => 'Dolma Lepcha']);
    }

    private function online(User $student, int $rupees = 1000): Payment
    {
        return Payment::create([
            'user_id' => $student->id,
            'gateway_order_id' => 'order_'.uniqid(),
            'amount' => $rupees * 100,
            'currency' => 'INR',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    private function record(?User $student = null, int $rupees = 5000, ?User $collector = null, OfflinePaymentMethod $method = OfflinePaymentMethod::Cash): Payment
    {
        return $this->service->record($student ?? $this->student, null, $rupees, $method, CarbonImmutable::now(), 'RCPT-77', 'Paid at the Gangtok centre', $collector ?? User::factory()->admin()->create());
    }

    public function test_recording_creates_a_collected_offline_payment_with_an_invoice_number(): void
    {
        $collector = User::factory()->salesTeam()->create();
        $plan = Plan::query()->firstOrFail();

        $payment = $this->service->record($this->student, $plan, 4500, OfflinePaymentMethod::Upi, CarbonImmutable::now()->subDay(), 'UTR123', 'Discount given', $collector);

        $this->assertTrue($payment->isOffline());
        $this->assertSame(PaymentChannel::Offline, $payment->channel);
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(450000, $payment->amount);
        $this->assertSame('upi', $payment->payment_mode);
        $this->assertSame(OfflinePaymentMethod::Upi, $payment->offlineMethod());
        $this->assertSame('UTR123', $payment->payment_mode_detail);
        $this->assertSame($plan->id, $payment->plan_id);
        $this->assertSame($collector->id, $payment->collected_by_user_id);
        $this->assertSame('BV/REG-OFF/'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT), $payment->invoice_number);
        $this->assertStringStartsWith('OFFLINE-', $payment->gateway_order_id);
        $this->assertTrue($payment->paid_at->isYesterday());
        $this->assertSame(1, Payment::query()->collected()->count());
    }

    public function test_recording_is_ledger_only_and_never_unlocks_the_student(): void
    {
        Notification::fake();

        $this->record();

        $fresh = $this->student->fresh();
        $this->assertNull($fresh->registration_expires_at);
        $this->assertNull($fresh->registration_paid_at);
        $this->assertFalse($fresh->canAccessDashboard());
        Notification::assertNothingSent();
        Notification::assertNotSentTo($this->student, RegistrationActivated::class);
    }

    public function test_bad_input_is_refused(): void
    {
        $collector = User::factory()->admin()->create();
        $cases = [
            'not a student' => fn () => $this->service->record(User::factory()->recruiter()->create(), null, 100, OfflinePaymentMethod::Cash, CarbonImmutable::now(), null, null, $collector),
            'zero amount' => fn () => $this->service->record($this->student, null, 0, OfflinePaymentMethod::Cash, CarbonImmutable::now(), null, null, $collector),
            'negative amount' => fn () => $this->service->record($this->student, null, -5, OfflinePaymentMethod::Cash, CarbonImmutable::now(), null, null, $collector),
            'future date' => fn () => $this->service->record($this->student, null, 100, OfflinePaymentMethod::Cash, CarbonImmutable::now()->addDay(), null, null, $collector),
        ];

        foreach ($cases as $label => $attempt) {
            try {
                $attempt();
                $this->fail("{$label} should have been refused");
            } catch (DomainException) {
                $this->assertSame(0, Payment::query()->count(), $label);
            }
        }
    }

    public function test_void_keeps_the_record_removes_it_from_totals_and_only_works_once_on_offline_payments(): void
    {
        $admin = User::factory()->admin()->create();
        $offline = $this->record(collector: $admin);
        $online = $this->online($this->student);

        $this->service->void($offline, 'Wrong student', $admin);

        $offline->refresh();
        $this->assertSame(PaymentStatus::Voided, $offline->status);
        $this->assertSame('Wrong student', $offline->void_reason);
        $this->assertSame($admin->id, $offline->voided_by_user_id);
        $this->assertNotNull($offline->voided_at);
        $this->assertModelExists($offline);
        $this->assertSame([$online->id], Payment::query()->collected()->pluck('id')->all());

        $untouched = $this->record();

        foreach ([[$offline, 'again'], [$online, 'gateway'], [$untouched, '']] as [$payment, $reason]) {
            try {
                $this->service->void($payment, $reason, $admin);
                $this->fail('Void should have been refused');
            } catch (DomainException) {
                $this->assertTrue(true);
            }
        }

        $this->assertSame(PaymentStatus::Paid, $untouched->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $online->fresh()->status);
    }

    public function test_admin_and_sales_team_record_from_the_payments_screen_but_others_cannot(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->salesTeam()->create()] as $staff) {
            Livewire::actingAs($staff)->test(ListPayments::class)
                ->callAction('recordOfflinePayment', [
                    'student_id' => $this->student->id,
                    'amount' => 3000,
                    'method' => 'cash',
                    'received_on' => now()->toDateString(),
                    'reference' => 'R-'.$staff->id,
                ])
                ->assertHasNoActionErrors();

            $this->assertDatabaseHas('payments', ['collected_by_user_id' => $staff->id, 'channel' => 'offline', 'amount' => 300000, 'payment_mode_detail' => 'R-'.$staff->id]);
        }

        foreach ([User::factory()->recruiter()->create(), User::factory()->recruitmentManager()->create(), User::factory()->student()->create()] as $other) {
            $this->actingAs($other)->get('/bv-admin/payments/payments')->assertForbidden();
        }
    }

    public function test_the_form_validates_amount_date_and_student(): void
    {
        $admin = User::factory()->admin()->create();
        $base = ['student_id' => $this->student->id, 'amount' => 100, 'method' => 'cash', 'received_on' => now()->toDateString()];

        Livewire::actingAs($admin)->test(ListPayments::class)
            ->callAction('recordOfflinePayment', [...$base, 'amount' => 0])
            ->assertHasActionErrors(['amount']);

        Livewire::actingAs($admin)->test(ListPayments::class)
            ->callAction('recordOfflinePayment', [...$base, 'received_on' => now()->addDays(3)->toDateString()])
            ->assertHasActionErrors(['received_on']);

        Livewire::actingAs($admin)->test(ListPayments::class)
            ->callAction('recordOfflinePayment', [...$base, 'student_id' => null])
            ->assertHasActionErrors(['student_id']);

        $this->assertSame(0, Payment::query()->count());
    }

    public function test_the_table_keeps_the_everyday_columns_and_hides_the_rest_until_asked(): void
    {
        $this->record();

        Livewire::actingAs(User::factory()->admin()->create())->test(ListPayments::class)
            ->assertCanRenderTableColumn('user.name')
            ->assertCanRenderTableColumn('payment_mode')
            ->assertCanRenderTableColumn('amount')
            ->assertCanRenderTableColumn('status')
            ->assertCanRenderTableColumn('center_id')
            ->assertCanNotRenderTableColumn('collector.name')
            ->assertCanNotRenderTableColumn('channel')
            ->assertCanNotRenderTableColumn('reference')
            ->assertCanNotRenderTableColumn('paid_at');
    }

    public function test_tabs_split_online_offline_and_voided(): void
    {
        $admin = User::factory()->admin()->create();
        $online = $this->online($this->student);
        $offline = $this->record(collector: $admin);
        $voided = $this->record(collector: $admin);
        $this->service->void($voided, 'Typo', $admin);

        Livewire::actingAs($admin)->test(ListPayments::class)
            ->assertCanSeeTableRecords([$online, $offline, $voided])
            ->set('activeTab', 'online')->assertCanSeeTableRecords([$online])->assertCanNotSeeTableRecords([$offline, $voided])
            ->set('activeTab', 'offline')->assertCanSeeTableRecords([$offline])->assertCanNotSeeTableRecords([$online, $voided])
            ->set('activeTab', 'voided')->assertCanSeeTableRecords([$voided])->assertCanNotSeeTableRecords([$online, $offline]);
    }

    public function test_filters_and_student_search_work(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->student()->create(['name' => 'Karma Bhutia']);
        $cash = $this->record(collector: $admin);
        $cheque = $this->record($other, 800, $admin, OfflinePaymentMethod::Cheque);
        $web = $this->online($this->student);

        Livewire::actingAs($admin)->test(ListPayments::class)
            ->filterTable('channel', 'offline')->assertCanSeeTableRecords([$cash, $cheque])->assertCanNotSeeTableRecords([$web])
            ->filterTable('payment_mode', 'cheque')->assertCanSeeTableRecords([$cheque])->assertCanNotSeeTableRecords([$cash])
            ->removeTableFilters()
            ->searchTable('Karma')->assertCanSeeTableRecords([$cheque])->assertCanNotSeeTableRecords([$cash, $web])
            ->searchTable('RCPT-77')->assertCanSeeTableRecords([$cash, $cheque]);
    }

    public function test_payments_group_by_student(): void
    {
        $admin = User::factory()->admin()->create();
        $this->record(collector: $admin);
        $this->online($this->student);

        Livewire::actingAs($admin)->test(ListPayments::class)
            ->set('tableGrouping', 'user_id')
            ->assertSuccessful()
            ->assertSee('Dolma Lepcha');
    }

    public function test_only_admins_can_void_and_sales_can_not_delete(): void
    {
        $payment = $this->record();

        Livewire::actingAs(User::factory()->salesTeam()->create())->test(ListPayments::class)
            ->assertTableActionHidden('void', $payment)
            ->assertTableBulkActionHidden('delete');

        Livewire::actingAs(User::factory()->admin()->create())->test(ListPayments::class)
            ->assertTableActionVisible('void', $payment)
            ->callTableAction('void', $payment, ['reason' => 'Duplicate entry']);

        $this->assertSame(PaymentStatus::Voided, $payment->fresh()->status);
    }

    public function test_online_payments_cannot_be_voided_from_the_table(): void
    {
        $online = $this->online($this->student);

        Livewire::actingAs(User::factory()->admin()->create())->test(ListPayments::class)
            ->assertTableActionHidden('void', $online);
    }

    public function test_the_candidate_page_lists_every_payment_and_can_record_for_that_student(): void
    {
        $admin = User::factory()->admin()->create();
        $web = $this->online($this->student, 1200);
        $cash = $this->record(collector: $admin);
        $someoneElse = $this->online(User::factory()->student()->create());

        Livewire::actingAs($admin)->test(PaymentsRelationManager::class, ['ownerRecord' => $this->student, 'pageClass' => EditCandidate::class])
            ->assertCanSeeTableRecords([$web, $cash])
            ->assertCanNotSeeTableRecords([$someoneElse])
            ->callAction(TestAction::make('recordOfflinePayment')->table(), [
                'amount' => 700,
                'method' => 'bank_transfer',
                'received_on' => now()->toDateString(),
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('payments', ['user_id' => $this->student->id, 'amount' => 70000, 'payment_mode' => 'bank_transfer']);
    }

    public function test_offline_payment_slips_download_with_the_method_instead_of_gateway_ids(): void
    {
        $payment = $this->record(method: OfflinePaymentMethod::Cheque);

        $response = $this->actingAs(User::factory()->salesTeam()->create())
            ->get(route('filament.bv-admin.payments.slip.download', $payment));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('BV-REG-OFF-', (string) $response->headers->get('Content-Disposition'));

        $this->service->void($payment, 'Mistake', User::factory()->admin()->create());
        $this->get(route('filament.bv-admin.payments.slip.download', $payment))->assertNotFound();
    }

    public function test_collected_by_is_a_center_that_staff_set_by_hand_and_is_blank_by_default(): void
    {
        $gangtok = Center::factory()->create(['name' => 'Gangtok']);
        $namchi = Center::factory()->create(['name' => 'Namchi']);
        $web = $this->online($this->student);
        $cash = $this->record();

        $this->assertNull($web->fresh()->center_id);
        $this->assertNull($cash->fresh()->center_id);

        $table = Livewire::actingAs($this->staffMember())->test(ListPayments::class);
        $table->call('updateTableColumnState', 'center_id', (string) $web->getKey(), (string) $gangtok->id);
        $table->call('updateTableColumnState', 'center_id', (string) $cash->getKey(), (string) $namchi->id);

        $this->assertSame($gangtok->id, $web->fresh()->center_id);
        $this->assertSame($namchi->id, $cash->fresh()->center_id);
        $this->assertSame('Gangtok', $web->fresh()->center->name);

        $table->call('updateTableColumnState', 'center_id', (string) $web->getKey(), '');
        $this->assertNull($web->fresh()->center_id);
    }

    public function test_a_center_can_be_picked_while_recording_or_mapping_and_stays_blank_otherwise(): void
    {
        $center = Center::factory()->create(['name' => 'Gangtok']);
        $staff = $this->staffMember();
        $base = ['student_id' => $this->student->id, 'amount' => 700, 'method' => 'cash', 'received_on' => now()->toDateString()];

        Livewire::actingAs($staff)->test(ListPayments::class)->callAction('recordOfflinePayment', $base);
        Livewire::actingAs($staff)->test(ListPayments::class)->callAction('recordOfflinePayment', [...$base, 'center_id' => $center->id]);

        $this->assertSame([null, $center->id], Payment::query()->where('channel', 'offline')->where('amount', 70000)->orderBy('id')->pluck('center_id')->all());
        $this->assertSame($staff->id, Payment::query()->latest('id')->first()->collected_by_user_id);

        $old = User::factory()->student()->unpaid()->create();
        Livewire::actingAs($staff)->test(ListCandidates::class)
            ->callTableAction('markAsAlreadyPaid', $old, ['amount' => 900, 'paid_on' => now()->subMonth()->toDateString(), 'method' => 'cash', 'duration_months' => 24, 'center_id' => $center->id]);

        $this->assertSame($center->id, Payment::query()->where('user_id', $old->id)->value('center_id'));
    }

    public function test_staff_can_change_the_method_of_any_payment_and_website_rows_become_offline_but_voided_ones_stay_locked(): void
    {
        $offline = $this->record();
        $mapped = $this->record();
        $mapped->update(['channel' => PaymentChannel::Legacy]);
        $web = $this->online($this->student);
        $voided = $this->record();
        $this->service->void($voided, 'Typo', User::factory()->admin()->create());

        $table = Livewire::actingAs($this->staffMember())->test(ListPayments::class);
        $table->call('updateTableColumnState', 'payment_mode', (string) $offline->getKey(), 'upi');
        $table->call('updateTableColumnState', 'payment_mode', (string) $mapped->getKey(), 'cash');
        $table->call('updateTableColumnState', 'payment_mode', (string) $web->getKey(), 'cash');
        $table->call('updateTableColumnState', 'payment_mode', (string) $voided->getKey(), 'upi');

        $this->assertSame('upi', $offline->fresh()->payment_mode);
        $this->assertSame('cash', $mapped->fresh()->payment_mode);
        $this->assertSame('cash', $web->fresh()->payment_mode);
        $this->assertSame(PaymentChannel::Offline, $web->fresh()->channel);
        $this->assertNotSame('upi', $voided->fresh()->payment_mode);
    }

    public function test_the_center_cannot_be_changed_on_voided_payments_or_by_other_roles(): void
    {
        $center = Center::factory()->create();
        $cash = $this->record();
        $voided = $this->record();
        $this->service->void($voided, 'Typo', User::factory()->admin()->create());

        $table = Livewire::actingAs($this->staffMember())->test(ListPayments::class);
        $table->call('updateTableColumnState', 'center_id', (string) $voided->getKey(), (string) $center->id);
        $this->assertNull($voided->fresh()->center_id);

        foreach ([User::factory()->recruiter()->create(), User::factory()->recruitmentManager()->create(), User::factory()->student()->create()] as $other) {
            $this->actingAs($other)->get('/bv-admin/payments/payments')->assertForbidden();
        }

        $this->assertNull($cash->fresh()->center_id);
    }

    public function test_payments_can_be_filtered_and_grouped_by_center(): void
    {
        $gangtok = Center::factory()->create(['name' => 'Gangtok']);
        $namchi = Center::factory()->create(['name' => 'Namchi']);
        $a = $this->record();
        $b = $this->record();
        $c = $this->online($this->student);
        $a->update(['center_id' => $gangtok->id]);
        $b->update(['center_id' => $namchi->id]);

        Livewire::actingAs($this->staffMember())->test(ListPayments::class)
            ->filterTable('center_id', $gangtok->id)->assertCanSeeTableRecords([$a])->assertCanNotSeeTableRecords([$b, $c])
            ->removeTableFilters()
            ->filterTable('no_center', true)->assertCanSeeTableRecords([$c])->assertCanNotSeeTableRecords([$a, $b])
            ->removeTableFilters()
            ->set('tableGrouping', 'center_id')
            ->assertSuccessful()
            ->assertSee('Gangtok')
            ->assertSee('Not set');
    }

    private function staffMember(): User
    {
        return User::factory()->salesTeam()->create();
    }
}
