<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Referrals\Pages\ListReferrals;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReferralResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_referrals(): void
    {
        $admin = User::factory()->admin()->create();
        $referrals = Referral::factory()->count(3)->create();

        Livewire::actingAs($admin)
            ->test(ListReferrals::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($referrals);
    }

    public function test_admin_can_view_referral(): void
    {
        $admin = User::factory()->admin()->create();
        $referral = Referral::factory()->create(['referred_name' => 'Pema Lepcha']);

        Livewire::actingAs($admin)
            ->test(ListReferrals::class)
            ->mountTableAction('view', $referral)
            ->assertMountedActionModalSee('Pema Lepcha');
    }

    public function test_admin_can_delete_referral(): void
    {
        $admin = User::factory()->admin()->create();
        $referral = Referral::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListReferrals::class)
            ->callTableAction('delete', $referral);

        $this->assertModelMissing($referral);
    }

    public function test_non_admin_cannot_access_referrals(): void
    {
        $manager = User::factory()->trainingManager()->create();

        $this->actingAs($manager)
            ->get(route('filament.bv-admin.resources.referrals.index'))
            ->assertForbidden();
    }
}
