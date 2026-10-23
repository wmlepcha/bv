/** Prefer same-origin /storage paths so APP_URL host mismatches do not break photos. */
export function toStorageSrc(url: string | null | undefined): string | null {
    if (!url) {
        return null;
    }

    if (
        url.startsWith('blob:') ||
        url.startsWith('data:') ||
        url.startsWith('/')
    ) {
        return url;
    }

    try {
        const parsed = new URL(url, window.location.origin);

        if (parsed.pathname.startsWith('/storage/')) {
            return `${parsed.pathname}${parsed.search}`;
        }
    } catch {
        // keep original
    }

    return url;
}
