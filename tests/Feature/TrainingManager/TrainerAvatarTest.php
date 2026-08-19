<?php

namespace Tests\Feature\TrainingManager;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrainerAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_manager_can_create_trainer_with_avatar(): void
    {
        Storage::fake('public');

        $manager = User::factory()->trainingManager()->create();
        $avatar = UploadedFile::fake()->image('trainer.jpg', 400, 500);

        $this->actingAs($manager)
            ->post(route('training-manager.trainers.store'), [
                'name' => 'Photo Trainer',
                'email' => 'photo-trainer@example.com',
                'password' => 'password',
                'avatar' => $avatar,
            ])
            ->assertRedirect(route('training-manager.trainers.index'));

        $trainer = User::query()->where('email', 'photo-trainer@example.com')->first();

        $this->assertNotNull($trainer);
        $this->assertSame(UserRole::Teacher, $trainer->role);
        $this->assertNotNull($trainer->avatar_path);
        Storage::disk('public')->assertExists($trainer->avatar_path);

        $this->actingAs($manager)
            ->get(route('training-manager.trainers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('app/training-manager/trainers/index')
                ->has('trainers', 1)
                ->where('trainers.0.photo_url', '/storage/'.$trainer->avatar_path)
            );
    }

    public function test_training_manager_can_replace_and_remove_trainer_avatar(): void
    {
        Storage::fake('public');

        $manager = User::factory()->trainingManager()->create();
        $trainer = User::factory()->teacher()->create([
            'name' => 'Replace Me',
            'email' => 'replace@example.com',
        ]);

        $original = UploadedFile::fake()->image('original.jpg', 300, 300);
        $originalPath = $original->store("trainers/{$trainer->id}", 'public');
        $trainer->forceFill(['avatar_path' => $originalPath])->save();

        $replacement = UploadedFile::fake()->image('replacement.png', 320, 320);

        $this->actingAs($manager)
            ->put(route('training-manager.trainers.update', $trainer), [
                'name' => $trainer->name,
                'email' => $trainer->email,
                'avatar' => $replacement,
            ])
            ->assertRedirect(route('training-manager.trainers.index'));

        $trainer->refresh();

        $this->assertNotSame($originalPath, $trainer->avatar_path);
        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertExists($trainer->avatar_path);

        $replacedPath = $trainer->avatar_path;

        $this->actingAs($manager)
            ->put(route('training-manager.trainers.update', $trainer), [
                'name' => $trainer->name,
                'email' => $trainer->email,
                'remove_avatar' => '1',
            ])
            ->assertRedirect(route('training-manager.trainers.index'));

        $trainer->refresh();

        $this->assertNull($trainer->avatar_path);
        Storage::disk('public')->assertMissing($replacedPath);
    }

    public function test_non_training_manager_cannot_manage_trainer_avatars(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->teacher()->create();
        $avatar = UploadedFile::fake()->image('blocked.jpg', 200, 200);

        $this->actingAs($teacher)
            ->post(route('training-manager.trainers.store'), [
                'name' => 'Blocked Trainer',
                'email' => 'blocked@example.com',
                'password' => 'password',
                'avatar' => $avatar,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'blocked@example.com',
        ]);
    }
}
