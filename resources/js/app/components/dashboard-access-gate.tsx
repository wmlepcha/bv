import { type ReactNode, useCallback, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import InputError from '@shared/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { User } from '@/types/auth';

type SharedProps = {
    auth: {
        user: User | null;
    };
    razorpayKey: string | null;
    registrationFeeAmount: number;
};

type RazorpayHandlerResponse = {
    razorpay_order_id: string;
    razorpay_payment_id: string;
    razorpay_signature: string;
};

type RazorpayCheckoutOptions = {
    key: string;
    amount: number;
    currency: string;
    name: string;
    description: string;
    order_id: string;
    prefill?: {
        name?: string;
        email?: string;
    };
    handler: (response: RazorpayHandlerResponse) => void;
    modal?: {
        ondismiss?: () => void;
    };
};

type RazorpayInstance = {
    open: () => void;
};

type RazorpayConstructor = new (options: RazorpayCheckoutOptions) => RazorpayInstance;

declare global {
    interface Window {
        Razorpay?: RazorpayConstructor;
    }
}

function loadRazorpayScript(): Promise<void> {
    if (window.Razorpay) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Razorpay.'));
        document.body.appendChild(script);
    });
}

type Props = {
    children: ReactNode;
};

export default function DashboardAccessGate({ children }: Props) {
    const { auth, razorpayKey, registrationFeeAmount } =
        usePage<SharedProps>().props;
    const user = auth.user;
    const [isPaying, setIsPaying] = useState(false);
    const [codeDialogOpen, setCodeDialogOpen] = useState(false);

    const codeForm = useForm({
        code: '',
    });

    const canAccess = user?.can_access_dashboard ?? false;
    const isStudent = user?.role === 'student';

    const startPayment = useCallback(async () => {
        if (!razorpayKey) {
            return;
        }

        setIsPaying(true);

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
            });

            if (!response.ok) {
                throw new Error('Unable to start payment.');
            }

            const order = (await response.json()) as {
                order_id: string;
                amount: number;
                currency: string;
                key: string;
                name: string;
                description: string;
                prefill?: {
                    name?: string;
                    email?: string;
                };
            };

            await loadRazorpayScript();

            if (!window.Razorpay) {
                throw new Error('Razorpay is unavailable.');
            }

            const checkout = new window.Razorpay({
                key: order.key,
                amount: order.amount,
                currency: order.currency,
                name: order.name,
                description: order.description,
                order_id: order.order_id,
                prefill: order.prefill,
                handler: (paymentResponse) => {
                    router.post('/payments/registration/verify', paymentResponse);
                },
                modal: {
                    ondismiss: () => setIsPaying(false),
                },
            });

            checkout.open();
        } catch {
            setIsPaying(false);
        }
    }, [razorpayKey]);

    const submitCode = (event: React.FormEvent) => {
        event.preventDefault();
        codeForm.post('/activation-codes/redeem', {
            onSuccess: () => setCodeDialogOpen(false),
        });
    };

    if (!isStudent || canAccess) {
        return <>{children}</>;
    }

    const feeInRupees = (registrationFeeAmount / 100).toLocaleString('en-IN', {
        style: 'currency',
        currency: 'INR',
    });

    return (
        <div className="relative min-h-[60vh]">
            <div className="pointer-events-none select-none blur-sm">{children}</div>

            <div className="absolute inset-0 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                <div className="w-full max-w-lg rounded-xl border border-border bg-card p-6 shadow-lg">
                    <h2 className="text-xl font-semibold">
                        Unlock your dashboard
                    </h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Pay the one-time registration fee ({feeInRupees}) to
                        access classes, PDFs, and notifications. If you have
                        already paid, enter your activation code below.
                    </p>

                    <div className="mt-6 flex flex-col gap-3 sm:flex-row">
                        <Button
                            className="flex-1"
                            onClick={startPayment}
                            disabled={isPaying || !razorpayKey}
                        >
                            {isPaying ? 'Opening payment...' : 'Pay registration fee'}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            className="flex-1"
                            onClick={() => setCodeDialogOpen(true)}
                        >
                            Already paid? Enter code
                        </Button>
                    </div>

                    {!razorpayKey && (
                        <p className="mt-3 text-xs text-muted-foreground">
                            Payment is not configured yet. Contact support or
                            use an activation code.
                        </p>
                    )}
                </div>
            </div>

            <Dialog open={codeDialogOpen} onOpenChange={setCodeDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Activation code</DialogTitle>
                        <DialogDescription>
                            Enter the one-time code provided by Bilingual
                            Vitae for students who paid before online
                            registration.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitCode} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="code">Code</Label>
                            <Input
                                id="code"
                                value={codeForm.data.code}
                                onChange={(event) =>
                                    codeForm.setData('code', event.target.value)
                                }
                                placeholder="003-2001-9001"
                                autoFocus
                            />
                            <InputError message={codeForm.errors.code} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={codeForm.processing}
                        >
                            Redeem code
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
