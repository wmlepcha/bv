import { router, useForm } from '@inertiajs/react';
import { Briefcase, CheckCircle2, GraduationCap, UserRound } from 'lucide-react';
import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { BirthDatePicker } from '@marketing/components/birth-date-picker';
import { resetPageTransition } from '@marketing/animations/gsap/timelines/transitions';

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
}

const candidateSteps = [
    { title: 'Personal information', description: 'Tell us who you are.' },
    { title: 'Contact details', description: 'How can we reach you?' },
    { title: 'Qualifications', description: 'Share your background and goals.' },
    { title: 'Documents', description: 'Upload your certificates and resume.' },
];

const recruiterSteps = [
    { title: 'Company information', description: 'Tell us about your organisation.' },
    { title: 'Contact details', description: 'Who should we coordinate with?' },
    { title: 'Hiring needs', description: 'What roles are you looking to fill?' },
    { title: 'Additional information', description: 'Anything else we should know.' },
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
                    <SelectValue placeholder={placeholder ?? 'Select an option'} />
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
                className="flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-white/15 bg-white/[0.03] px-4 py-6 text-center transition-colors hover:border-primary/40 hover:bg-white/[0.05]"
            >
                <span className="text-sm font-medium text-foreground/90">
                    {fileName ?? 'Click to upload a file'}
                </span>
                <span className="mt-1 text-xs text-muted-foreground">{accept.replace(/,/g, ', ')}</span>
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
                            index <= currentStep ? 'bg-primary' : 'bg-white/10',
                        )}
                    />
                ))}
            </div>
            <p className="text-xs font-medium uppercase tracking-wider text-muted-foreground xs-short:text-[10px]">
                Step {currentStep + 1} of {steps.length}
            </p>
        </div>
    );
}

export function LearnMoreInquiryModal({ open, onOpenChange, initialRole }: LearnMoreInquiryModalProps) {
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
        const errors: Record<string, string> = {};

        if (role === 'candidate') {
            const data = candidateForm.data;

            if (step === 0) {
                if (!data.first_name.trim()) errors.first_name = 'First name is required.';
                if (!data.last_name.trim()) errors.last_name = 'Last name is required.';
                if (!data.date_of_birth) errors.date_of_birth = 'Date of birth is required.';
            }

            if (step === 1) {
                if (!data.email.trim()) errors.email = 'Email is required.';
                if (!data.phone.trim()) errors.phone = 'Phone number is required.';
                if (!data.city.trim()) errors.city = 'City is required.';
                if (!data.country.trim()) errors.country = 'Country is required.';
            }

            if (step === 2) {
                if (!data.education_level) errors.education_level = 'Education level is required.';
                if (!data.years_of_experience) errors.years_of_experience = 'Experience is required.';
                if (!data.languages.trim()) errors.languages = 'Languages are required.';
            }

            if (step === 3) {
                if (!data.qualification_certificate) {
                    errors.qualification_certificate = 'Qualification certificate is required.';
                }
                if (!data.resume) errors.resume = 'Resume is required.';
            }
        }

        if (role === 'recruiter') {
            const data = recruiterForm.data;

            if (step === 0) {
                if (!data.company_name.trim()) errors.company_name = 'Company name is required.';
                if (!data.industry.trim()) errors.industry = 'Industry is required.';
            }

            if (step === 1) {
                if (!data.contact_name.trim()) errors.contact_name = 'Contact name is required.';
                if (!data.designation.trim()) errors.designation = 'Designation is required.';
                if (!data.email.trim()) errors.email = 'Email is required.';
                if (!data.phone.trim()) errors.phone = 'Phone number is required.';
            }

            if (step === 2) {
                if (!data.roles_hiring.trim()) errors.roles_hiring = 'Roles hiring is required.';
                if (!data.positions_count.trim()) errors.positions_count = 'Number of positions is required.';
                if (!data.required_skills.trim()) errors.required_skills = 'Required skills are required.';
            }
        }

        setStepErrors(errors);
        return Object.keys(errors).length === 0;
    };

    const handleNext = () => {
        if (!validateStep()) {
            return;
        }

        if (step < steps.length - 1) {
            setStep((current) => current + 1);
            setStepErrors({});
            return;
        }

        const activeForm = role === 'recruiter' ? recruiterForm : candidateForm;

        activeForm.post('/inquiries', {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
            onFlash: (flash) => {
                if (flash.inquirySubmitted) {
                    setPhase('success');
                }
            },
            onSuccess: () => setPhase('success'),
        });
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
                        <div className="grid gap-4 xs-short:gap-2 sm:grid-cols-2">
                            <Field label="First name" required error={stepErrors.first_name ?? candidateForm.errors.first_name}>
                                <Input
                                    className="xs-short:h-8 xs-short:text-sm"
                                    value={data.first_name}
                                    onChange={(e) => candidateForm.setData('first_name', e.target.value)}
                                />
                            </Field>
                            <Field label="Last name" required error={stepErrors.last_name ?? candidateForm.errors.last_name}>
                                <Input
                                    className="xs-short:h-8 xs-short:text-sm"
                                    value={data.last_name}
                                    onChange={(e) => candidateForm.setData('last_name', e.target.value)}
                                />
                            </Field>
                        </div>
                        <Field label="Date of birth" required error={stepErrors.date_of_birth ?? candidateForm.errors.date_of_birth}>
                            <BirthDatePicker
                                value={data.date_of_birth}
                                onChange={(v) => candidateForm.setData('date_of_birth', v)}
                            />
                        </Field>
                    </>
                )}

                {step === 1 && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="Email" required error={stepErrors.email ?? candidateForm.errors.email}>
                                <Input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => candidateForm.setData('email', e.target.value)}
                                />
                            </Field>
                            <Field label="Phone" required error={stepErrors.phone ?? candidateForm.errors.phone}>
                                <Input
                                    value={data.phone}
                                    onChange={(e) => candidateForm.setData('phone', e.target.value)}
                                />
                            </Field>
                        </div>
                        <Field label="Address" error={candidateForm.errors.address}>
                            <Input
                                value={data.address}
                                onChange={(e) => candidateForm.setData('address', e.target.value)}
                            />
                        </Field>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="City" required error={stepErrors.city ?? candidateForm.errors.city}>
                                <Input
                                    value={data.city}
                                    onChange={(e) => candidateForm.setData('city', e.target.value)}
                                />
                            </Field>
                            <Field label="Country" required error={stepErrors.country ?? candidateForm.errors.country}>
                                <Input
                                    value={data.country}
                                    onChange={(e) => candidateForm.setData('country', e.target.value)}
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
                            onChange={(value) => candidateForm.setData('education_level', value)}
                            error={stepErrors.education_level ?? candidateForm.errors.education_level}
                            options={[
                                { value: 'high-school', label: 'High school' },
                                { value: 'diploma', label: 'Diploma' },
                                { value: 'bachelors', label: "Bachelor's degree" },
                                { value: 'masters', label: "Master's degree" },
                                { value: 'doctorate', label: 'Doctorate' },
                                { value: 'other', label: 'Other' },
                            ]}
                        />
                        <Field label="Field of study" error={candidateForm.errors.field_of_study}>
                            <Input
                                value={data.field_of_study}
                                onChange={(e) => candidateForm.setData('field_of_study', e.target.value)}
                            />
                        </Field>
                        <FormSelect
                            id="years_of_experience"
                            label="Years of experience"
                            required
                            value={data.years_of_experience}
                            onChange={(value) => candidateForm.setData('years_of_experience', value)}
                            error={stepErrors.years_of_experience ?? candidateForm.errors.years_of_experience}
                            options={[
                                { value: '0-1', label: '0–1 years' },
                                { value: '1-3', label: '1–3 years' },
                                { value: '3-5', label: '3–5 years' },
                                { value: '5+', label: '5+ years' },
                            ]}
                        />
                        <Field label="Languages spoken" required error={stepErrors.languages ?? candidateForm.errors.languages}>
                            <Input
                                placeholder="e.g. English, Hindi, Nepali"
                                value={data.languages}
                                onChange={(e) => candidateForm.setData('languages', e.target.value)}
                            />
                        </Field>
                        <Field label="Career goals" error={candidateForm.errors.career_goals}>
                            <textarea
                                rows={3}
                                value={data.career_goals}
                                onChange={(e) => candidateForm.setData('career_goals', e.target.value)}
                                className="border-input min-h-[88px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
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
                            fileName={data.qualification_certificate?.name ?? null}
                            onChange={(file) => candidateForm.setData('qualification_certificate', file)}
                            error={stepErrors.qualification_certificate ?? candidateForm.errors.qualification_certificate}
                        />
                        <FileField
                            id="resume"
                            label="Resume / CV"
                            required
                            accept=".pdf,.doc,.docx"
                            fileName={data.resume?.name ?? null}
                            onChange={(file) => candidateForm.setData('resume', file)}
                            error={stepErrors.resume ?? candidateForm.errors.resume}
                        />
                        <FileField
                            id="id_document"
                            label="ID document (optional)"
                            accept=".pdf,.jpg,.jpeg,.png"
                            fileName={data.id_document?.name ?? null}
                            onChange={(file) => candidateForm.setData('id_document', file)}
                            error={candidateForm.errors.id_document}
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
                        <Field label="Company name" required error={stepErrors.company_name ?? recruiterForm.errors.company_name}>
                            <Input
                                value={data.company_name}
                                onChange={(e) => recruiterForm.setData('company_name', e.target.value)}
                            />
                        </Field>
                        <Field label="Industry" required error={stepErrors.industry ?? recruiterForm.errors.industry}>
                            <Input
                                value={data.industry}
                                onChange={(e) => recruiterForm.setData('industry', e.target.value)}
                            />
                        </Field>
                        <FormSelect
                            id="company_size"
                            label="Company size"
                            value={data.company_size}
                            onChange={(value) => recruiterForm.setData('company_size', value)}
                            error={recruiterForm.errors.company_size}
                            options={[
                                { value: '1-10', label: '1–10 employees' },
                                { value: '11-50', label: '11–50 employees' },
                                { value: '51-200', label: '51–200 employees' },
                                { value: '201-500', label: '201–500 employees' },
                                { value: '500+', label: '500+ employees' },
                            ]}
                        />
                        <Field label="Website" error={recruiterForm.errors.website}>
                            <Input
                                type="url"
                                placeholder="https://"
                                value={data.website}
                                onChange={(e) => recruiterForm.setData('website', e.target.value)}
                            />
                        </Field>
                    </>
                )}

                {step === 1 && (
                    <>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="Contact name" required error={stepErrors.contact_name ?? recruiterForm.errors.contact_name}>
                                <Input
                                    value={data.contact_name}
                                    onChange={(e) => recruiterForm.setData('contact_name', e.target.value)}
                                />
                            </Field>
                            <Field label="Designation" required error={stepErrors.designation ?? recruiterForm.errors.designation}>
                                <Input
                                    value={data.designation}
                                    onChange={(e) => recruiterForm.setData('designation', e.target.value)}
                                />
                            </Field>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="Email" required error={stepErrors.email ?? recruiterForm.errors.email}>
                                <Input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => recruiterForm.setData('email', e.target.value)}
                                />
                            </Field>
                            <Field label="Phone" required error={stepErrors.phone ?? recruiterForm.errors.phone}>
                                <Input
                                    value={data.phone}
                                    onChange={(e) => recruiterForm.setData('phone', e.target.value)}
                                />
                            </Field>
                        </div>
                    </>
                )}

                {step === 2 && (
                    <>
                        <Field label="Roles you are hiring for" required error={stepErrors.roles_hiring ?? recruiterForm.errors.roles_hiring}>
                            <Input
                                placeholder="e.g. Customer service, Hospitality, Healthcare"
                                value={data.roles_hiring}
                                onChange={(e) => recruiterForm.setData('roles_hiring', e.target.value)}
                            />
                        </Field>
                        <FormSelect
                            id="positions_count"
                            label="Number of open positions"
                            required
                            value={data.positions_count}
                            onChange={(value) => recruiterForm.setData('positions_count', value)}
                            error={stepErrors.positions_count ?? recruiterForm.errors.positions_count}
                            options={[
                                { value: '1-5', label: '1–5' },
                                { value: '6-15', label: '6–15' },
                                { value: '16-30', label: '16–30' },
                                { value: '30+', label: '30+' },
                            ]}
                        />
                        <Field label="Required skills" required error={stepErrors.required_skills ?? recruiterForm.errors.required_skills}>
                            <textarea
                                rows={3}
                                value={data.required_skills}
                                onChange={(e) => recruiterForm.setData('required_skills', e.target.value)}
                                className="border-input min-h-[88px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Language proficiency, certifications, soft skills..."
                            />
                        </Field>
                        <Field label="Preferred locations" error={recruiterForm.errors.preferred_locations}>
                            <Input
                                placeholder="Countries or cities"
                                value={data.preferred_locations}
                                onChange={(e) => recruiterForm.setData('preferred_locations', e.target.value)}
                            />
                        </Field>
                    </>
                )}

                {step === 3 && (
                    <>
                        <Field label="How did you hear about us?" error={recruiterForm.errors.heard_about}>
                            <Input
                                value={data.heard_about}
                                onChange={(e) => recruiterForm.setData('heard_about', e.target.value)}
                            />
                        </Field>
                        <FormSelect
                            id="hiring_timeline"
                            label="Hiring timeline"
                            value={data.hiring_timeline}
                            onChange={(value) => recruiterForm.setData('hiring_timeline', value)}
                            error={recruiterForm.errors.hiring_timeline}
                            options={[
                                { value: 'immediate', label: 'Immediately' },
                                { value: '1-3-months', label: '1–3 months' },
                                { value: '3-6-months', label: '3–6 months' },
                                { value: 'flexible', label: 'Flexible' },
                            ]}
                        />
                        <Field label="Additional notes" error={recruiterForm.errors.additional_notes}>
                            <textarea
                                rows={4}
                                value={data.additional_notes}
                                onChange={(e) => recruiterForm.setData('additional_notes', e.target.value)}
                                className="border-input min-h-[112px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Share any specific requirements or partnership goals..."
                            />
                        </Field>
                    </>
                )}
            </div>
        );
    }, [recruiterForm, step, stepErrors]);

    const isProcessing = role === 'recruiter' ? recruiterForm.processing : candidateForm.processing;

    const isCompactStep = role === 'candidate' && step === 0;

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent
                overlayClassName="bg-background/70 backdrop-blur-md"
                className={cn(
                    'data-[state=open]:duration-300 data-[state=closed]:duration-300 sm:max-w-xl',
                    'max-sm:fixed max-sm:inset-0 max-sm:top-0 max-sm:left-0 max-sm:h-dvh max-sm:max-h-dvh max-sm:w-full max-sm:max-w-none max-sm:translate-x-0 max-sm:translate-y-0 max-sm:rounded-none max-sm:border-0 max-sm:p-5 max-sm:gap-4 xs-short:p-3 xs-short:gap-2',
                    'max-sm:data-[state=open]:zoom-in-100 max-sm:data-[state=closed]:zoom-out-100 max-sm:data-[state=open]:slide-in-from-bottom max-sm:data-[state=closed]:slide-out-to-bottom',
                    phase === 'form'
                        ? 'flex max-h-[90vh] flex-col overflow-hidden'
                        : 'flex max-h-[90vh] flex-col overflow-hidden max-sm:overflow-hidden',
                )}
            >
                {phase === 'role' && (
                    <>
                        <DialogHeader className="shrink-0 max-sm:gap-2 max-sm:text-center">
                            <DialogTitle className="max-sm:text-xl xs-short:text-lg">Get started with Bilingual Vitae</DialogTitle>
                            <DialogDescription className="max-sm:text-sm xs-short:text-xs">
                                Tell us who you are so we can show you the right form.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 sm:grid-cols-2 max-sm:flex max-sm:min-h-0 max-sm:flex-1 max-sm:flex-col max-sm:gap-4">
                            <button
                                type="button"
                                onClick={() => selectRole('candidate')}
                                className="group rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-left transition-colors hover:border-primary/40 hover:bg-white/[0.06] max-sm:flex max-sm:flex-1 max-sm:flex-col max-sm:items-center max-sm:justify-center max-sm:p-6 max-sm:text-center xs-short:p-4"
                            >
                                <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-primary/15 text-primary max-sm:mb-4 max-sm:h-16 max-sm:w-16 xs-short:mb-3 xs-short:h-14 xs-short:w-14">
                                    <GraduationCap className="size-5 max-sm:size-8 xs-short:size-7" />
                                </div>
                                <p className="font-semibold max-sm:text-lg xs-short:text-base">I am a Candidate</p>
                                <p className="mt-2 text-sm text-muted-foreground max-sm:mt-3 max-sm:max-w-xs max-sm:text-sm xs-short:mt-2 xs-short:text-xs">
                                    Apply for training, certification, and international placement.
                                </p>
                            </button>

                            <button
                                type="button"
                                onClick={() => selectRole('recruiter')}
                                className="group rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-left transition-colors hover:border-brand-cool-light/40 hover:bg-white/[0.06] max-sm:flex max-sm:flex-1 max-sm:flex-col max-sm:items-center max-sm:justify-center max-sm:p-6 max-sm:text-center xs-short:p-4"
                            >
                                <div className="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-brand-cool-light/15 text-brand-cool-light max-sm:mb-4 max-sm:h-16 max-sm:w-16 xs-short:mb-3 xs-short:h-14 xs-short:w-14">
                                    <Briefcase className="size-5 max-sm:size-8 xs-short:size-7" />
                                </div>
                                <p className="font-semibold max-sm:text-lg xs-short:text-base">I am a Recruiter</p>
                                <p className="mt-2 text-sm text-muted-foreground max-sm:mt-3 max-sm:max-w-xs max-sm:text-sm xs-short:mt-2 xs-short:text-xs">
                                    Partner with us to access skilled, workplace-ready talent.
                                </p>
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
                            <DialogDescription className="xs-short:hidden">{currentStepMeta.description}</DialogDescription>
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
                                {role === 'candidate' ? candidateFormFields : recruiterFormFields}
                            </div>

                            <div className="mt-6 flex shrink-0 items-center justify-between gap-3 border-t border-border pt-4 xs-short:mt-2 xs-short:pt-3">
                                <Button type="button" variant="outline" className="xs-short:h-8 xs-short:px-3" onClick={handleBack}>
                                    Back
                                </Button>
                                <Button type="button" className="xs-short:h-8 xs-short:px-3" onClick={handleNext} disabled={isProcessing}>
                                    {isProcessing && <Spinner />}
                                    {step === steps.length - 1 ? 'Submit' : 'Continue'}
                                </Button>
                            </div>
                        </div>
                    </>
                )}

                {phase === 'success' && (
                    <div className="flex flex-col items-center py-6 text-center">
                        <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/15 text-primary">
                            <CheckCircle2 className="size-7" />
                        </div>
                        <DialogTitle className="text-xl">Thank you for reaching out!</DialogTitle>
                        <DialogDescription className="mt-3 max-w-sm">
                            We&apos;ve received your {role === 'candidate' ? 'application' : 'inquiry'}. You&apos;ll hear
                            from us soon.
                        </DialogDescription>
                        <Button className="mt-6" onClick={() => handleOpenChange(false)}>
                            Close
                        </Button>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
