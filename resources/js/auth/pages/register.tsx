import { Form, Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { store } from '@/routes/student/register';
import InputError from '@shared/components/input-error';
import PasswordInput from '@shared/components/password-input';
import { PrivateSeoHead } from '@shared/components/seo-head';
import TextLink from '@shared/components/text-link';
import TurnstileWidget from '@shared/components/turnstile-widget';

type SpecialIdOption = {
    value: string;
    label: string;
};

type PurposeOption = {
    value: string;
    label: string;
};

const STEPS = ['Details', 'Profile', 'Security'] as const;

const FIELD_STEP: Record<string, number> = {
    first_name: 0,
    last_name: 0,
    email: 0,
    state: 1,
    special_id: 1,
    registration_purpose: 1,
    password: 2,
    password_confirmation: 2,
    'cf-turnstile-response': 2,
};

const selectClassName =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 max-sm:h-12 max-sm:text-base dark:bg-input/30';

type Props = {
    passwordRules: string;
    portal: 'student';
    states: string[];
    specialIds: SpecialIdOption[];
    purposes: PurposeOption[];
    turnstileSiteKey: string | null;
    formToken: string;
};

export default function Register({
    passwordRules,
    states,
    specialIds,
    purposes,
    turnstileSiteKey,
    formToken,
}: Props) {
    const [turnstileResetKey, setTurnstileResetKey] = useState(0);
    const [step, setStep] = useState(0);
    const stepsRef = useRef<HTMLDivElement>(null);
    const isLastStep = step === STEPS.length - 1;

    const currentStepIsValid = () => {
        const fields = stepsRef.current?.querySelectorAll<
            HTMLInputElement | HTMLSelectElement
        >(`[data-step="${step}"] input, [data-step="${step}"] select`);

        return Array.from(fields ?? []).every((field) =>
            field.reportValidity(),
        );
    };

    const goNext = () => {
        if (currentStepIsValid()) {
            setStep((current) => Math.min(current + 1, STEPS.length - 1));
        }
    };

    const stepClassName = (index: number) => (index === step ? '' : 'hidden');

    return (
        <>
            <PrivateSeoHead title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                onError={(formErrors) => {
                    setTurnstileResetKey((key) => key + 1);
                    setStep(
                        Math.min(
                            ...Object.keys(formErrors).map(
                                (field) =>
                                    FIELD_STEP[field] ?? STEPS.length - 1,
                            ),
                            STEPS.length - 1,
                        ),
                    );
                }}
                onKeyDown={(event) => {
                    if (
                        event.key === 'Enter' &&
                        !isLastStep &&
                        event.target instanceof HTMLInputElement
                    ) {
                        event.preventDefault();
                        goNext();
                    }
                }}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div
                            className="grid gap-2"
                            role="progressbar"
                            aria-valuemin={1}
                            aria-valuemax={STEPS.length}
                            aria-valuenow={step + 1}
                            aria-label="Registration progress"
                        >
                            <div className="flex gap-1.5">
                                {STEPS.map((label, index) => (
                                    <div
                                        key={label}
                                        className={cn(
                                            'h-1.5 flex-1 rounded-full transition-colors duration-300',
                                            index <= step
                                                ? 'bg-primary'
                                                : 'bg-foreground/15',
                                        )}
                                    />
                                ))}
                            </div>
                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                <span className="font-medium text-foreground">
                                    Step {step + 1} of {STEPS.length}:{' '}
                                    {STEPS[step]}
                                </span>
                                <span className="flex items-center gap-1">
                                    {step > 0 && <Check className="size-3.5" />}
                                    {Math.round((step / STEPS.length) * 100)}%
                                    complete
                                </span>
                            </div>
                        </div>

                        <div className="grid gap-6" ref={stepsRef}>
                            <div
                                data-step="0"
                                className={cn('grid gap-6', stepClassName(0))}
                            >
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="first_name">
                                            First name
                                        </Label>
                                        <Input
                                            id="first_name"
                                            className="max-sm:h-12"
                                            type="text"
                                            required
                                            tabIndex={1}
                                            autoComplete="given-name"
                                            name="first_name"
                                            placeholder="First name"
                                        />
                                        <InputError
                                            message={errors.first_name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="last_name">
                                            Last name
                                        </Label>
                                        <Input
                                            id="last_name"
                                            className="max-sm:h-12"
                                            type="text"
                                            required
                                            tabIndex={2}
                                            autoComplete="family-name"
                                            name="last_name"
                                            placeholder="Last name"
                                        />
                                        <InputError
                                            message={errors.last_name}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        className="max-sm:h-12"
                                        type="email"
                                        required
                                        tabIndex={3}
                                        autoComplete="email"
                                        name="email"
                                        placeholder="email@example.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>
                            </div>
                            <div
                                data-step="1"
                                className={cn('grid gap-6', stepClassName(1))}
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="state">State</Label>
                                    <select
                                        id="state"
                                        name="state"
                                        required
                                        tabIndex={4}
                                        defaultValue=""
                                        className={selectClassName}
                                    >
                                        <option value="" disabled>
                                            Select your state
                                        </option>
                                        {states.map((state) => (
                                            <option key={state} value={state}>
                                                {state}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.state} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="special_id">
                                        Special ID
                                    </Label>
                                    <select
                                        id="special_id"
                                        name="special_id"
                                        required
                                        tabIndex={5}
                                        defaultValue=""
                                        className={selectClassName}
                                    >
                                        <option value="" disabled>
                                            Select special ID type
                                        </option>
                                        {specialIds.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.special_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="registration_purpose">
                                        Purpose of registration
                                    </Label>
                                    <select
                                        id="registration_purpose"
                                        name="registration_purpose"
                                        required
                                        tabIndex={6}
                                        defaultValue=""
                                        className={selectClassName}
                                    >
                                        <option value="" disabled>
                                            What are you joining for?
                                        </option>
                                        {purposes.map((option) => (
                                            <option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={errors.registration_purpose}
                                    />
                                </div>
                            </div>
                            <div
                                data-step="2"
                                className={cn('grid gap-6', stepClassName(2))}
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="password">Password</Label>
                                    <PasswordInput
                                        id="password"
                                        className="max-sm:h-12"
                                        required
                                        tabIndex={7}
                                        autoComplete="new-password"
                                        name="password"
                                        placeholder="Password"
                                        passwordrules={passwordRules}
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirm password
                                    </Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        className="max-sm:h-12"
                                        required
                                        tabIndex={8}
                                        autoComplete="new-password"
                                        name="password_confirmation"
                                        placeholder="Confirm password"
                                        passwordrules={passwordRules}
                                    />
                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>

                                <input
                                    type="hidden"
                                    name="form_token"
                                    value={formToken}
                                />
                                <div
                                    aria-hidden="true"
                                    className="absolute -left-[9999px] h-0 w-0 overflow-hidden"
                                >
                                    <label htmlFor="website">Website</label>
                                    <input
                                        id="website"
                                        type="text"
                                        name="website"
                                        tabIndex={-1}
                                        autoComplete="off"
                                    />
                                </div>

                                <TurnstileWidget
                                    siteKey={turnstileSiteKey}
                                    resetKey={turnstileResetKey}
                                />
                                <InputError
                                    message={errors['cf-turnstile-response']}
                                />
                            </div>

                            <div className="mt-2 flex gap-3">
                                {step > 0 && (
                                    <Button
                                        key="back"
                                        type="button"
                                        variant="outline"
                                        className="max-sm:h-12"
                                        onClick={() =>
                                            setStep((current) => current - 1)
                                        }
                                    >
                                        Back
                                    </Button>
                                )}
                                {isLastStep ? (
                                    <Button
                                        key="submit"
                                        type="submit"
                                        className="flex-1 max-sm:h-12"
                                        tabIndex={9}
                                        data-test="register-user-button"
                                    >
                                        {processing && <Spinner />}
                                        Create account
                                    </Button>
                                ) : (
                                    <Button
                                        key="next"
                                        type="button"
                                        className="flex-1 max-sm:h-12"
                                        onClick={goNext}
                                        data-test="register-next-button"
                                    >
                                        Next
                                    </Button>
                                )}
                            </div>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink
                                href={login({ query: { portal: 'student' } })}
                                tabIndex={10}
                            >
                                Log in
                            </TextLink>
                        </div>

                        <div className="text-center text-sm max-sm:hidden">
                            <Link
                                href={login()}
                                className="text-muted-foreground underline-offset-4 hover:underline"
                            >
                                Choose a different account type
                            </Link>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create your candidate account',
    description:
        'Sign up as a candidate. You will need to pay the registration fee to unlock your dashboard.',
    backHref: login({ query: { portal: 'student' } }).url,
};
