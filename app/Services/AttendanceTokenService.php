<?php

namespace App\Services;

use App\Models\AttendanceSession;

/**
 * Stateless rotating check-in codes: the code for a session changes every `ttl_seconds`
 * and is verified from the session secret, so nothing is stored per rotation.
 */
class AttendanceTokenService
{
    /**
     * @return array{token: string, expires_in: int}
     */
    public function current(AttendanceSession $session): array
    {
        $ttl = max(5, $session->ttl_seconds);
        $slot = intdiv(now()->getTimestamp(), $ttl);

        return [
            'token' => $this->encode($session, $slot),
            'expires_in' => (($slot + 1) * $ttl) - now()->getTimestamp(),
        ];
    }

    /**
     * Resolve the open session a token belongs to, or null when it is forged, stale or closed.
     */
    public function verify(string $token): ?AttendanceSession
    {
        $decoded = base64_decode(strtr($token, '-_', '+/'), true);

        if ($decoded === false || substr_count($decoded, '.') !== 2) {
            return null;
        }

        [$id, $slot, $mac] = explode('.', $decoded);

        if (! ctype_digit($id) || ! ctype_digit($slot)) {
            return null;
        }

        $session = AttendanceSession::query()->find((int) $id);

        if ($session === null || ! $session->isOpen()) {
            return null;
        }

        $currentSlot = intdiv(now()->getTimestamp(), max(5, $session->ttl_seconds));

        // Current slot, or the previous one to tolerate scan delay and clock drift.
        if ((int) $slot > $currentSlot || (int) $slot < $currentSlot - 1) {
            return null;
        }

        return hash_equals($this->mac($session, (int) $slot), $mac) ? $session : null;
    }

    private function encode(AttendanceSession $session, int $slot): string
    {
        $raw = $session->id.'.'.$slot.'.'.$this->mac($session, $slot);

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function mac(AttendanceSession $session, int $slot): string
    {
        return substr(hash_hmac('sha256', $session->id.'.'.$slot, $session->secret), 0, 24);
    }
}
