import { router, useForm, usePage } from '@inertiajs/react';
import {
    Briefcase,
    CheckCircle2,
    ChevronRight,
    GraduationCap,
    UserRound,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import AppLogoIcon from '@app/components/app-logo-icon';
import { resetPageTransition } from '@marketing/animations/gsap/timelines/transitions';
import { BirthDatePicker } from '@marketing/components/birth-date-picker';
import InputError from '@shared/components/input-error';
import TurnstileWidget from '@shared/components/turnstile-widget';
import { BRAND_NAME_TM } from '@shared/lib/brand';
import { normalizePhone, validatePhone } from '@shared/lib/phone';

export type InquiryRole = 'candidate' | 'recruiter';

type ModalPhase = 'role' | 'form' | 'success';

interface LearnMoreInquiryModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initialRole?: InquiryRole;
}

interface CandidateFormData {
    type: 'candidate';
    first_name: string;
    last_name: string;
    date_of_birth: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    country: string;
    education_level: string;
    field_of_study: string;
    years_of_experience: string;
    languages: string;
    career_goals: string;
    qualification_certificate: File | null;
    resume: File | null;
    id_document: File | null;
    fax_number: string;
    'cf-turnstile-response': string;
}

interface RecruiterFormData {
    type: 'recruiter';
    company_name: string;
    industry: string;
    company_size: string;
    website: string;
    contact_name: string;
    designation: string;
    email: string;
    phone: string;
    roles_hiring: string;
    positions_count: string;
    required_skills: string;
    preferred_locations: string;
    heard_about: string;
    hiring_timeline: string;
    additional_notes: string;
    fax_number: string;
    'cf-turnstile-response': string;
}

const NAME_PATTERN = /^[\p{L}\p{M}\s'.-]+$/u;
const PLACE_PATTERN = /^[\p{L}\p{M}\p{N}\s'.,()-]+$/u;
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const MAX_FILE_BYTES = 5 * 1024 * 1024;
const DOCUMENT_TYPES = ['pdf', 'jpg', 'jpeg', 'png'];
const RESUME_TYPES = ['pdf', 'doc', 'docx'];

/** Mirrors the server rules in StoreInquiryRequest so errors show on the step they belong to. */
function textError(
    value: string,
    label: string,
    rules: {
        required?: boolean;
        min?: number;
        max?: number;
        pattern?: RegExp;
        patternMessage?: string;
    },
): string | undefined {
    const trimmed = value.trim();

    if (trimmed === '') {
        return rules.required ? `${label} is required.` : undefined;
    }

    if (rules.min && trimmed.length < rules.min) {
        return `${label} must be at least ${rules.min} characters.`;
    }

    if (rules.max && trimmed.length > rules.max) {
        return `${label} must be at most ${rules.max} characters.`;
    }

    if (rules.pattern && !rules.pattern.test(trimmed)) {
        return rules.patternMessage ?? `${label} contains invalid characters.`;
    }

    return undefined;
}

function fileError(
    file: File | null,
    label: string,
    allowed: string[],
    required: boolean,
): string | undefined {
    if (!file) {
        return required ? `${label} is required.` : undefined;
    }

    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

    if (!allowed.includes(extension)) {
        return `${label} must be a ${allowed.join(', ').toUpperCase()} file.`;
    }

    if (file.size > MAX_FILE_BYTES) {
        return `${label} must be 5 MB or smaller.`;
    }

    return undefined;
}

function emailError(value: string): string | undefined {
    const trimmed = value.trim();

    if (!trimmed) {
        return 'Email is required.';
    }

    if (trimmed.length > 255 || !EMAIL_PATTERN.test(trimmed)) {
        return 'Enter a valid email address.';
    }

    return undefined;
}

function phoneFieldError(value: string): string | undefined {
    return value.trim()
        ? (validatePhone(value) ?? undefined)
        : 'Phone number is required.';
}

function websiteError(value: string): string | undefined {
    const trimmed = value.trim();

    if (!trimmed) {
        return undefined;
    }

    try {
        const url = new URL(trimmed);

        if (
            (url.protocol !== 'http:' && url.protocol !== 'https:') ||
            trimmed.length > 255
        ) {
            throw new Error('invalid');
        }
    } catch {
        return 'Enter a valid website starting with http:// or https://.';
    }

    return undefined;
}

function birthDateError(value: string): string | undefined {
    if (!value) {
        return 'Date of birth is required.';
    }

    const date = new Date(value);

    if (
        Number.isNaN(date.getTime()) ||
        date >= new Date() ||
        date < new Date('1920-01-01')
    ) {
        return 'Enter a valid date of birth.';
    }

    return undefined;
}

/** Wizard step that owns each server-validated field. */
const FIELD_STEP: Record<string, number> = {
    first_name: 0,
    last_name: 0,
    date_of_birth: 0,
    company_name: 0,
    industry: 0,
    company_size: 0,
    website: 0,
    email: 1,
    phone: 1,
    address: 1,
    city: 1,
    country: 1,
    contact_name: 1,
    designation: 1,
    education_level: 2,
    field_of_study: 2,
    years_of_experience: 2,
    languages: 2,
    career_goals: 2,
    roles_hiring: 2,
    positions_count: 2,
    required_skills: 2,
    preferred_locations: 2,
};

const candidateSteps = [
    { title: 'Personal information', description: 'Tell us who you are.' },
    { title: 'Contact details', description: 'How can we reach you?' },
    {
        title: 'Qualifications',
        description: 'Share your background and goals.',
    },
    { title: 'Documents', description: 'Upload your certificates and resume.' },
];

const recruiterSteps = [
    {
        title: 'Company information',
        description: 'Tell us about your organisation.',
    },
    { title: 'Contact details', description: 'Who should we coordinate with?' },
    {
        title: 'Hiring needs',
        description: 'What roles are you looking to fill?',
    },
    {
        title: 'Additional information',
        description: 'Anything else we should know.',
    },
];

const initialCandidateData: CandidateFormData = {
    type: 'candidate',
    first_name: '',
    last_name: '',
    date_of_birth: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    country: '',
    education_level: '',
    field_of_study: '',
    years_of_experience: '',
    languages: '',
    career_goals: '',
    qualification_certificate: null,
    resume: null,
    id_document: null,
    fax_number: '',
    'cf-turnstile-response': '',
};

const initialRecruiterData: RecruiterFormData = {
    type: 'recruiter',
    company_name: '',
    industry: '',
    company_size: '',
    website: '',
    contact_name: '',
    designation: '',
    email: '',
    phone: '',
    roles_hiring: '',
    positions_count: '',
    required_skills: '',
    preferred_locations: '',
    heard_about: '',
    hiring_timeline: '',
    additional_notes: '',
    fax_number: '',
    'cf-turnstile-response': '',
};

function Field({
    label,
    required,
    error,
    children,
}: {
    label: string;
    required?: boolean;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2 xs-short:gap-1">
            <Label className="xs-short:text-xs xs-short:leading-tight">
                {label}
                {required && <span className="text-primary"> *</span>}
            </Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

function FormSelect({
    id,
    label,
    value,
    onChange,
    options,
    required,
    error,
    placeholder,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: { value: string; label: string }[];
    required?: boolean;
    error?: string;
    placeholder?: string;
}) {
    return (
        <Field label={label} required={required} error={error}>
            <Select value={value || undefined} onValueChange={onChange}>
                <SelectTrigger id={id} className="w-full">
                    <SelectValue
                        placeholder={placeholder ?? 'Select an option'}
                    />
                </SelectTrigger>
                <SelectContent className="z-[60] max-h-60">
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </Field>
    );
}

function FileField({
    id,
    label,
    required,
    error,
    accept,
    fileName,
    onChange,
}: {
    id: string;
    label: string;
    required?: boolean;
    error?: string;
    accept: string;
    fileName: string | null;
    onChange: (file: File | null) => void;
}) {
    return (
        <Field label={label} required={required} error={error}>
            <label
                htmlFor={id}
                className="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-foreground/15 bg-foreground/[0.03] px-4 py-6 text-center transition-colors hover:border-primary/40 hover:bg-foreground/[0.05]"
            >
                <span className="text-sm font-medium text-foreground/90">
                    {fileName ?? 'Click to upload a file'}
                </span>
                <span className="mt-1 text-xs text-muted-foreground">
                    {accept.replace(/,/g, ', ')}
                </span>
                <input
                    id={id}
                    type="file"
                    accept={accept}
                    className="sr-only"
                    onChange={(e) => onChange(e.target.files?.[0] ?? null)}
                />
            </label>
        </Field>
    );
}

function StepProgress({
    steps,
    currentStep,
}: {
    steps: { title: string }[];
    currentStep: number;
}) {
    return (
        <div className="mb-6 shrink-0 space-y-3 xs-short:mb-2 xs-short:space-y-1">
            <div className="flex gap-2">
                {steps.map((step, index) => (
                    <div
                        key={step.title}
                        className={cn(
                            'h-1.5 flex-1 rounded-full transition-colors',
                            index <= currentStep
                                ? 'bg-primary'
                                : 'bg-foreground/10',
                        )}
                    />
                ))}
            </div>
            <p className="text-xs font-medium tracking-wider text-muted-foreground uppercase xs-short:text-[10px]">
                Step {currentStep + 1} of {steps.length}
            </p>
        </div>
    );
}

export function LearnMoreInquiryModal({
    open,
    onOpenChange,
    initialRole,
}: LearnMoreInquiryModalProps) {
    const { inquiryOptions, siteLayout } = usePage().props;
    const turnstileSiteKey = inquiryOptions.turnstileSiteKey;
    const [turnstileResetKey, setTurnstileResetKey] = useState(0);
    const { logoUrlLight, logoUrlDark } = siteLayout.header;
    const [phase, setPhase] = useState<ModalPhase>('role');
    const [role, setRole] = useState<InquiryRole | null>(null);
    const [step, setStep] = useState(0);
    const [stepErrors, setStepErrors] = useState<Record<string, string>>({});

    const candidateForm = useForm<CandidateFormData>(initialCandidateData);
    const recruiterForm = useForm<RecruiterFormData>(initialRecruiterData);

    const steps = role === 'recruiter' ? recruiterSteps : candidateSteps;

    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;

            if (flash?.inquirySubmitted) {
                setPhase('success');
            }
        });
    }, []);

    useEffect(() => {
        if (!open || !initialRole) {
            return;
        }

        setRole(initialRole);
        setStep(0);
        setStepErrors({});
        candidateForm.clearErrors();
        recruiterForm.clearErrors();
        setPhase('form');
    }, [open, initialRole]);

    const resetModal = useCallback(() => {
        setPhase('role');
        setRole(null);
        setStep(0);
        setStepErrors({});
        candidateForm.clearErrors();
        recruiterForm.clearErrors();
        candidateForm.setData(initialCandidateData);
        recruiterForm.setData(initialRecruiterData);
    }, [candidateForm, recruiterForm]);

    const handleOpenChange = (nextOpen: boolean) => {
        if (!nextOpen) {
            resetModal();

            const wrapper = document.getElementById('page-transition-wrapper');

            if (wrapper instanceof HTMLElement) {
                resetPageTransition(wrapper);
            }
        }

        onOpenChange(nextOpen);
    };

    const selectRole = (selectedRole: InquiryRole) => {
        setRole(selectedRole);
        setStep(0);
        setStepErrors({});
        candidateForm.clearErrors();
        recruiterForm.clearErrors();
        setPhase('form');
    };

    const validateStep = (): boolean => {
        const errors: Record<string, string | undefined> = {};

        if (role === 'candidate') {
            const data = candidateForm.data;
            const person = {
                required: true,
                min: 2,
                max: 100,
                pattern: NAME_PATTERN,
                patternMessage: 'Use letters only.',
            };
            const place = {
                required: true,
                min: 2,
                max: 100,
                pattern: PLACE_PATTERN,
            };

            if (step === 0) {
                errors.first_name = textError(
                    data.first_name,
                    'First name',
                    person,
                );
                errors.last_name = textError(
                    data.last_name,
                    'Last name',
                    person,
                );
                errors.date_of_birth = birthDateError(data.date_of_birth);
            }

            if (step === 1) {
                errors.email = emailError(data.email);
                errors.phone = phoneFieldError(data.phone);
                errors.address = textError(data.address, 'Address', {
                    min: 5,
                    max: 255,
                });
                errors.city = textError(data.city, 'City', place);
                errors.country = textError(data.country, 'Country', place);
            }

            if (step === 2) {
                errors.education_level = data.education_level
                    ? undefined
                    : 'Education level is required.';
                errors.years_of_experience = data.years_of_experience
                    ? undefined
                    : 'Experience is required.';
                errors.field_of_study = textError(
                    data.field_of_study,
                    'Field of study',
                    { min: 2, max: 150 },
                );
                errors.languages = textError(data.languages, 'Languages', {
                    required: true,
                    min: 2,
                    max: 255,
                });
                errors.career_goals = textError(
                    data.career_goals,
                    'Career goals',
                    { min: 10, max: 1000 },
                );
            }

            if (step === 3) {
                errors.qualification_certificate = fileError(
                    data.qualification_certificate,
                    'Qualification certificate',
                    DOCUMENT_TYPES,
                    true,
                );
                errors.resume = fileError(
                    data.resume,
                    'Resume',
                    RESUME_TYPES,
                    true,
                );
                errors.id_document = fileError(
                    data.id_document,
                    'ID document',
                    DOCUMENT_TYPES,
                    false,
                );
            }
        }

        if (role === 'recruiter') {
            const data = recruiterForm.data;

            if (step === 0) {
                errors.company_name = textError(
                    data.company_name,
                    'Company name',
                    { required: true, min: 2, max: 150 },
                );
                errors.industry = textError(data.industry, 'Industry', {
                    required: true,
                    min: 2,
                    max: 100,
                });
                errors.website = websiteError(data.website);
            }

            if (step === 1) {
                errors.contact_name = textError(
                    data.contact_name,
                    'Contact name',
                    {
                        required: true,
                        min: 2,
                        max: 100,
                        pattern: NAME_PATTERN,
                        patternMessage: 'Use letters only.',
                    },
                );
                errors.designation = textError(
                    data.designation,
                    'Designation',
                    { required: true, min: 2, max: 100 },
                );
                errors.email = emailError(data.email);
                errors.phone = phoneFieldError(data.phone);
            }

            if (step === 2) {
                errors.roles_hiring = textError(
                    data.roles_hiring,
                    'Roles hiring',
                    { required: true, min: 3, max: 255 },
                );
                errors.positions_count = data.positions_count
                    ? undefined
                    : 'Number of positions is required.';
                errors.required_skills = textError(
                    data.required_skills,
                    'Required skills',
                    { required: true, min: 10, max: 500 },
                );
                errors.preferred_locations = textError(
                    data.preferred_locations,
                    'Preferred locations',
                    { min: 2, max: 255 },
                );
            }

            if (step === 3) {
                errors.heard_about = textError(data.heard_about, 'This field', {
                    max: 255,
                });
                errors.additional_notes = textError(
                    data.additional_notes,
                    'Additional notes',
                    { min: 10, max: 1000 },
                );
            }
        }

        const found = Object.fromEntries(
            Object.entries(errors).filter(
                (entry): entry is [string, string] => entry[1] !== undefined,
            ),
        );

        setStepErrors(found);

        return Object.keys(found).length === 0;
    };

    const handleNext = () => {
        if (!validateStep()) {
            return;
        }

        if (step < steps.length - 1) {
            setStep((current) => current + 1);
            setStepErrors({});
            candidateForm.clearErrors();
            recruiterForm.clearErrors();

            return;
        }

        if (turnstileSiteKey && !activeForm.data['cf-turnstile-response']) {
            setStepErrors({
                'cf-turnstile-response': 'Please complete the security check.',
            });

            return;
        }

        activeForm.transform((data) => ({
            ...data,
            phone: normalizePhone(data.phone),
        }));
        activeForm.post('/inquiries', {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => setPhase('success'),
            onError: (errors) => {
                setStep(
                    Math.min(
                        ...Object.keys(errors).map(
                            (field) => FIELD_STEP[field] ?? steps.length - 1,
                        ),
                        steps.length - 1,
                    ),
                );
                setSecurityField('cf-turnstile-response', '');
                setTurnstileResetKey((key) => key + 1);
            },
        });
    };

    // Security fields exist on both forms; setting the inactive one is harmless.
    const setSecurityField = (
        field: 'fax_number' | 'cf-turnstile-response',
        value: string,
    ) => {
        candidateForm.setData(field, value);
        recruiterForm.setData(field, value);
    };

    const handleBack = () => {
        if (step > 0) {
            setStep((current) => current - 1);
            setStepErrors({});

            return;
        }

        setPhase('role');
        setRole(null);
    };

    const currentStepMeta = steps[step];

    const candidateFormFields = useMemo(() => {
        const data = candidateForm.data;

        return (
            <div
                className={cn(
                    'space-y-4',
                    step === 0 &&
                        'max-sm:flex max-sm:h-full max-sm:flex-1 max-sm:flex-col max-sm:justify-evenly max-sm:space-y-0 xs-short:block xs-short:space-y-2',
                )}
            >
                {step === 0 && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2 xs-short:gap-2">
                            <Field
                                label="First name"
                                required
                                error={
                                    stepErrors.first_name ??
                                    candidateForm.errors.first_name
                                }
                            >
                                <Input
                                    className="xs-short:h-8 xs-short:text-sm"
                                    value={data.first_name}
                                    onChange={(e) =>
                                        candidateForm.setData(
                                            'first_name',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Last name"
                                required
                                error={
                                    stepErrors.last_name ??
                                    candidateForm.errors.last_name
                                }
                            >
                                <Input
                                    className="xs-short:h-8 xs-short:text-sm"
                                    value={data.last_name}
                                    onChange={(e) =>
                                        candidateForm.setData(
                                            'last_name',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                        <Field
                            label="Date of birth"
                            required
                            error={
                                stepErrors.date_of_birth ??
                                candidateForm.errors.date_of_birth
                            }
                        >
                            <BirthDatePicker
                                value={data.date_of_birth}
                                onChange={(v) =>
                                    candidateForm.setData('date_of_birth', v)
                                }
                            />
                        </Field>
                    </>
                )}

                {step === 1 && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                label="Email"
                                required
                                error={
                                    stepErrors.email ??
                                    candidateForm.errors.email
                                }
                            >
                                <Input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        candidateForm.setData(
                                            'email',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Phone"
                                required
                                error={
                                    stepErrors.phone ??
                                    candidateForm.errors.phone
                                }
                            >
                                <Input
                                    inputMode="tel"
                                    placeholder="e.g. +919832581198"
                                    value={data.phone}
                                    onChange={(e) =>
                                        candidateForm.setData(
                                            'phone',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                        <Field
                            label="Address"
                            error={
                                stepErrors.address ??
                                candidateForm.errors.address
                            }
                        >
                            <Input
                                value={data.address}
                                onChange={(e) =>
                                    candidateForm.setData(
                                        'address',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                label="City"
                                required
                                error={
                                    stepErrors.city ?? candidateForm.errors.city
                                }
                            >
                                <Input
                                    value={data.city}
                                    onChange={(e) =>
                                        candidateForm.setData(
                                            'city',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Country"
                                required
                                error={
                                    stepErrors.country ??
                                    candidateForm.errors.country
                                }
                            >
                                <Input
                                    value={data.country}
                                    onChange={(e) =>
                                        candidateForm.setData(
                                            'country',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                    </>
                )}

                {step === 2 && (
                    <>
                        <FormSelect
                            id="education_level"
                            label="Education level"
                            required
                            value={data.education_level}
                            onChange={(value) =>
                                candidateForm.setData('education_level', value)
                            }
                            error={
                                stepErrors.education_level ??
                                candidateForm.errors.education_level
                            }
                            options={inquiryOptions.educationLevels}
                        />
                        <Field
                            label="Field of study"
                            error={
                                stepErrors.field_of_study ??
                                candidateForm.errors.field_of_study
                            }
                        >
                            <Input
                                value={data.field_of_study}
                                onChange={(e) =>
                                    candidateForm.setData(
                                        'field_of_study',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <FormSelect
                            id="years_of_experience"
                            label="Years of experience"
                            required
                            value={data.years_of_experience}
                            onChange={(value) =>
                                candidateForm.setData(
                                    'years_of_experience',
                                    value,
                                )
                            }
                            error={
                                stepErrors.years_of_experience ??
                                candidateForm.errors.years_of_experience
                            }
                            options={[
                                { value: '0-1', label: '0–1 years' },
                                { value: '1-3', label: '1–3 years' },
                                { value: '3-5', label: '3–5 years' },
                                { value: '5+', label: '5+ years' },
                            ]}
                        />
                        <Field
                            label="Languages spoken"
                            required
                            error={
                                stepErrors.languages ??
                                candidateForm.errors.languages
                            }
                        >
                            <Input
                                placeholder="e.g. English, Hindi, Nepali"
                                value={data.languages}
                                onChange={(e) =>
                                    candidateForm.setData(
                                        'languages',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Career goals"
                            error={
                                stepErrors.career_goals ??
                                candidateForm.errors.career_goals
                            }
                        >
                            <textarea
                                rows={3}
                                value={data.career_goals}
                                onChange={(e) =>
                                    candidateForm.setData(
                                        'career_goals',
                                        e.target.value,
                                    )
                                }
                                className="min-h-[88px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Tell us about your career aspirations..."
                            />
                        </Field>
                    </>
                )}

                {step === 3 && (
                    <>
                        <FileField
                            id="qualification_certificate"
                            label="Qualification certificate"
                            required
                            accept=".pdf,.jpg,.jpeg,.png"
                            fileName={
                                data.qualification_certificate?.name ?? null
                            }
                            onChange={(file) =>
                                candidateForm.setData(
                                    'qualification_certificate',
                                    file,
                                )
                            }
                            error={
                                stepErrors.qualification_certificate ??
                                candidateForm.errors.qualification_certificate
                            }
                        />
                        <FileField
                            id="resume"
                            label="Resume / CV"
                            required
                            accept=".pdf,.doc,.docx"
                            fileName={data.resume?.name ?? null}
                            onChange={(file) =>
                                candidateForm.setData('resume', file)
                            }
                            error={
                                stepErrors.resume ?? candidateForm.errors.resume
                            }
                        />
                        <FileField
                            id="id_document"
                            label="ID document (optional)"
                            accept=".pdf,.jpg,.jpeg,.png"
                            fileName={data.id_document?.name ?? null}
                            onChange={(file) =>
                                candidateForm.setData('id_document', file)
                            }
                            error={
                                stepErrors.id_document ??
                                candidateForm.errors.id_document
                            }
                        />
                    </>
                )}
            </div>
        );
    }, [candidateForm, step, stepErrors]);

    const recruiterFormFields = useMemo(() => {
        const data = recruiterForm.data;

        return (
            <div className="space-y-4 max-sm:space-y-3">
                {step === 0 && (
                    <>
                        <Field
                            label="Company name"
                            required
                            error={
                                stepErrors.company_name ??
                                recruiterForm.errors.company_name
                            }
                        >
                            <Input
                                value={data.company_name}
                                onChange={(e) =>
                                    recruiterForm.setData(
                                        'company_name',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <Field
                            label="Industry"
                            required
                            error={
                                stepErrors.industry ??
                                recruiterForm.errors.industry
                            }
                        >
                            <Input
                                value={data.industry}
                                onChange={(e) =>
                                    recruiterForm.setData(
                                        'industry',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <FormSelect
                            id="company_size"
                            label="Company size"
                            value={data.company_size}
                            onChange={(value) =>
                                recruiterForm.setData('company_size', value)
                            }
                            error={
                                stepErrors.company_size ??
                                recruiterForm.errors.company_size
                            }
                            options={[
                                { value: '1-10', label: '1–10 employees' },
                                { value: '11-50', label: '11–50 employees' },
                                { value: '51-200', label: '51–200 employees' },
                                {
                                    value: '201-500',
                                    label: '201–500 employees',
                                },
                                { value: '500+', label: '500+ employees' },
                            ]}
                        />
                        <Field
                            label="Website"
                            error={
                                stepErrors.website ??
                                recruiterForm.errors.website
                            }
                        >
                            <Input
                                type="url"
                                placeholder="https://"
                                value={data.website}
                                onChange={(e) =>
                                    recruiterForm.setData(
                                        'website',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                    </>
                )}

                {step === 1 && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                label="Contact name"
                                required
                                error={
                                    stepErrors.contact_name ??
                                    recruiterForm.errors.contact_name
                                }
                            >
                                <Input
                                    value={data.contact_name}
                                    onChange={(e) =>
                                        recruiterForm.setData(
                                            'contact_name',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Designation"
                                required
                                error={
                                    stepErrors.designation ??
                                    recruiterForm.errors.designation
                                }
                            >
                                <Input
                                    value={data.designation}
                                    onChange={(e) =>
                                        recruiterForm.setData(
                                            'designation',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                label="Email"
                                required
                                error={
                                    stepErrors.email ??
                                    recruiterForm.errors.email
                                }
                            >
                                <Input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        recruiterForm.setData(
                                            'email',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Phone"
                                required
                                error={
                                    stepErrors.phone ??
                                    recruiterForm.errors.phone
                                }
                            >
                                <Input
                                    inputMode="tel"
                                    placeholder="e.g. +919832581198"
                                    value={data.phone}
                                    onChange={(e) =>
                                        recruiterForm.setData(
                                            'phone',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                    </>
                )}

                {step === 2 && (
                    <>
                        <Field
                            label="Roles you are hiring for"
                            required
                            error={
                                stepErrors.roles_hiring ??
                                recruiterForm.errors.roles_hiring
                            }
                        >
                            <Input
                                placeholder="e.g. Customer service, Hospitality, Healthcare"
                                value={data.roles_hiring}
                                onChange={(e) =>
                                    recruiterForm.setData(
                                        'roles_hiring',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <FormSelect
                            id="positions_count"
                            label="Number of open positions"
                            required
                            value={data.positions_count}
                            onChange={(value) =>
                                recruiterForm.setData('positions_count', value)
                            }
                            error={
                                stepErrors.positions_count ??
                                recruiterForm.errors.positions_count
                            }
                            options={[
                                { value: '1-5', label: '1–5' },
                                { value: '6-15', label: '6–15' },
                                { value: '16-30', label: '16–30' },
                                { value: '30+', label: '30+' },
                            ]}
                        />
                        <Field
                            label="Required skills"
                            required
                            error={
                                stepErrors.required_skills ??
                                recruiterForm.errors.required_skills
                            }
                        >
                            <textarea
                                rows={3}
                                value={data.required_skills}
                                onChange={(e) =>
                                    recruiterForm.setData(
                                        'required_skills',
                                        e.target.value,
                                    )
                                }
                                className="min-h-[88px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Language proficiency, certifications, soft skills..."
                            />
                        </Field>
                        <Field
                            label="Preferred locations"
                            error={
                                stepErrors.preferred_locations ??
                                recruiterForm.errors.preferred_locations
                            }
                        >
                            <Input
                                placeholder="Countries or cities"
                                value={data.preferred_locations}
                                onChange={(e) =>
                                    recruiterForm.setData(
                                        'preferred_locations',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                    </>
                )}

                {step === 3 && (
                    <>
                        <Field
                            label="How did you hear about us?"
                            error={
                                stepErrors.heard_about ??
                                recruiterForm.errors.heard_about
                            }
                        >
                            <Input
                                value={data.heard_about}
                                onChange={(e) =>
                                    recruiterForm.setData(
                                        'heard_about',
                                        e.target.value,
                                    )
                                }
                            />
                        </Field>
                        <FormSelect
                            id="hiring_timeline"
                            label="Hiring timeline"
                            value={data.hiring_timeline}
                            onChange={(value) =>
                                recruiterForm.setData('hiring_timeline', value)
                            }
                            error={
                                stepErrors.hiring_timeline ??
                                recruiterForm.errors.hiring_timeline
                            }
                            options={[
                                { value: 'immediate', label: 'Immediately' },
                                { value: '1-3-months', label: '1–3 months' },
                                { value: '3-6-months', label: '3–6 months' },
                                { value: 'flexible', label: 'Flexible' },
                            ]}
                        />
                        <Field
                            label="Additional notes"
                            error={
                                stepErrors.additional_notes ??
                                recruiterForm.errors.additional_notes
                            }
                        >
                            <textarea
                                rows={4}
                                value={data.additional_notes}
                                onChange={(e) =>
                                    recruiterForm.setData(
                                        'additional_notes',
                                        e.target.value,
                                    )
                                }
                                className="min-h-[112px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Share any specific requirements or partnership goals..."
                            />
                        </Field>
                    </>
                )}
            </div>
        );
    }, [recruiterForm, step, stepErrors]);

    const activeForm = role === 'recruiter' ? recruiterForm : candidateForm;
    const isProcessing = activeForm.processing;

    const isCompactStep = role === 'candidate' && step === 0;

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent
                overlayClassName="bg-background/70 backdrop-blur-md"
                className={cn(
                    'data-[state=closed]:duration-300 data-[state=open]:duration-300 sm:max-w-xl',
                    'max-sm:fixed max-sm:inset-0 max-sm:top-0 max-sm:left-0 max-sm:h-dvh max-sm:max-h-dvh max-sm:w-full max-sm:max-w-none max-sm:translate-x-0 max-sm:translate-y-0 max-sm:gap-4 max-sm:rounded-none max-sm:border-0 max-sm:p-5 max-sm:pt-[calc(1.25rem+env(safe-area-inset-top))] max-sm:pb-[calc(1.25rem+env(safe-area-inset-bottom))] xs-short:gap-2 xs-short:p-3',
                    'max-sm:data-[state=closed]:zoom-out-100 max-sm:data-[state=closed]:slide-out-to-bottom max-sm:data-[state=open]:zoom-in-100 max-sm:data-[state=open]:slide-in-from-bottom',
                    phase === 'form'
                        ? 'flex max-h-[90vh] flex-col overflow-hidden'
                        : 'flex max-h-[90vh] flex-col overflow-hidden max-sm:overflow-hidden',
                )}
            >
                {phase === 'role' && (
                    <>
                        <DialogHeader className="shrink-0 max-sm:mt-4 max-sm:gap-2 max-sm:text-left">
                            <div className="flex h-10 items-center sm:hidden">
                                <AppLogoIcon
                                    className="h-9 w-auto dark:hidden"
                                    alt={BRAND_NAME_TM}
                                    src={logoUrlLight}
                                />
                                <AppLogoIcon
                                    className="hidden h-9 w-auto dark:block"
                                    alt={BRAND_NAME_TM}
                                    src={logoUrlDark}
                                />
                            </div>
                            <DialogTitle className="max-sm:mt-2 max-sm:text-lg max-sm:whitespace-nowrap xs-short:text-lg">
                                Get started with {BRAND_NAME_TM}
                            </DialogTitle>
                            <DialogDescription className="max-sm:text-sm xs-short:text-xs">
                                Tell us who you are so we can show you the right
                                form.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 max-sm:flex max-sm:flex-col max-sm:gap-3 sm:grid-cols-2">
                            <button
                                type="button"
                                onClick={() => selectRole('candidate')}
                                className="group marketing-panel marketing-panel-hover rounded-2xl border p-6 text-left transition-colors hover:border-primary/40 max-sm:flex max-sm:min-h-[88px] max-sm:items-center max-sm:gap-4 max-sm:p-4 max-sm:transition-transform max-sm:active:scale-[0.98] xs-short:p-3"
                            >
                                <div className="mb-4 flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/15 text-primary max-sm:mb-0 max-sm:h-14 max-sm:w-14">
                                    <GraduationCap className="size-5 max-sm:size-7" />
                                </div>
                                <div className="max-sm:min-w-0 max-sm:flex-1">
                                    <p className="font-semibold max-sm:text-lg xs-short:text-base">
                                        I am a Candidate
                                    </p>
                                    <p className="mt-2 text-sm text-muted-foreground max-sm:mt-1 max-sm:text-sm">
                                        Apply for training, certification, and
                                        international placement.
                                    </p>
                                </div>
                                <ChevronRight className="size-5 shrink-0 text-muted-foreground sm:hidden" />
                            </button>

                            <button
                                type="button"
                                onClick={() => selectRole('recruiter')}
                                className="group marketing-panel marketing-panel-hover rounded-2xl border p-6 text-left transition-colors hover:border-brand-cool-light/40 max-sm:flex max-sm:min-h-[88px] max-sm:items-center max-sm:gap-4 max-sm:p-4 max-sm:transition-transform max-sm:active:scale-[0.98] xs-short:p-3"
                            >
                                <div className="mb-4 flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-cool-light/15 text-brand-cool-light max-sm:mb-0 max-sm:h-14 max-sm:w-14">
                                    <Briefcase className="size-5 max-sm:size-7" />
                                </div>
                                <div className="max-sm:min-w-0 max-sm:flex-1">
                                    <p className="font-semibold max-sm:text-lg xs-short:text-base">
                                        I am a Recruiter
                                    </p>
                                    <p className="mt-2 text-sm text-muted-foreground max-sm:mt-1 max-sm:text-sm">
                                        Partner with us to access skilled,
                                        workplace-ready talent.
                                    </p>
                                </div>
                                <ChevronRight className="size-5 shrink-0 text-muted-foreground sm:hidden" />
                            </button>
                        </div>
                    </>
                )}

                {phase === 'form' && role && currentStepMeta && (
                    <>
                        <DialogHeader className="shrink-0 xs-short:gap-0.5">
                            <DialogTitle className="flex items-center gap-2 xs-short:text-base">
                                {role === 'candidate' ? (
                                    <UserRound className="size-5 text-primary xs-short:size-4" />
                                ) : (
                                    <Briefcase className="size-5 text-brand-cool-light xs-short:size-4" />
                                )}
                                {currentStepMeta.title}
                            </DialogTitle>
                            <DialogDescription className="xs-short:hidden">
                                {currentStepMeta.description}
                            </DialogDescription>
                        </DialogHeader>

                        <StepProgress steps={steps} currentStep={step} />

                        <div className="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div
                                className={cn(
                                    'min-h-0 flex-1',
                                    isCompactStep
                                        ? 'max-sm:flex max-sm:flex-col xs-short:overflow-y-auto xs-short:overscroll-contain'
                                        : 'overflow-y-auto overscroll-contain',
                                )}
                            >
                                {role === 'candidate'
                                    ? candidateFormFields
                                    : recruiterFormFields}
                            </div>

                            {step === steps.length - 1 && (
                                <div className="mt-4 shrink-0 space-y-2">
                                    <div
                                        aria-hidden="true"
                                        className="absolute -left-[9999px] h-0 w-0 overflow-hidden"
                                    >
                                        <label htmlFor="inquiry-fax">Fax</label>
                                        <input
                                            id="inquiry-fax"
                                            type="text"
                                            tabIndex={-1}
                                            autoComplete="off"
                                            value={activeForm.data.fax_number}
                                            onChange={(e) =>
                                                setSecurityField(
                                                    'fax_number',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <TurnstileWidget
                                        siteKey={turnstileSiteKey}
                                        resetKey={turnstileResetKey}
                                        onToken={(token) =>
                                            setSecurityField(
                                                'cf-turnstile-response',
                                                token,
                                            )
                                        }
                                    />
                                    {(() => {
                                        const message =
                                            stepErrors[
                                                'cf-turnstile-response'
                                            ] ??
                                            Object.values(activeForm.errors)[0];

                                        return message ? (
                                            <p
                                                role="alert"
                                                className="text-sm text-destructive"
                                            >
                                                {message}
                                            </p>
                                        ) : null;
                                    })()}
                                </div>
                            )}

                            <div className="mt-6 flex shrink-0 items-center justify-between gap-3 border-t border-border pt-4 xs-short:mt-2 xs-short:pt-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="max-sm:h-12 max-sm:flex-1 xs-short:h-8 xs-short:px-3"
                                    onClick={handleBack}
                                >
                                    Back
                                </Button>
                                <Button
                                    type="button"
                                    className="max-sm:h-12 max-sm:flex-[2] xs-short:h-8 xs-short:px-3"
                                    onClick={handleNext}
                                    disabled={isProcessing}
                                >
                                    {isProcessing && <Spinner />}
                                    {step === steps.length - 1
                                        ? 'Submit'
                                        : 'Continue'}
                                </Button>
                            </div>
                        </div>
                    </>
                )}

                {phase === 'success' && (
                    <div className="flex flex-col items-center py-6 text-center max-sm:flex-1 max-sm:justify-center">
                        <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/15 text-primary">
                            <CheckCircle2 className="size-7" />
                        </div>
                        <DialogTitle className="text-xl">
                            Submitted successfully!
                        </DialogTitle>
                        <DialogDescription className="mt-3 max-w-sm">
                            Thank you! You have successfully submitted the form.
                            Our recruitment manager will review your{' '}
                            {role === 'candidate' ? 'application' : 'inquiry'}{' '}
                            and get back to you soon.
                        </DialogDescription>
                        <Button
                            className="mt-6"
                            onClick={() => handleOpenChange(false)}
                        >
                            Close
                        </Button>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
