<?php

namespace Tests\Feature\Auth;

use App\Models\ActivationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivationCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_student_can_redeem_valid_code(): void
    {
        $user = User::factory()->student()->unpaid()->create();

        $code = ActivationCode::create([
            'code' => '003-2026-0001',
            'email' => $user->email,
            'student_number' => '003-2026-0001',
        ]);

        $response = $this->actingAs($user)->post(route('activation-codes.redeem'), [
            'code' => $code->code,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();
        $code->refresh();

        $this->assertNotNull($user->registration_paid_at);
        $this->assertSame('003-2026-0001', $user->student_number);
        $this->assertNotNull($code->used_at);
    }

    public function test_used_code_cannot_be_redeemed_again(): void
    {
        $user = User::factory()->student()->unpaid()->create();

        $code = ActivationCode::create([
            'code' => '003-2026-0002',
            'student_number' => '003-2026-0002',
            'used_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('activation-codes.redeem'), [
            'code' => $code->code,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_code_with_wrong_email_is_rejected(): void
    {
        $user = User::factory()->student()->unpaid()->create([
            'email' => 'student@example.com',
        ]);

        ActivationCode::create([
            'code' => '003-2026-0003',
            'email' => 'other@example.com',
            'student_number' => '003-2026-0003',
        ]);

        $response = $this->actingAs($user)->post(route('activation-codes.redeem'), [
            'code' => '003-2026-0003',
        ]);

        $response->assertSessionHasErrors('code');
    }
}
