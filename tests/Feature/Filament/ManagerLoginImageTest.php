<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ManageHeaderSettings;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerLoginImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_pages_have_no_image_by_default(): void
    {
        $this->get('/bv-rm-login')
            ->assertInertia(fn (Assert $page) => $page
                ->where('siteLayout.auth.recruitmentManagerImageUrl', null)
                ->where('siteLayout.auth.trainingManagerImageUrl', null));
    }

    public function test_each_login_uses_its_own_image_setting(): void
    {
        SiteSetting::setValue('auth.recruitment_manager_image', '/storage/images/rm.jpg');
        SiteSetting::setValue('auth.training_manager_image', 'images/tm.jpg');

        $this->get('/bv-rm-login')
            ->assertInertia(fn (Assert $page) => $page
                ->where('siteLayout.auth.recruitmentManagerImageUrl', '/storage/images/rm.jpg')
                ->where('siteLayout.auth.trainingManagerImageUrl', asset('storage/images/tm.jpg')));
    }

    public function test_admin_can_upload_and_clear_the_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageHeaderSettings::class)
            ->fillForm(['recruitment_manager_image' => UploadedFile::fake()->image('rm.jpg', 800, 600)])
            ->call('save')
            ->assertHasNoFormErrors();

        $saved = SiteSetting::getValue('auth.recruitment_manager_image');
        $this->assertStringStartsWith('/storage/images/', $saved);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $saved));
        $this->assertSame('', (string) SiteSetting::getValue('auth.training_manager_image'));

        Livewire::actingAs($admin)
            ->test(ManageHeaderSettings::class)
            ->fillForm(['recruitment_manager_image' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('', (string) SiteSetting::getValue('auth.recruitment_manager_image'));
    }
}
