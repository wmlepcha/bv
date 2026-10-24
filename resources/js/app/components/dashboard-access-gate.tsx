import { useForm, usePage } from '@inertiajs/react';
import {  useState } from 'react';
import type {ReactNode} from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/responsive-dialog';
import type { User } from '@/types/auth';
import RegistrationPlanPicker from '@app/components/registration-plan-picker';
import { useRegistrationCheckout } from '@app/hooks/use-registration-checkout';
import InputError from '@shared/components/input-error';
import { BRAND_NAME_TM } from '@shared/lib/brand';
import type { RegistrationPlan } from '@shared/types/registration-plan';

type SharedProps = {
    auth: {
        user: User | null;
    };
    cashfreeAppId: string | null;
    registrationPlans: RegistrationPlan[];
    registrationTermsHtml: string;
    studentPhone: string | null;
};

type Props = {
    children: ReactNode;
};

function formatExpiryDate(value: string): string {
    return new Date(value).toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export default function DashboardAccessGate({ children }: Props) {
    const {
        auth,
        cashfreeAppId,
        registrationPlans,
        registrationTermsHtml,
        studentPhone,
    } = usePage<SharedProps>().props;
    const user = auth.user;
    const [agreedToTerms, setAgreedToTerms] = useState(false);
    const [termsDialogOpen, setTermsDialogOpen] = useState(false);
    const [codeDialogOpen, setCodeDialogOpen] = useState(false);
    const [phone, setPhone] = useState(studentPhone ?? '');

    const codeForm = useForm({
        code: '',
    });

    const {
        isPaying,
        phoneDialogOpen,
        setPhoneDialogOpen,
        phoneError,
        setPhoneError,
        activePlanId,
        startPayment,
        submitPhone,
    } = useRegistrationCheckout({
        agreedToTerms,
        cashfreeAppId,
        studentPhone,
        phone,
        setPhone,
        onBeforeCheckout: () => {
            setTermsDialogOpen(false);
            setCodeDialogOpen(false);
        },
    });

    const canAccess = user?.can_access_dashboard ?? false;
    const isStudent = user?.role === 'student';

    const submitCode = (event: React.FormEvent) => {
        event.preventDefault();

        if (!agreedToTerms) {
            return;
        }

        codeForm.post('/activation-codes/redeem', {
            onSuccess: () => setCodeDialogOpen(false),
        });
    };

    if (!isStudent || canAccess) {
        return <>{children}</>;
    }

    const hadAccessBefore =
        user?.registration_paid_at !== null ||
        user?.registration_expires_at !== null;

    const subtext = hadAccessBefore
        ? `Your plan ended on ${formatExpiryDate(
              user?.registration_expires_at ?? user?.registration_paid_at ?? '',
          )}. Choose a plan to continue your dashboard access.`
        : 'Choose a plan to access classes, PDFs, notifications, and your CV profile.';

    const mobileSubtext = hadAccessBefore
        ? 'Your plan has expired. Pick a plan to restore access.'
        : 'Classes, CV, PDFs, and notifications included.';

    return (
        <div className="relative min-h-0 overflow-hidden">
            <div className="pointer-events-none select-none blur-sm">
                {children}
            </div>

            <div className="absolute inset-0 flex items-start overflow-y-hidden bg-background/85 p-2 backdrop-blur-md sm:p-4 md:pl-9">
                <div className="mx-auto flex min-h-0 w-full max-w-5xl lg:max-w-7xl">
                    <div
                        className="relative flex w-full flex-col overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-brand-cool-dark via-[#031835] to-[#042a5c] p-3 shadow-2xl shadow-brand-cool-dark/25 sm:rounded-[1.5rem] sm:p-5 md:p-6 max-h-[calc(100dvh-2rem)] overflow-y-auto overscroll-contain md:overflow-y-hidden"
                    >
                        <div
                            className="pointer-events-none absolute -right-20 -top-24 size-72 rounded-full bg-brand-cool-light/10 blur-3xl"
                            aria-hidden
                        />
                        <div
                            className="pointer-events-none absolute -bottom-28 -left-20 size-80 rounded-full bg-primary/10 blur-3xl"
                            aria-hidden
                        />

                        <div className="relative z-10 flex flex-col">
                            <div className="mb-2 flex shrink-0 flex-col gap-1.5 md:mb-4 md:flex-row md:items-end md:justify-between md:gap-2">
                                <div>
                                    <p className="text-[10px] font-semibold uppercase tracking-[0.18em] text-brand-cool-light/90 md:text-[11px]">
                                        Student access
                                    </p>
                                    <h2 className="text-xl font-semibold tracking-tight text-white sm:text-2xl md:text-3xl">
                                        Choose your plan
                                    </h2>
                                </div>
                                <p className="text-xs leading-snug text-white/75 sm:hidden">
                                    {mobileSubtext}
                                </p>
                                <p className="hidden max-w-md text-sm leading-snug text-white/75 sm:block md:text-right">
                                    {subtext}
                                </p>
                            </div>

                            <div className="mb-2 flex shrink-0 items-center gap-2.5 rounded-lg border border-white/15 bg-white/5 px-2.5 py-2 backdrop-blur-sm md:mb-4 md:gap-3 md:rounded-xl md:px-3 md:py-2.5">
                                <Checkbox
                                    id="agree-terms"
                                    checked={agreedToTerms}
                                    onCheckedChange={(checked) =>
                                        setAgreedToTerms(checked === true)
                                    }
                                    className="size-4 border-white/35 bg-white/10 data-[state=checked]:border-primary data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground"
                                />
                                <p className="text-xs leading-5 text-white/90 md:text-sm">
                                    <Label
                                        htmlFor="agree-terms"
                                        className="font-normal text-white/95"
                                    >
                                        I agree to the{' '}
                                    </Label>
                                    <button
                                        type="button"
                                        className="font-semibold text-brand-warm-light underline decoration-brand-warm-light/50 underline-offset-4 transition hover:text-white hover:decoration-white"
                                        onClick={() => setTermsDialogOpen(true)}
                                    >
                                        Terms and Conditions
                                    </button>
                                </p>
                            </div>

                            <div className="shrink-0">
                                <RegistrationPlanPicker
                                    plans={registrationPlans}
                                    agreedToTerms={agreedToTerms}
                                    isPaying={isPaying}
                                    activePlanId={activePlanId}
                                    cashfreeAppId={cashfreeAppId}
                                    surface="dark"
                                    onSelectPlan={(planId) =>
                                        void startPayment(planId)
                                    }
                                />
                            </div>

                            {!cashfreeAppId && (
                                <p className="mt-3 shrink-0 text-sm text-white/70">
                                    Online payment is not configured yet.
                                    Contact support or enter your registration
                                    code.
                                </p>
                            )}

                            <div className="mt-2 flex shrink-0 justify-center md:mt-4">
                                <button
                                    type="button"
                                    className="text-xs font-medium text-white/75 underline-offset-4 transition hover:text-white hover:underline disabled:pointer-events-none disabled:opacity-50 md:text-sm"
                                    disabled={!agreedToTerms}
                                    onClick={() => setCodeDialogOpen(true)}
                                >
                                    Already paid offline? Enter registration
                                    code
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Dialog open={termsDialogOpen} onOpenChange={setTermsDialogOpen}>
                <DialogContent className="sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Terms and Conditions</DialogTitle>
                        <DialogDescription>
                            Please read these terms before choosing a plan.
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        className="max-h-[60vh] overflow-y-auto text-sm leading-relaxed text-foreground/90 [&_a]:text-primary [&_a]:underline [&_em]:italic [&_h2]:mt-4 [&_h2]:text-base [&_h2]:font-semibold [&_h3]:mt-3 [&_h3]:text-sm [&_h3]:font-semibold [&_li]:mt-1 [&_ol]:mt-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mt-3 [&_p:first-child]:mt-0 [&_strong]:font-semibold [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:pl-5"
                        dangerouslySetInnerHTML={{
                            __html: registrationTermsHtml,
                        }}
                    />
                </DialogContent>
            </Dialog>

            <Dialog open={phoneDialogOpen} onOpenChange={setPhoneDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Mobile number required</DialogTitle>
                        <DialogDescription>
                            Cashfree needs a mobile number to process your
                            payment. We&apos;ll save it to your profile.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitPhone} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="payment-phone">Mobile number</Label>
                            <Input
                                id="payment-phone"
                                type="tel"
                                inputMode="numeric"
                                value={phone}
                                onChange={(event) => {
                                    setPhone(event.target.value);
                                    setPhoneError(null);
                                }}
                                placeholder="9876543210"
                                autoFocus
                            />
                            <InputError message={phoneError ?? undefined} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={!agreedToTerms || isPaying}
                        >
                            {isPaying
                                ? 'Opening payment...'
                                : 'Continue to payment'}
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={codeDialogOpen} onOpenChange={setCodeDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Already paid? Enter your registration code
                        </DialogTitle>
                        <DialogDescription>
                            Enter the registration code provided by{' '}
                            {BRAND_NAME_TM} for students who paid offline. This
                            unlocks your dashboard for two years.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitCode} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="code">Registration code</Label>
                            <Input
                                id="code"
                                value={codeForm.data.code}
                                onChange={(event) =>
                                    codeForm.setData('code', event.target.value)
                                }
                                placeholder="BV-26-A3K9"
                                autoFocus
                            />
                            <InputError message={codeForm.errors.code} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={codeForm.processing}
                        >
                            Unlock dashboard
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
