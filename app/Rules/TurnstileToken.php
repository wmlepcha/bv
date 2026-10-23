<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Throwable;

class TurnstileToken implements ValidationRule
{
    /**
     * Verifies a Cloudflare Turnstile token. Passes when no secret is configured (local dev),
     * fails closed if Cloudflare cannot be reached.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret');

        if (blank($secret)) {
            return;
        }

        $message = 'Please complete the security check and try again.';

        if (! is_string($value) || $value === '') {
            $fail($message);

            return;
        }

        try {
            $passed = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ])
                ->json('success') === true;
        } catch (Throwable) {
            $passed = false;
        }

        if (! $passed) {
            $fail($message);
        }
    }
}
