import { useEffect, useRef } from 'react';

type TurnstileApi = {
    render: (
        container: HTMLElement,
        options: {
            sitekey: string;
            theme?: 'auto';
            callback?: (token: string) => void;
            'expired-callback'?: () => void;
            'error-callback'?: () => void;
        },
    ) => string;
    reset: (widgetId?: string) => void;
    remove: (widgetId?: string) => void;
};

declare global {
    interface Window {
        turnstile?: TurnstileApi;
    }
}

const SCRIPT_SRC =
    'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

function loadTurnstile(): Promise<TurnstileApi> {
    return new Promise((resolve, reject) => {
        if (window.turnstile) {
            resolve(window.turnstile);

            return;
        }

        const script =
            document.querySelector<HTMLScriptElement>(
                `script[src="${SCRIPT_SRC}"]`,
            ) ??
            Object.assign(document.createElement('script'), {
                src: SCRIPT_SRC,
                async: true,
            });

        script.addEventListener('load', () => resolve(window.turnstile!));
        script.addEventListener('error', reject);

        if (!script.isConnected) {
            document.head.appendChild(script);
        }
    });
}

/**
 * Renders a Cloudflare Turnstile widget. Cloudflare injects the
 * `cf-turnstile-response` hidden input into the surrounding <form>.
 * Bump `resetKey` to get a fresh token (tokens are single-use).
 * Outside a <form> (e.g. Inertia `useForm` data), read the token via `onToken`.
 */
export default function TurnstileWidget({
    siteKey,
    resetKey = 0,
    onToken,
}: {
    siteKey: string | null;
    resetKey?: number;
    onToken?: (token: string) => void;
}) {
    const onTokenRef = useRef(onToken);

    useEffect(() => {
        onTokenRef.current = onToken;
    });

    const containerRef = useRef<HTMLDivElement>(null);
    const widgetId = useRef<string | undefined>(undefined);

    useEffect(() => {
        if (!siteKey || !containerRef.current) {
            return;
        }

        const container = containerRef.current;
        let cancelled = false;

        loadTurnstile()
            .then((turnstile) => {
                if (!cancelled) {
                    widgetId.current = turnstile.render(container, {
                        sitekey: siteKey,
                        callback: (token) => onTokenRef.current?.(token),
                        'expired-callback': () => onTokenRef.current?.(''),
                        'error-callback': () => onTokenRef.current?.(''),
                    });
                }
            })
            .catch(() => undefined);

        return () => {
            cancelled = true;
            window.turnstile?.remove(widgetId.current);
        };
    }, [siteKey]);

    useEffect(() => {
        if (resetKey > 0) {
            window.turnstile?.reset(widgetId.current);
        }
    }, [resetKey]);

    return siteKey ? <div ref={containerRef} /> : null;
}
