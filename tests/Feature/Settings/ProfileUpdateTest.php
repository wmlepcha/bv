<?php

namespace Tests\Feature\Settings;

use App\Enums\AvatarAnimal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_page_includes_photo_url()
    {
        Storage::fake('public');

        $user = User::factory()->teacher()->create();
        $path = UploadedFile::fake()->image('me.jpg')->store("avatars/{$user->id}", 'public');
        $user->forceFill(['avatar_path' => $path])->save();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/settings/profile')
                ->where('photo_url', '/storage/'.$path)
            );
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_teacher_can_upload_and_remove_profile_photo()
    {
        Storage::fake('public');

        $user = User::factory()->teacher()->create([
            'name' => 'Faculty User',
            'email' => 'faculty@example.com',
        ]);

        $avatar = UploadedFile::fake()->image('faculty.jpg', 400, 500);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $avatar,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);

        $storedPath = $user->avatar_path;

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'remove_avatar' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNull($user->avatar_path);
        Storage::disk('public')->assertMissing($storedPath);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh());
    }

    public function test_default_avatar_animal_is_stable_and_saved_choice_wins(): void
    {
        $user = User::factory()->create();

        $default = $user->avatarAnimal();
        $this->assertSame($default, $user->fresh()->avatarAnimal());
        $this->assertSame($default->value, $user->toAuthArray()['avatar_animal']);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'avatar_animal' => AvatarAnimal::Panda->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(AvatarAnimal::Panda, $user->fresh()->avatarAnimal());
        $this->get(route('profile.edit'))
            ->assertInertia(fn (Assert $page) => $page->where('avatar_animal', 'panda'));
    }

    public function test_invalid_avatar_animal_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'avatar_animal' => 'dragon',
            ])
            ->assertSessionHasErrors('avatar_animal');
    }
}
