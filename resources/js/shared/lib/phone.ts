/**
 * E.164-style phone helpers (must include + and country code).
 * Mirrors App\Support\PhoneNumber on the backend.
 */

export const PHONE_PATTERN = /^\+[1-9]\d{7,14}$/;

export function normalizePhone(value: string | null | undefined): string {
    if (value == null) {
        return '';
    }

    let normalized = value.trim().replace(/[\s\-()]/g, '');

    if (normalized.startsWith('00')) {
        normalized = `+${normalized.slice(2)}`;
    }

    return normalized;
}

export function isValidPhone(value: string | null | undefined): boolean {
    const normalized = normalizePhone(value);

    if (!normalized) {
        return false;
    }

    return PHONE_PATTERN.test(normalized);
}

export function validatePhone(value: string): string | null {
    const trimmed = value.trim();

    if (!trimmed) {
        return null;
    }

    if (!isValidPhone(trimmed)) {
        return 'Enter a valid phone number with country code (e.g. +919832581198).';
    }

    return null;
}
