import { usePage } from '@inertiajs/react';
import { useState } from 'react';
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

function formatExpiryDate(value: string): string {
    return new Date(value).toLocaleDateString('en-IN', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function isWithinRenewalWindow(expiresAt: string): boolean {
    const expiry = new Date(expiresAt);

    if (Number.isNaN(expiry.getTime())) {
        return false;
    }

    const windowStart = new Date(expiry);
    windowStart.setMonth(windowStart.getMonth() - 2);

    return Date.now() >= windowStart.getTime();
}

export default function RegistrationRenewalBanner() {
    const {
        auth,
        cashfreeAppId,
        registrationPlans,
        registrationTermsHtml,
        studentPhone,
    } = usePage<SharedProps>().props;
    const user = auth.user;

    const [planDialogOpen, setPlanDialogOpen] = useState(false);
    const [agreedToTerms, setAgreedToTerms] = useState(false);
    const [termsDialogOpen, setTermsDialogOpen] = useState(false);
    const [phone, setPhone] = useState(studentPhone ?? '');

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
            setPlanDialogOpen(false);
            setTermsDialogOpen(false);
        },
    });

    if (
        user?.role !== 'student' ||
        !user.can_access_dashboard ||
        !user.registration_expires_at
    ) {
        return null;
    }

    const expiryLabel = formatExpiryDate(user.registration_expires_at);
    const showRenewal = isWithinRenewalWindow(user.registration_expires_at);

    return (
        <div className="ml-auto max-w-full min-w-0">
            <div className="flex min-w-0 items-center justify-end gap-3">
                <p className="max-w-[16rem] min-w-0 text-right text-xs leading-snug text-foreground sm:max-w-md sm:text-sm xl:max-w-lg">
                    Your account is active till{' '}
                    <span className="font-semibold text-foreground">
                        {expiryLabel}
                    </span>
                    .
                    {showRenewal
                        ? ' Renew early to keep uninterrupted access.'
                        : null}
                </p>
                {showRenewal ? (
                    <Button
                        type="button"
                        size="sm"
                        className="shrink-0 rounded-full px-5 shadow-sm"
                        onClick={() => setPlanDialogOpen(true)}
                    >
                        Choose your plan
                    </Button>
                ) : null}
            </div>

            {showRenewal ? (
                <>
                    <Dialog
                        open={planDialogOpen}
                        onOpenChange={setPlanDialogOpen}
                    >
                        <DialogContent
                            phone="flush"
                            className="max-h-[90vh] gap-0 overflow-y-auto border-border/60 p-0 sm:max-w-4xl"
                        >
                            <div className="border-b border-border/70 bg-gradient-to-br from-accent/40 via-background to-background px-6 py-6 sm:px-8">
                                <DialogHeader className="space-y-2 text-left">
                                    <DialogTitle className="text-2xl font-semibold tracking-tight">
                                        Choose your plan
                                    </DialogTitle>
                                    <DialogDescription className="text-base leading-relaxed">
                                        Extend your dashboard access. Any
                                        remaining time is kept and your new plan
                                        is added on top.
                                    </DialogDescription>
                                </DialogHeader>
                            </div>

                            <div className="space-y-6 px-6 py-6 sm:px-8">
                                <div className="flex items-start gap-3 rounded-2xl border border-border/70 bg-muted/30 p-4">
                                    <Checkbox
                                        id="renew-agree-terms"
                                        checked={agreedToTerms}
                                        onCheckedChange={(checked) =>
                                            setAgreedToTerms(checked === true)
                                        }
                                    />
                                    <p className="text-sm leading-5 text-muted-foreground">
                                        <Label
                                            htmlFor="renew-agree-terms"
                                            className="font-normal"
                                        >
                                            I agree to the{' '}
                                        </Label>
                                        <button
                                            type="button"
                                            className="font-medium text-primary underline underline-offset-2"
                                            onClick={() =>
                                                setTermsDialogOpen(true)
                                            }
                                        >
                                            Terms and Conditions
                                        </button>
                                    </p>
                                </div>

                                <RegistrationPlanPicker
                                    plans={registrationPlans}
                                    agreedToTerms={agreedToTerms}
                                    isPaying={isPaying}
                                    activePlanId={activePlanId}
                                    cashfreeAppId={cashfreeAppId}
                                    mode="renew"
                                    surface="light"
                                    onSelectPlan={(planId) =>
                                        void startPayment(planId)
                                    }
                                />
                            </div>
                        </DialogContent>
                    </Dialog>

                    <Dialog
                        open={termsDialogOpen}
                        onOpenChange={setTermsDialogOpen}
                    >
                        <DialogContent className="sm:max-w-xl">
                            <DialogHeader>
                                <DialogTitle>Terms and Conditions</DialogTitle>
                                <DialogDescription>
                                    Please read these terms before choosing a
                                    plan.
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

                    <Dialog
                        open={phoneDialogOpen}
                        onOpenChange={setPhoneDialogOpen}
                    >
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    Mobile number required
                                </DialogTitle>
                                <DialogDescription>
                                    Cashfree needs a mobile number to process
                                    your payment. We&apos;ll save it to your
                                    profile.
                                </DialogDescription>
                            </DialogHeader>

                            <form onSubmit={submitPhone} className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="renew-payment-phone">
                                        Mobile number
                                    </Label>
                                    <Input
                                        id="renew-payment-phone"
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
                                    <InputError
                                        message={phoneError ?? undefined}
                                    />
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
                </>
            ) : null}
        </div>
    );
}
