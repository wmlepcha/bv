/** JSON fetch with Laravel's XSRF cookie, for small endpoints that are not Inertia visits. */
export async function postJson<T>(
    url: string,
    body: Record<string, unknown>,
): Promise<{ ok: boolean; status: number; data: T }> {
    const xsrf = decodeURIComponent(
        document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    });

    return {
        ok: response.ok,
        status: response.status,
        data: (await response.json().catch(() => ({}))) as T,
    };
}
