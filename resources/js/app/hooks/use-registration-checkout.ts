import { router } from '@inertiajs/react';
import { useCallback, useState  } from 'react';
import type {FormEvent} from 'react';

type CashfreeCheckoutResult = {
    error?: unknown;
    redirect?: boolean;
    paymentDetails?: {
        orderId?: string;
    };
};

type CashfreeInstance = {
    checkout: (options: {
        paymentSessionId: string;
        redirectTarget?: '_self' | '_modal' | '_blank';
    }) => Promise<CashfreeCheckoutResult | undefined>;
};

type CashfreeConstructor = (options: {
    mode: 'production' | 'sandbox';
}) => CashfreeInstance;

declare global {
    interface Window {
        Cashfree?: CashfreeConstructor;
    }
}

function loadCashfreeScript(): Promise<void> {
    if (window.Cashfree) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://sdk.cashfree.com/js/v3/cashfree.js';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Cashfree.'));
        document.body.appendChild(script);
    });
}

function prefersMobileCheckoutRedirect(): boolean {
    if (typeof navigator === 'undefined') {
        return false;
    }

    return /Android|iPhone|iPod/i.test(navigator.userAgent);
}

function isCashfreeFrame(element: Element): boolean {
    const src = element.getAttribute('src') ?? '';
    const id = element.id ?? '';
    const name = element.getAttribute('name') ?? '';

    return /cashfree/i.test(`${src} ${id} ${name}`);
}

function unblockCashfreeOverlay(): void {
    document.querySelectorAll('iframe').forEach((frame) => {
        if (!isCashfreeFrame(frame)) {
            return;
        }

        frame.removeAttribute('inert');
        frame.removeAttribute('aria-hidden');
        frame.style.pointerEvents = 'auto';
        frame.style.zIndex = '2147483646';

        const parent = frame.parentElement;

        if (parent) {
            parent.removeAttribute('inert');
            parent.removeAttribute('aria-hidden');
            parent.style.pointerEvents = 'auto';
            parent.style.zIndex = '2147483646';
        }
    });
}

function watchCashfreeOverlay(): () => void {
    unblockCashfreeOverlay();

    const observer = new MutationObserver(unblockCashfreeOverlay);
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['inert', 'aria-hidden'],
    });

    return () => observer.disconnect();
}

export function normalizeIndianMobile(value: string): string | null {
    const digits = value.replace(/\D+/g, '');
    let mobile = digits;

    if (mobile.startsWith('91') && mobile.length === 12) {
        mobile = mobile.slice(2);
    }

    if (mobile.startsWith('0') && mobile.length === 11) {
        mobile = mobile.slice(1);
    }

    if (!/^[6-9]\d{9}$/.test(mobile)) {
        return null;
    }

    return mobile;
}

type UseRegistrationCheckoutOptions = {
    agreedToTerms: boolean;
    cashfreeAppId: string | null;
    studentPhone: string | null;
    phone: string;
    setPhone: (value: string) => void;
    onBeforeCheckout?: () => void;
};

export function useRegistrationCheckout({
    agreedToTerms,
    cashfreeAppId,
    studentPhone,
    phone,
    setPhone,
    onBeforeCheckout,
}: UseRegistrationCheckoutOptions) {
    const [isPaying, setIsPaying] = useState(false);
    const [phoneDialogOpen, setPhoneDialogOpen] = useState(false);
    const [phoneError, setPhoneError] = useState<string | null>(null);
    const [activePlanId, setActivePlanId] = useState<number | null>(null);

    const startPayment = useCallback(
        async (planId: number, phoneOverride?: string) => {
            if (!agreedToTerms || !cashfreeAppId) {
                return;
            }

            const resolvedPhone = normalizeIndianMobile(
                phoneOverride ?? studentPhone ?? phone,
            );

            if (!resolvedPhone) {
                setActivePlanId(planId);
                setPhoneError(null);
                setPhoneDialogOpen(true);

                return;
            }

            setIsPaying(true);
            setActivePlanId(planId);
            setPhoneDialogOpen(false);

            try {
                const csrfToken =
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '';

                const response = await fetch('/payments/registration/order', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        phone: resolvedPhone,
                        plan_id: planId,
                    }),
                });

                if (!response.ok) {
                    const payload = (await response.json().catch(() => null)) as
                        | {
                              message?: string;
                              errors?: {
                                  phone?: string[];
                                  plan_id?: string[];
                              };
                          }
                        | null;

                    if (payload?.errors?.phone?.[0]) {
                        setPhoneError(payload.errors.phone[0]);
                        setPhoneDialogOpen(true);
                        setIsPaying(false);

                        return;
                    }

                    throw new Error(
                        payload?.message ?? 'Unable to start payment.',
                    );
                }

                const order = (await response.json()) as {
                    order_id: string;
                    payment_session_id: string;
                    environment: 'production' | 'sandbox';
                };

                await loadCashfreeScript();

                if (!window.Cashfree) {
                    throw new Error('Cashfree is unavailable.');
                }

                const cashfree = window.Cashfree({
                    mode:
                        order.environment === 'sandbox'
                            ? 'sandbox'
                            : 'production',
                });

                const useMobileRedirect = prefersMobileCheckoutRedirect();

                onBeforeCheckout?.();
                await new Promise<void>((resolve) => {
                    window.setTimeout(resolve, 50);
                });

                const stopWatching = watchCashfreeOverlay();

                try {
                    const result = await cashfree.checkout({
                        paymentSessionId: order.payment_session_id,
                        redirectTarget: useMobileRedirect ? '_self' : '_modal',
                    });

                    if (useMobileRedirect) {
                        return;
                    }

                    if (result?.error) {
                        setIsPaying(false);

                        return;
                    }

                    router.post('/payments/registration/verify', {
                        order_id: order.order_id,
                    });
                } finally {
                    stopWatching();
                }
            } catch {
                setIsPaying(false);
            }
        },
        [agreedToTerms, cashfreeAppId, onBeforeCheckout, phone, studentPhone],
    );

    const submitPhone = useCallback(
        (event: FormEvent) => {
            event.preventDefault();

            const normalized = normalizeIndianMobile(phone);

            if (!normalized) {
                setPhoneError('Enter a valid 10-digit Indian mobile number.');

                return;
            }

            if (activePlanId === null) {
                return;
            }

            setPhoneError(null);
            void startPayment(activePlanId, normalized);
        },
        [activePlanId, phone, startPayment],
    );

    return {
        isPaying,
        phoneDialogOpen,
        setPhoneDialogOpen,
        phoneError,
        setPhoneError,
        activePlanId,
        startPayment,
        submitPhone,
    };
}
