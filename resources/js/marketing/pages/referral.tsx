import { Link, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Send, UserPlus } from 'lucide-react';
import { useState } from 'react';
import type { ComponentProps, FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { store as storeReferral } from '@/routes/referral';
import AppLogoIcon from '@app/components/app-logo-icon';
import { AuthPageBackground } from '@shared/components/auth-page-background';
import InputError from '@shared/components/input-error';
import { SeoHead } from '@shared/components/seo-head';
import { BRAND_NAME_TM } from '@shared/lib/brand';

type Option = { value: string; label: string };

type Props = {
    interestOptions: Option[];
    countryOptions: Option[];
};

type ReferralForm = {
    referrer_name: string;
    referrer_phone: string;
    referred_name: string;
    referred_phone: string;
    referred_email: string;
    interest: string;
    preferred_country: string;
    company_website: string;
};

type Field = Exclude<keyof ReferralForm, 'company_website'>;

/** Honeypot: hidden from people, usually filled in by bots. */
const HONEYPOT_FIELD = 'company_website';

const NAME_PATTERN = /^[\p{L}\p{M}][\p{L}\p{M}\s.'-]*$/u;
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function normalizePhone(value: string): string {
    let phone = value.trim().replace(/[\s\-()]/g, '');

    if (phone.startsWith('00')) {
        phone = `+${phone.slice(2)}`;
    }

    const indianMobile = phone.match(/^0?([6-9]\d{9})$/);

    return indianMobile ? `+91${indianMobile[1]}` : phone;
}

function isValidPhone(value: string): boolean {
    return /^\+[1-9]\d{7,14}$/.test(normalizePhone(value));
}

function validate(data: ReferralForm): Partial<Record<Field, string>> {
    const errors: Partial<Record<Field, string>> = {};

    const name = (field: Field, missing: string, invalid: string) => {
        const value = data[field].trim();

        if (value === '') {
            errors[field] = missing;
        } else if (value.length < 2 || !NAME_PATTERN.test(value)) {
            errors[field] = invalid;
        }
    };

    const phone = (field: Field, missing: string) => {
        if (data[field].trim() === '') {
            errors[field] = missing;
        } else if (!isValidPhone(data[field])) {
            errors[field] =
                'Enter a 10-digit mobile number, or include the country code (e.g. +49…).';
        }
    };

    name(
        'referrer_name',
        'Please enter your name.',
        'Please use letters only for your name.',
    );
    phone('referrer_phone', 'Please enter your mobile number.');
    name(
        'referred_name',
        'Please enter the name of the person you are referring.',
        'Please use letters only for their name.',
    );
    phone('referred_phone', 'Please enter their mobile number.');

    if (
        !errors.referrer_phone &&
        !errors.referred_phone &&
        normalizePhone(data.referrer_phone) ===
            normalizePhone(data.referred_phone)
    ) {
        errors.referred_phone =
            'Their mobile number must be different from yours.';
    }

    if (
        data.referred_email.trim() !== '' &&
        !EMAIL_PATTERN.test(data.referred_email.trim())
    ) {
        errors.referred_email = 'Please enter a valid email address.';
    }

    if (data.interest === '') {
        errors.interest = 'Please choose what they are interested in.';
    }

    if (data.preferred_country === '') {
        errors.preferred_country = 'Please choose a preferred country.';
    }

    return errors;
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <fieldset className="flex flex-col gap-4">
            <legend className="mb-4 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                {title}
            </legend>
            {children}
        </fieldset>
    );
}

export default function Referral({ interestOptions, countryOptions }: Props) {
    const { logoUrlLight, logoUrlDark } = usePage().props.siteLayout.header;
    const [submitted, setSubmitted] = useState(false);

    const form = useForm<ReferralForm>({
        referrer_name: '',
        referrer_phone: '',
        referred_name: '',
        referred_phone: '',
        referred_email: '',
        interest: '',
        preferred_country: '',
        company_website: '',
    });

    const update = (field: Field, value: string) => {
        form.setData(field, value);

        if (form.errors[field]) {
            form.clearErrors(field);
        }
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const errors = validate(form.data);

        if (Object.keys(errors).length > 0) {
            form.clearErrors();
            form.setError(errors);
            document
                .getElementById(Object.keys(errors)[0])
                ?.focus({ preventScroll: false });

            return;
        }

        form.post(storeReferral.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setSubmitted(true);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
        });
    };

    const textField = (
        field: Field,
        label: string,
        props: ComponentProps<typeof Input> = {},
        hint?: string,
    ) => (
        <div className="flex flex-col gap-2">
            <Label htmlFor={field}>{label}</Label>
            <Input
                id={field}
                name={field}
                value={form.data[field]}
                onChange={(event) => update(field, event.target.value)}
                aria-invalid={form.errors[field] ? true : undefined}
                aria-describedby={`${field}-message`}
                className="h-11"
                {...props}
            />
            {form.errors[field] ? (
                <InputError
                    id={`${field}-message`}
                    message={form.errors[field]}
                />
            ) : (
                hint && (
                    <p
                        id={`${field}-message`}
                        className="text-xs text-muted-foreground"
                    >
                        {hint}
                    </p>
                )
            )}
        </div>
    );

    const choiceField = (
        field: Field,
        label: string,
        options: Option[],
        columns: string,
    ) => (
        <fieldset
            className="flex flex-col gap-2"
            aria-describedby={`${field}-message`}
        >
            <legend className="mb-2 text-sm font-medium">{label}</legend>
            <div id={field} tabIndex={-1} className={cn('grid gap-2', columns)}>
                {options.map((option) => (
                    <label key={option.value} className="cursor-pointer">
                        <input
                            type="radio"
                            name={field}
                            value={option.value}
                            checked={form.data[field] === option.value}
                            onChange={() => update(field, option.value)}
                            className="peer sr-only"
                        />
                        <span
                            className={cn(
                                'flex h-11 items-center justify-center rounded-lg border bg-background px-3 text-center text-sm font-medium transition-colors',
                                'peer-checked:border-primary peer-checked:bg-primary/10 peer-checked:text-primary',
                                'peer-focus-visible:ring-[3px] peer-focus-visible:ring-ring/50',
                                'hover:bg-muted',
                                form.errors[field]
                                    ? 'border-destructive'
                                    : 'border-input',
                            )}
                        >
                            {option.label}
                        </span>
                    </label>
                ))}
            </div>
            <InputError id={`${field}-message`} message={form.errors[field]} />
        </fieldset>
    );

    return (
        <>
            <SeoHead
                title="Referral Form"
                description="Know someone who wants to build a career in Germany or Austria? Refer them to Bilingual Vitae."
                canonical="/referral"
            />
            <main className="relative flex min-h-svh flex-col items-center overflow-hidden px-4 py-8 sm:justify-center sm:py-12">
                <AuthPageBackground />
                <div className="relative z-10 w-full max-w-lg">
                    <div className="flex flex-col gap-8 rounded-2xl border border-white/10 bg-card/95 p-5 shadow-xl shadow-black/20 backdrop-blur-md sm:p-8">
                        <div className="flex flex-col items-center gap-4 text-center">
                            <Link href={home()} aria-label={BRAND_NAME_TM}>
                                <AppLogoIcon
                                    className="h-12 w-auto sm:h-14 dark:hidden"
                                    src={logoUrlLight}
                                />
                                <AppLogoIcon
                                    className="hidden h-12 w-auto sm:h-14 dark:block"
                                    src={logoUrlDark}
                                />
                            </Link>
                            <div className="space-y-2">
                                <h1 className="text-2xl font-semibold tracking-tight">
                                    Referral Form
                                </h1>
                                <p className="text-sm leading-relaxed text-muted-foreground">
                                    Know someone who wants to build a career in
                                    Germany or Austria? Share their details and
                                    our team will get in touch with them.
                                </p>
                            </div>
                        </div>

                        {submitted ? (
                            <div
                                role="status"
                                className="flex flex-col items-center gap-4 py-4 text-center"
                            >
                                <CheckCircle2 className="size-14 text-green-600" />
                                <div className="space-y-2">
                                    <h2 className="text-lg font-semibold">
                                        Thank you for your referral!
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        We have received the details and our
                                        team will contact them soon.
                                    </p>
                                </div>
                                <Button
                                    variant="outline"
                                    className="h-11 w-full sm:w-auto"
                                    onClick={() => setSubmitted(false)}
                                >
                                    <UserPlus />
                                    Refer someone else
                                </Button>
                            </div>
                        ) : (
                            <form
                                onSubmit={submit}
                                noValidate
                                className="flex flex-col gap-8"
                            >
                                <div
                                    aria-hidden="true"
                                    className="absolute -left-[9999px] h-px w-px overflow-hidden"
                                >
                                    <label htmlFor={HONEYPOT_FIELD}>
                                        Leave this field empty
                                    </label>
                                    <input
                                        id={HONEYPOT_FIELD}
                                        name={HONEYPOT_FIELD}
                                        type="text"
                                        tabIndex={-1}
                                        autoComplete="off"
                                        value={form.data[HONEYPOT_FIELD]}
                                        onChange={(event) =>
                                            form.setData(
                                                HONEYPOT_FIELD,
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>

                                <Section title="Your details">
                                    {textField('referrer_name', 'Your name', {
                                        autoComplete: 'name',
                                        autoCapitalize: 'words',
                                        maxLength: 100,
                                        placeholder: 'Full name',
                                    })}
                                    {textField(
                                        'referrer_phone',
                                        'Your mobile number',
                                        {
                                            type: 'tel',
                                            inputMode: 'tel',
                                            autoComplete: 'tel',
                                            maxLength: 20,
                                            placeholder: '98325 81198',
                                        },
                                        'Indian numbers can be entered without +91.',
                                    )}
                                </Section>

                                <Section title="Person you are referring">
                                    {textField('referred_name', 'Their name', {
                                        autoComplete: 'off',
                                        autoCapitalize: 'words',
                                        maxLength: 100,
                                        placeholder: 'Full name',
                                    })}
                                    {textField(
                                        'referred_phone',
                                        'Their mobile number',
                                        {
                                            type: 'tel',
                                            inputMode: 'tel',
                                            autoComplete: 'off',
                                            maxLength: 20,
                                            placeholder: '98325 81198',
                                        },
                                    )}
                                    {textField(
                                        'referred_email',
                                        'Their email (optional)',
                                        {
                                            type: 'email',
                                            inputMode: 'email',
                                            autoComplete: 'off',
                                            autoCapitalize: 'none',
                                            maxLength: 255,
                                            placeholder: 'name@example.com',
                                        },
                                    )}
                                    {choiceField(
                                        'interest',
                                        'Interested in',
                                        interestOptions,
                                        'grid-cols-2',
                                    )}
                                    {choiceField(
                                        'preferred_country',
                                        'Preferred country',
                                        countryOptions,
                                        'grid-cols-3',
                                    )}
                                </Section>

                                <Button
                                    type="submit"
                                    size="lg"
                                    className="h-12 w-full text-base font-semibold"
                                    disabled={form.processing}
                                >
                                    {form.processing ? <Spinner /> : <Send />}
                                    Submit referral
                                </Button>
                            </form>
                        )}
                    </div>
                </div>
            </main>
        </>
    );
}
