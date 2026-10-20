<?php

namespace Tests\Feature\Filament;

use App\Enums\PaymentStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AttendanceByBatchChart;
use App\Filament\Widgets\CenterCollections;
use App\Filament\Widgets\CollectedOverTimeChart;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\PlacementPieChart;
use App\Filament\Widgets\RecentPaymentsTable;
use App\Filament\Widgets\RegistrationPurposeChart;
use App\Filament\Widgets\RevenueByCenterChart;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Batch;
use App\Models\BatchEnrollment;
use App\Models\Center;
use App\Models\CourseFeedback;
use App\Models\Payment;
use App\Models\StudentProfile;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('bv-admin');
    }

    private function pay(int $rupees, ?Center $center = null, PaymentStatus $status = PaymentStatus::Paid, ?string $paidAt = null): Payment
    {
        return Payment::query()->create([
            'user_id' => User::factory()->student()->paid()->create()->id,
            'amount' => $rupees * 100,
            'currency' => 'INR',
            'gateway_order_id' => 'order_'.fake()->unique()->uuid(),
            'status' => $status,
            'paid_at' => $paidAt ?? now(),
            'center_id' => $center?->id,
        ]);
    }

    /**
     * @return array{datasets: list<array{data: list<mixed>}>, labels: list<string>}
     */
    private function chartData(object $chart): array
    {
        return (fn (): array => $this->getData())->call($chart);
    }

    public function test_admin_and_sales_team_open_the_dashboard_without_the_welcome_widget(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->salesTeam()->create()] as $user) {
            $this->actingAs($user)->get('/bv-admin')->assertOk()->assertSee('This month')->assertDontSee('Welcome');
        }
    }

    public function test_dashboard_is_closed_to_non_staff(): void
    {
        $this->actingAs(User::factory()->student()->paid()->create())->get('/bv-admin')->assertForbidden();
    }

    public function test_revenue_by_center_groups_paid_payments_and_buckets_missing_centers(): void
    {
        $coimbatore = Center::factory()->create(['name' => 'Coimbatore']);
        $this->pay(1000, $coimbatore);
        $this->pay(500, $coimbatore);
        $this->pay(300);
        $this->pay(9999, $coimbatore, PaymentStatus::Voided);
        $this->pay(9999, $coimbatore, PaymentStatus::Pending);

        $data = $this->chartData(Livewire::actingAs(User::factory()->admin()->create())->test(RevenueByCenterChart::class)->instance());

        $this->assertSame(['Coimbatore', 'Unassigned'], $data['labels']);
        $this->assertEquals([1500, 300], $data['datasets'][0]['data']);
    }

    public function test_date_range_filter_narrows_payments(): void
    {
        $this->pay(100, null, PaymentStatus::Paid, now()->subYears(2)->toDateTimeString());
        $this->pay(200);

        $month = $this->chartData(Livewire::actingAs(User::factory()->admin()->create())
            ->test(RevenueByCenterChart::class, ['pageFilters' => ['range' => 'month']])->instance());
        $all = $this->chartData(Livewire::test(RevenueByCenterChart::class, ['pageFilters' => ['range' => 'all']])->instance());

        $this->assertEquals([200], $month['datasets'][0]['data']);
        $this->assertEquals([300], $all['datasets'][0]['data']);
    }

    public function test_paid_payments_without_paid_at_do_not_break_all_time_charts(): void
    {
        $payment = $this->pay(400);
        $payment->forceFill(['paid_at' => null])->save();

        $admin = User::factory()->admin()->create();
        $time = $this->chartData(Livewire::actingAs($admin)->test(CollectedOverTimeChart::class, ['pageFilters' => ['range' => 'all']])->instance());
        $center = $this->chartData(Livewire::test(RevenueByCenterChart::class, ['pageFilters' => ['range' => 'all']])->instance());

        $this->assertEquals([400], $time['datasets'][0]['data']);
        $this->assertEquals([400], $center['datasets'][0]['data']);
    }

    public function test_collected_over_time_sums_per_day(): void
    {
        $this->pay(100);
        $this->pay(250);

        $data = $this->chartData(Livewire::actingAs(User::factory()->admin()->create())->test(CollectedOverTimeChart::class)->instance());

        $this->assertEquals([350], $data['datasets'][0]['data']);
    }

    public function test_registration_purpose_chart_counts_each_purpose(): void
    {
        StudentProfile::factory()->count(2)->create();
        StudentProfile::factory()->forPlacement()->create();

        $data = $this->chartData(Livewire::actingAs(User::factory()->admin()->create())->test(RegistrationPurposeChart::class)->instance());

        $this->assertSame([2, 1], $data['datasets'][0]['data']);
    }

    public function test_stats_render_revenue_and_average_rating(): void
    {
        $this->pay(1500);
        CourseFeedback::factory()->create(array_fill_keys(array_keys(CourseFeedback::RATING_QUESTIONS), 4));
        StudentProfile::factory()->forPlacement()->hired()->create();

        Livewire::actingAs(User::factory()->admin()->create())->test(DashboardStats::class)
            ->assertSee('₹1,500')
            ->assertSee('4.0 / 5')
            ->assertSee('Online')
            ->assertSee('Placed')
            ->assertSee('Unplaced');
    }

    public function test_attendance_by_batch_is_present_over_sessions_times_enrolled(): void
    {
        $batch = Batch::factory()->create(['name' => 'A1 Morning']);
        $students = User::factory()->student()->paid()->count(2)->create();
        $students->each(fn (User $student) => BatchEnrollment::factory()->create(['batch_id' => $batch->id, 'student_id' => $student->id]));
        $session = AttendanceSession::factory()->create(['batch_id' => $batch->id]);
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id, 'student_id' => $students[0]->id,
            'status' => 'present', 'method' => 'qr', 'marked_at' => now(),
        ]);

        $data = $this->chartData(Livewire::actingAs(User::factory()->admin()->create())->test(AttendanceByBatchChart::class)->instance());

        $this->assertSame(['A1 Morning'], $data['labels']);
        $this->assertEquals([50], $data['datasets'][0]['data']);
    }

    public function test_center_cards_show_each_center_even_without_payments(): void
    {
        $this->pay(1200, Center::factory()->create(['name' => 'Siliguri']));
        Center::factory()->create(['name' => 'Empty Center']);
        $this->pay(80);

        Livewire::actingAs(User::factory()->admin()->create())->test(CenterCollections::class)
            ->assertSee('Siliguri')->assertSee('₹1,200.00')->assertSee('Empty Center')->assertSee('Unassigned');
    }

    public function test_placement_pie_shows_counts_for_placed_and_unplaced(): void
    {
        StudentProfile::factory()->forPlacement()->hired()->create();
        StudentProfile::factory()->forPlacement()->count(3)->create();
        StudentProfile::factory()->create();

        $data = $this->chartData(Livewire::actingAs(User::factory()->admin()->create())->test(PlacementPieChart::class)->instance());

        $this->assertSame([1, 3], $data['datasets'][0]['data']);
        $this->assertSame(['Placed · 1 (25%)', 'Unplaced · 3 (75%)'], $data['labels']);
    }

    public function test_stats_split_collected_by_channel_and_students_by_purpose(): void
    {
        $this->pay(1000);
        $this->pay(500)->forceFill(['channel' => 'offline'])->save();
        StudentProfile::factory()->forPlacement()->create();

        Livewire::actingAs(User::factory()->admin()->create())->test(DashboardStats::class)
            ->assertSee('Online ₹1,000.00')
            ->assertSee('Offline ₹500.00')
            ->assertSee('Training 0 · Training + Placement 1');
    }

    public function test_recent_payments_table_lists_latest_payments_with_their_center(): void
    {
        $this->pay(750, Center::factory()->create(['name' => 'Gangtok']));

        Livewire::actingAs(User::factory()->admin()->create())->test(RecentPaymentsTable::class)
            ->assertSee('Gangtok')
            ->assertSee('₹750.00');
    }

    public function test_dashboard_greets_the_user_by_first_name(): void
    {
        $this->actingAs(User::factory()->admin()->create(['name' => 'Asha Rai']))->get('/bv-admin')->assertSee('Asha');
    }

    public function test_period_dropdown_lives_in_the_header_and_switches_the_range(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())->test(Dashboard::class)
            ->assertSee('This month')
            ->assertActionExists('period_all')
            ->callAction('period_all')
            ->assertSet('filters.range', 'all')
            ->assertSee('All time');
    }

    public function test_dashboard_page_registers_the_widgets(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())->test(Dashboard::class)->assertOk();
    }
}
