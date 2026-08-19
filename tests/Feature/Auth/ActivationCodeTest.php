<?php

namespace Tests\Feature\Auth;

use App\Models\ActivationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivationCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_student_can_redeem_mapped_code(): void
    {
        $user = User::factory()->student()->unpaid()->create([
            'student_number' => '003-2026-1001',
        ]);

        $code = ActivationCode::create([
            'code' => 'BV-26-ABCD',
            'email' => $user->email,
            'assigned_user_id' => $user->id,
            'student_number' => $user->student_number,
        ]);

        $response = $this->actingAs($user)->post(route('activation-codes.redeem'), [
            'code' => $code->code,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();
        $code->refresh();

        $this->assertNotNull($user->registration_paid_at);
        $this->assertNotNull($user->registration_expires_at);
        $this->assertTrue($user->registration_expires_at->isFuture());
        $this->assertSame('003-2026-1001', $user->student_number);
        $this->assertNotNull($code->used_at);
        $this->assertSame($user->id, $code->used_by_user_id);
    }

    public function test_used_code_cannot_be_redeemed_again(): void
    {
        $user = User::factory()->student()->unpaid()->create();

        $code = ActivationCode::create([
            'code' => 'BV-26-EFGH',
            'assigned_user_id' => $user->id,
            'student_number' => $user->student_number,
            'used_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('activation-codes.redeem'), [
            'code' => $code->code,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_code_assigned_to_different_student_is_rejected(): void
    {
        $user = User::factory()->student()->unpaid()->create();
        $other = User::factory()->student()->unpaid()->create();

        ActivationCode::create([
            'code' => 'BV-26-IJKL',
            'email' => $other->email,
            'assigned_user_id' => $other->id,
            'student_number' => $other->student_number,
        ]);

        $response = $this->actingAs($user)->post(route('activation-codes.redeem'), [
            'code' => 'BV-26-IJKL',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->registration_paid_at);
    }

    public function test_unmapped_code_is_rejected(): void
    {
        $user = User::factory()->student()->unpaid()->create();

        ActivationCode::create([
            'code' => 'BV-26-MNOP',
            'email' => $user->email,
            'assigned_user_id' => null,
            'student_number' => $user->student_number,
        ]);

        $response = $this->actingAs($user)->post(route('activation-codes.redeem'), [
            'code' => 'BV-26-MNOP',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->registration_paid_at);
    }

    public function test_mapped_code_with_mismatched_email_is_rejected(): void
    {
        $user = User::factory()->student()->unpaid()->create([
            'email' => 'student@example.com',
        ]);

        ActivationCode::create([
            'code' => 'BV-26-QRST',
            'email' => 'other@example.com',
            'assigned_user_id' => $user->id,
            'student_number' => $user->student_number,
        ]);

        $response = $this->actingAs($user)->post(route('activation-codes.redeem'), [
            'code' => 'BV-26-QRST',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->registration_paid_at);
    }
}
