import { useForm, usePage } from '@inertiajs/react';
import { ChevronDown, Download, ImageIcon, Upload, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/responsive-select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import {
    edit as cvEdit,
    translate as cvTranslate,
    update as cvUpdate,
} from '@/routes/student/cv';
import BasicCvProfile from '@app/components/basic-cv-profile';
import FullCvProfile from '@app/components/full-cv-profile';
import type { CvLocale } from '@app/lib/cv-labels';
import { printCvDocument } from '@app/lib/cv-print';
import type {
    CvActivityEntry,
    CvBilingualEntry,
    CvCertification,
    CvExperience,
    CvLicense,
    CvProfile,
    CvQualification,
    CvTraining,
} from '@app/types/cv';
import { BirthDatePicker } from '@shared/components/birth-date-picker';
import InputError from '@shared/components/input-error';
import { PrivateSeoHead } from '@shared/components/seo-head';
import { BRAND_NAME_TM } from '@shared/lib/brand';
import { normalizePhone, validatePhone } from '@shared/lib/phone';

const PASSPORT_PHOTO_MAX_BYTES = 5 * 1024 * 1024;
const FULL_BODY_PHOTO_MAX_BYTES = 8 * 1024 * 1024;
const DOCUMENT_MAX_BYTES = 10 * 1024 * 1024;
const ACCEPTED_PHOTO_TYPES = ['image/jpeg', 'image/png'];
const ACCEPTED_DOCUMENT_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];
const ACCEPTED_DOCUMENT_ACCEPT = 'image/jpeg,image/png,application/pdf';
const ACCEPTED_PDF_TYPES = ['application/pdf'];
const ACCEPTED_PDF_ACCEPT = 'application/pdf';
const CV_DRAFT_STORAGE_KEY = 'bv.student-cv.draft';
const CV_DRAFT_STEP_KEY = 'bv.student-cv.step';
const LANGUAGE_PROFICIENCY_LEVELS = [
    'Beginner',
    'Intermediate',
    'Professional',
    'Native',
] as const;

const LANGUAGE_TYPES = [
    'Regional',
    'Regional/Foreign',
    'Foreign Language',
] as const;

const CERTIFICATION_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as const;

/** India's 22 Eighth Schedule (scheduled) languages */
const REGIONAL_LANGUAGES_IN = [
    'Assamese',
    'Bengali',
    'Bodo',
    'Dogri',
    'Gujarati',
    'Hindi',
    'Kannada',
    'Kashmiri',
    'Konkani',
    'Maithili',
    'Malayalam',
    'Manipuri',
    'Marathi',
    'Nepali',
    'Odia',
    'Punjabi',
    'Sanskrit',
    'Santali',
    'Sindhi',
    'Tamil',
    'Telugu',
    'Urdu',
] as const;

const FOREIGN_LANGUAGE_OPTIONS = [
    'English',
    'German',
    'Japanese',
    'French',
    'Spanish',
] as const;

const LANGUAGE_PICKER_OTHER = '__other__';

const DEFAULT_LANGUAGE_TYPES: Array<(typeof LANGUAGE_TYPES)[number]> = [
    'Regional',
    'Regional/Foreign',
    'Regional/Foreign',
    'Foreign Language',
];

function isRegionalLanguage(name: string): boolean {
    return (REGIONAL_LANGUAGES_IN as readonly string[]).includes(name);
}

function isForeignLanguageOption(name: string): boolean {
    return (FOREIGN_LANGUAGE_OPTIONS as readonly string[]).includes(name);
}

function isKnownLanguageOption(name: string): boolean {
    return isRegionalLanguage(name) || isForeignLanguageOption(name);
}

function suggestedLanguageType(
    name: string,
): (typeof LANGUAGE_TYPES)[number] | null {
    if (isRegionalLanguage(name)) {
        return 'Regional';
    }

    if (isForeignLanguageOption(name)) {
        return 'Foreign Language';
    }

    return null;
}

/** Character limits aligned with UpdateStudentCvRequest */
const LIMIT = {
    short: 50,
    medium: 100,
    standard: 255,
    note: 255,
    introduction: 500,
    paragraph: 1000,
    long: 2000,
    veryLong: 5000,
} as const;

type Options = {
    civilStatuses: string[];
    genders: string[];
    states: string[];
    specialIds: Array<{ value: string; label: string }>;
};

type Props = {
    cv: CvProfile;
    options: Options;
};

type PageProps = {
    flash?: { success?: string };
};

type FormExperience = CvExperience & { certificate?: File | null };
type FormQualification = CvQualification & { certificate?: File | null };
type FormTraining = CvTraining & { certificate?: File | null };
type FormActivity = CvActivityEntry & { certificate?: File | null };
type FormCertification = CvCertification & { certificate?: File | null };
type FormLicense = CvLicense & { certificate?: File | null };
type FormBilingual = CvBilingualEntry & { certificate?: File | null };

type FormData = {
    first_name: string;
    last_name: string;
    mobile_whatsapp: string;
    date_of_birth: string;
    place_of_birth: string;
    civil_status: string;
    gender: string;
    secondary_email: string;
    secondary_mobile: string;
    current_address: string;
    permanent_address: string;
    nationality: string;
    passport_or_id_number: string;
    drivers_license: string;
    introduction: string;
    hobbies: string;
    skills: string;
    gap_reasons: string;
    current_status: string;
    pursuing_details: string;
    headline: string;
    passport_photo: File | null;
    full_body_photo: File | null;
    passport_document: File | null;
    drivers_license_document: File | null;
    vitae: {
        is_newbie: boolean;
        experiences: FormExperience[];
        qualifications: FormQualification[];
        trainings: FormTraining[];
        seminars: FormActivity[];
        presentations: FormActivity[];
        publications: FormActivity[];
        certifications: FormCertification[];
        licenses: FormLicense[];
        bilingual: FormBilingual[];
    };
};

function emptyExperience(): FormExperience {
    return {
        duration: '',
        start_date: '',
        end_date: '',
        designation: '',
        department: '',
        organization: '',
        city: '',
        state: '',
        cases_handled: '',
        bed_capacity: '',
        medical_equipment: '',
        departments_offered: '',
        accreditations: '',
        critical_cases: '',
        has_experience_letter: true,
        certificate_path: null,
        certificate: null,
    };
}

function emptyQualification(): FormQualification {
    return {
        start_date: '',
        end_date: '',
        title: '',
        college: '',
        university: '',
        city: '',
        state: '',
        country: '',
        board: '',
        language_of_instruction: '',
        certificate_path: null,
        certificate: null,
    };
}

function emptyTraining(): FormTraining {
    return {
        date: '',
        topic: '',
        organization_location: '',
        certificate_path: null,
        certificate: null,
    };
}

function emptyActivity(): FormActivity {
    return {
        date: '',
        topic: '',
        organization: '',
        location: '',
        evidence_type: null,
        certificate_path: null,
        link_url: '',
        certificate: null,
    };
}

function mapLegacyTrainingToActivity(
    item: CvTraining & { certificate?: File | null },
): FormActivity {
    return {
        ...emptyActivity(),
        date: item.date ?? '',
        topic: item.topic ?? '',
        organization: item.organization_location ?? '',
        location: '',
        evidence_type: item.certificate_path ? 'file' : null,
        certificate_path: item.certificate_path ?? null,
        certificate: null,
    };
}

function normalizeActivityList(
    items: Array<Partial<FormActivity> & Partial<CvActivityEntry>> | undefined,
    legacyTrainings?: CvTraining[],
): FormActivity[] {
    const mapped = (items ?? []).map((item) => ({
        ...emptyActivity(),
        ...item,
        evidence_type:
            item.evidence_type === 'file' || item.evidence_type === 'link'
                ? item.evidence_type
                : null,
        certificate: null,
    }));

    if (mapped.length > 0) {
        return mapped;
    }

    if (legacyTrainings && legacyTrainings.length > 0) {
        return legacyTrainings.map((item) => mapLegacyTrainingToActivity(item));
    }

    return [emptyActivity()];
}

function emptyCertification(): FormCertification {
    return {
        name: '',
        description: '',
        certificate_path: null,
        certificate: null,
    };
}

function emptyLicense(): FormLicense {
    return {
        title: '',
        registration_number: '',
        issued_date: '',
        valid_till: '',
        certificate_path: null,
        certificate: null,
    };
}

function emptyBilingual(
    languageType: (typeof LANGUAGE_TYPES)[number] | '' = '',
): FormBilingual {
    return {
        language: '',
        language_type: languageType,
        proficiency: '',
        certification_level: '',
        certificates: '',
        certificate_path: null,
        certificate: null,
    };
}

function defaultBilingualRows(): FormBilingual[] {
    return DEFAULT_LANGUAGE_TYPES.map((type) => emptyBilingual(type));
}

function ensureBilingualRows(
    items: Array<
        Partial<FormBilingual> & {
            language?: string | null;
            proficiency?: string | null;
            language_type?: string | null;
            certification_level?: string | null;
        }
    >,
): FormBilingual[] {
    const rows = items.map((item) => normalizeBilingualEntry(item));

    while (rows.length < 4) {
        rows.push(emptyBilingual(DEFAULT_LANGUAGE_TYPES[rows.length] ?? ''));
    }

    return rows;
}

function normalizeBilingualEntry(
    item: Partial<FormBilingual> & {
        language?: string | null;
        proficiency?: string | null;
        language_type?: string | null;
        certification_level?: string | null;
    },
): FormBilingual {
    const rawProficiency = String(item.proficiency ?? '').trim();
    const isLevel = (LANGUAGE_PROFICIENCY_LEVELS as readonly string[]).includes(
        rawProficiency,
    );
    const language =
        String(item.language ?? '').trim() || (isLevel ? '' : rawProficiency);

    const rawType = String(item.language_type ?? '').trim();
    const languageType = (LANGUAGE_TYPES as readonly string[]).includes(rawType)
        ? rawType
        : '';

    const rawCert = String(item.certification_level ?? '').trim();
    const certificationLevel = (
        CERTIFICATION_LEVELS as readonly string[]
    ).includes(rawCert)
        ? rawCert
        : '';

    return {
        ...emptyBilingual(),
        ...item,
        language,
        language_type: languageType,
        proficiency: isLevel ? rawProficiency : '',
        certification_level:
            languageType === 'Regional' ? '' : certificationLevel,
        certificates: item.certificates ?? '',
        certificate_path: item.certificate_path ?? null,
        certificate: null,
    };
}

function bilingualLanguagePlaceholder(index: number): string {
    if (index === 0) {
        return 'Language 1 (Regional)';
    }

    if (index === 1) {
        return 'Language 2 (Regional/Foreign)';
    }

    if (index === 2) {
        return 'Language 3 (Regional/Foreign)';
    }

    if (index === 3) {
        return 'Language 4 (Foreign Language)';
    }

    return `Language ${index + 1} (Foreign Language)`;
}

const CV_STEPS = [
    {
        id: 'personal',
        title: 'Personal',
        description:
            'Details should match your passport and academic documents. Upload a smiling passport photo and a full professional head-to-toe photo.',
    },
    {
        id: 'contact',
        title: 'Contact',
        description:
            'Current and permanent addresses, WhatsApp number, and parent/guardian/spouse contacts.',
    },
    {
        id: 'certification',
        title: 'Certification & license',
        description:
            'Add professional certifications and licenses with optional certificate uploads.',
    },
    {
        id: 'experience',
        title: 'Experience',
        description:
            'Tell us if you are a fresher or have work experience. Freshers share current plans; others add roles with dates, details, and experience certificates.',
    },
    {
        id: 'bilingual',
        title: BRAND_NAME_TM,
        description:
            'Add languages you know, select proficiency level, and upload certificates when available.',
    },
    {
        id: 'qualification',
        title: 'Qualification',
        description:
            'Highest to lowest. Mention university and college names with state and city.',
    },
    {
        id: 'training',
        title: 'Achievements',
        description:
            'Add seminars, presentations, and publications with date, topic, organisation, location, and either a certificate upload or website link.',
    },
    {
        id: 'intro',
        title: 'Introduction',
        description:
            'Write your introduction, then add skills and hobbies for the Basic CV preview.',
    },
] as const;

type CvStepId = (typeof CV_STEPS)[number]['id'];

function stripFileFields(data: FormData): FormData {
    return {
        ...data,
        passport_photo: null,
        full_body_photo: null,
        passport_document: null,
        drivers_license_document: null,
        vitae: {
            ...data.vitae,
            experiences: data.vitae.experiences.map(
                ({ certificate: _c, ...rest }) => ({
                    ...rest,
                    certificate: null,
                }),
            ),
            qualifications: data.vitae.qualifications.map(
                ({ certificate: _c, ...rest }) => ({
                    ...rest,
                    certificate: null,
                }),
            ),
            trainings: data.vitae.trainings.map(
                ({ certificate: _c, ...rest }) => ({
                    ...rest,
                    certificate: null,
                }),
            ),
            seminars: (data.vitae.seminars ?? []).map(
                ({ certificate: _c, ...rest }) => ({
                    ...rest,
                    certificate: null,
                }),
            ),
            presentations: (data.vitae.presentations ?? []).map(
                ({ certificate: _c, ...rest }) => ({
                    ...rest,
                    certificate: null,
                }),
            ),
            publications: (data.vitae.publications ?? []).map(
                ({ certificate: _c, ...rest }) => ({
                    ...rest,
                    certificate: null,
                }),
            ),
            certifications: data.vitae.certifications.map(
                ({ certificate: _c, ...rest }) => ({
                    ...rest,
                    certificate: null,
                }),
            ),
            licenses: data.vitae.licenses.map(
                ({ certificate: _c, ...rest }) => ({
                    ...rest,
                    certificate: null,
                }),
            ),
            bilingual: data.vitae.bilingual.map(
                ({ certificate: _c, ...rest }) => ({
                    ...rest,
                    certificate: null,
                }),
            ),
        },
    };
}

function readCvDraft(): FormData | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = window.localStorage.getItem(CV_DRAFT_STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as FormData;

        return stripFileFields(parsed);
    } catch {
        return null;
    }
}

function writeCvDraft(data: FormData, stepId: CvStepId): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(
        CV_DRAFT_STORAGE_KEY,
        JSON.stringify(stripFileFields(data)),
    );
    window.localStorage.setItem(CV_DRAFT_STEP_KEY, stepId);
}

function clearCvDraft(): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.removeItem(CV_DRAFT_STORAGE_KEY);
    window.localStorage.removeItem(CV_DRAFT_STEP_KEY);
}

function readCvDraftStep(): CvStepId | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const saved = window.localStorage.getItem(CV_DRAFT_STEP_KEY);

    if (!saved) {
        return null;
    }

    return CV_STEPS.some((step) => step.id === saved)
        ? (saved as CvStepId)
        : null;
}

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: React.ReactNode;
}) {
    return (
        <section className="space-y-5 sm:space-y-6 sm:rounded-2xl sm:border sm:border-border/80 sm:bg-card/40 sm:p-8 sm:shadow-sm">
            <div className="space-y-2">
                <h2 className="text-2xl font-bold tracking-tight sm:text-lg sm:font-semibold">
                    {title}
                </h2>
                {description && (
                    <p className="max-w-2xl text-sm leading-relaxed text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {children}
        </section>
    );
}

function Field({
    label,
    error,
    hint,
    children,
}: {
    label: string;
    error?: string;
    hint?: string;
    children: React.ReactNode;
}) {
    return (
        <div
            className={cn(
                'grid gap-2',
                // iOS-style cell on phones: label above a borderless control inside a rounded card
                'max-sm:gap-1 max-sm:rounded-2xl max-sm:border max-sm:border-border max-sm:bg-card/70 max-sm:px-4 max-sm:py-2.5',
                'max-sm:[&_[data-slot=input]]:border-0 max-sm:[&_[data-slot=input]]:bg-transparent max-sm:[&_[data-slot=input]]:px-0 max-sm:[&_[data-slot=input]]:shadow-none max-sm:[&_[data-slot=input]]:focus-visible:ring-0',
                'max-sm:[&_[data-slot=select-trigger]]:border-0 max-sm:[&_[data-slot=select-trigger]]:bg-transparent max-sm:[&_[data-slot=select-trigger]]:px-0 max-sm:[&_[data-slot=select-trigger]]:shadow-none',
                'max-sm:[&_textarea]:border-0 max-sm:[&_textarea]:bg-transparent max-sm:[&_textarea]:px-0 max-sm:[&_textarea]:shadow-none max-sm:[&_textarea]:focus-visible:ring-0',
            )}
        >
            <Label className="max-sm:text-xs max-sm:font-normal max-sm:text-muted-foreground">
                {label}
            </Label>
            {children}
            {hint && !error ? (
                <p className="text-xs text-muted-foreground">{hint}</p>
            ) : null}
            <InputError message={error} />
        </div>
    );
}

function sanitizeCvText(value: string): string {
    return value.replace(/\0/g, '').replace(/<[^>]*>/g, '');
}

function characterLimitHint(maxLength: number): string {
    return `Up to ${maxLength} characters`;
}

function characterLimitError(maxLength: number): string {
    return `Maximum ${maxLength} characters allowed.`;
}

function LimitedField({
    label,
    error,
    value,
    onChange,
    maxLength,
    placeholder,
    type = 'text',
    autoComplete,
    inputMode,
    className,
}: {
    label: string;
    error?: string;
    value: string;
    onChange: (value: string) => void;
    maxLength: number;
    placeholder?: string;
    type?: React.HTMLInputTypeAttribute;
    autoComplete?: string;
    inputMode?: React.HTMLAttributes<HTMLInputElement>['inputMode'];
    className?: string;
}) {
    const [limitError, setLimitError] = useState<string | undefined>();

    return (
        <Field
            label={label}
            error={limitError ?? error}
            hint={characterLimitHint(maxLength)}
        >
            <Input
                type={type}
                className={className}
                value={value}
                placeholder={placeholder}
                autoComplete={autoComplete}
                inputMode={inputMode}
                maxLength={maxLength}
                onChange={(e) => {
                    const cleaned = sanitizeCvText(e.target.value);

                    if (cleaned.length > maxLength) {
                        setLimitError(characterLimitError(maxLength));
                        onChange(cleaned.slice(0, maxLength));

                        return;
                    }

                    setLimitError(undefined);
                    onChange(cleaned);
                }}
                onPaste={(e) => {
                    const pasted = e.clipboardData.getData('text');
                    const input = e.currentTarget;
                    const start = input.selectionStart ?? value.length;
                    const end = input.selectionEnd ?? value.length;
                    const next = sanitizeCvText(
                        value.slice(0, start) + pasted + value.slice(end),
                    );

                    if (next.length > maxLength) {
                        e.preventDefault();
                        setLimitError(characterLimitError(maxLength));
                        onChange(next.slice(0, maxLength));
                    }
                }}
            />
        </Field>
    );
}

function LimitedTextarea({
    label,
    error,
    value,
    onChange,
    maxLength,
    rows = 4,
    placeholder,
}: {
    label: string;
    error?: string;
    value: string;
    onChange: (value: string) => void;
    maxLength: number;
    rows?: number;
    placeholder?: string;
}) {
    const [limitError, setLimitError] = useState<string | undefined>();

    return (
        <Field
            label={label}
            error={limitError ?? error}
            hint={characterLimitHint(maxLength)}
        >
            <Textarea
                value={value}
                rows={rows}
                placeholder={placeholder}
                maxLength={maxLength}
                onChange={(e) => {
                    const cleaned = sanitizeCvText(e.target.value);

                    if (cleaned.length > maxLength) {
                        setLimitError(characterLimitError(maxLength));
                        onChange(cleaned.slice(0, maxLength));

                        return;
                    }

                    setLimitError(undefined);
                    onChange(cleaned);
                }}
                onPaste={(e) => {
                    const pasted = e.clipboardData.getData('text');
                    const input = e.currentTarget;
                    const start = input.selectionStart ?? value.length;
                    const end = input.selectionEnd ?? value.length;
                    const next = sanitizeCvText(
                        value.slice(0, start) + pasted + value.slice(end),
                    );

                    if (next.length > maxLength) {
                        e.preventDefault();
                        setLimitError(characterLimitError(maxLength));
                        onChange(next.slice(0, maxLength));
                    }
                }}
            />
        </Field>
    );
}

function validatePhotoFile(file: File, maxBytes: number): string | null {
    if (!ACCEPTED_PHOTO_TYPES.includes(file.type)) {
        return 'Please upload a JPG or PNG image.';
    }

    if (file.size > maxBytes) {
        const maxMb = Math.round(maxBytes / (1024 * 1024));

        return `Image must be ${maxMb}MB or smaller.`;
    }

    return null;
}

function validateDocumentFile(file: File): string | null {
    if (!ACCEPTED_DOCUMENT_TYPES.includes(file.type)) {
        return 'Please upload a JPG, PNG, or PDF file.';
    }

    if (file.size > DOCUMENT_MAX_BYTES) {
        return 'File must be 10MB or smaller.';
    }

    return null;
}

function validatePdfFile(file: File): string | null {
    if (!ACCEPTED_PDF_TYPES.includes(file.type)) {
        return 'Please upload a PDF file.';
    }

    if (file.size > DOCUMENT_MAX_BYTES) {
        return 'File must be 10MB or smaller.';
    }

    return null;
}

function fileBasename(path: string | null | undefined): string | null {
    if (!path) {
        return null;
    }

    const parts = path.split('/');

    return parts[parts.length - 1] || null;
}

function TextFieldWithUpload({
    id,
    label,
    error,
    value,
    onChange,
    maxLength,
    placeholder,
    file,
    existingUrl,
    existingPath,
    onFileChange,
    onClearPending,
    accept = ACCEPTED_DOCUMENT_ACCEPT,
    hint = 'Upload a scan or photo of the document — JPG, PNG, or PDF, up to 10MB.',
    validateFile = validateDocumentFile,
}: {
    id: string;
    label: string;
    error?: string;
    value: string;
    onChange: (value: string) => void;
    maxLength: number;
    placeholder?: string;
    file: File | null;
    existingUrl: string | null;
    existingPath: string | null;
    onFileChange: (file: File | null) => void;
    onClearPending: () => void;
    accept?: string;
    hint?: string;
    validateFile?: (file: File) => string | null;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [clientError, setClientError] = useState<string | undefined>();
    const [limitError, setLimitError] = useState<string | undefined>();
    const displayName =
        file?.name ??
        fileBasename(existingPath) ??
        (existingUrl ? 'Uploaded file' : null);

    return (
        <Field
            label={label}
            error={limitError ?? clientError ?? error}
            hint={`${characterLimitHint(maxLength)}. ${hint}`}
        >
            <div className="flex gap-2">
                <Input
                    className="min-w-0 flex-1"
                    value={value}
                    maxLength={maxLength}
                    placeholder={placeholder}
                    onChange={(e) => {
                        const cleaned = sanitizeCvText(e.target.value);

                        if (cleaned.length > maxLength) {
                            setLimitError(characterLimitError(maxLength));
                            onChange(cleaned.slice(0, maxLength));

                            return;
                        }

                        setLimitError(undefined);
                        onChange(cleaned);
                    }}
                    onPaste={(e) => {
                        const pasted = e.clipboardData.getData('text');
                        const input = e.currentTarget;
                        const start = input.selectionStart ?? value.length;
                        const end = input.selectionEnd ?? value.length;
                        const next = sanitizeCvText(
                            value.slice(0, start) + pasted + value.slice(end),
                        );

                        if (next.length > maxLength) {
                            e.preventDefault();
                            setLimitError(characterLimitError(maxLength));
                            onChange(next.slice(0, maxLength));
                        }
                    }}
                />
                <Button
                    type="button"
                    variant="outline"
                    className="shrink-0 gap-1.5 max-sm:h-11 max-sm:w-full max-sm:rounded-full"
                    onClick={() => inputRef.current?.click()}
                >
                    <Upload className="size-4" />
                    Upload
                </Button>
                <input
                    ref={inputRef}
                    id={id}
                    type="file"
                    accept={accept}
                    className="sr-only"
                    onChange={(e) => {
                        const next = e.target.files?.[0] ?? null;
                        e.target.value = '';

                        if (!next) {
                            return;
                        }

                        const validationError = validateFile(next);

                        if (validationError) {
                            setClientError(validationError);
                            onFileChange(null);

                            return;
                        }

                        setClientError(undefined);
                        onFileChange(next);
                    }}
                />
            </div>
            {displayName && (
                <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                    {existingUrl && !file ? (
                        <a
                            href={existingUrl}
                            target="_blank"
                            rel="noreferrer"
                            className="truncate font-medium text-primary hover:underline"
                        >
                            {displayName}
                        </a>
                    ) : (
                        <span className="truncate">{displayName}</span>
                    )}
                    {file && (
                        <button
                            type="button"
                            onClick={() => {
                                setClientError(undefined);
                                onClearPending();
                            }}
                            className="inline-flex items-center gap-1 font-medium text-muted-foreground hover:text-foreground"
                        >
                            <X className="size-3.5" />
                            Clear
                        </button>
                    )}
                </div>
            )}
        </Field>
    );
}

function DocumentUploadField({
    id,
    label,
    error,
    file,
    existingPath,
    onFileChange,
    onClearPending,
    accept = ACCEPTED_DOCUMENT_ACCEPT,
    hint = 'Upload a scan or photo of the document — JPG, PNG, or PDF, up to 10MB.',
    validateFile = validateDocumentFile,
}: {
    id: string;
    label: string;
    error?: string;
    file: File | null;
    existingPath: string | null;
    onFileChange: (file: File | null) => void;
    onClearPending: () => void;
    accept?: string;
    hint?: string;
    validateFile?: (file: File) => string | null;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [clientError, setClientError] = useState<string | undefined>();
    const displayName = file?.name ?? fileBasename(existingPath);

    return (
        <Field label={label} error={clientError ?? error} hint={hint}>
            <div className="flex gap-2">
                <Button
                    type="button"
                    variant="outline"
                    className="shrink-0 gap-1.5"
                    onClick={() => inputRef.current?.click()}
                >
                    <Upload className="size-4" />
                    Upload
                </Button>
                <input
                    ref={inputRef}
                    id={id}
                    type="file"
                    accept={accept}
                    className="sr-only"
                    onChange={(e) => {
                        const next = e.target.files?.[0] ?? null;
                        e.target.value = '';

                        if (!next) {
                            return;
                        }

                        const validationError = validateFile(next);

                        if (validationError) {
                            setClientError(validationError);
                            onFileChange(null);

                            return;
                        }

                        setClientError(undefined);
                        onFileChange(next);
                    }}
                />
            </div>
            {displayName && (
                <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                    <span className="truncate">{displayName}</span>
                    {file && (
                        <button
                            type="button"
                            onClick={() => {
                                setClientError(undefined);
                                onClearPending();
                            }}
                            className="inline-flex items-center gap-1 font-medium text-muted-foreground hover:text-foreground"
                        >
                            <X className="size-3.5" />
                            Clear
                        </button>
                    )}
                </div>
            )}
        </Field>
    );
}

type ActivityListKey = 'seminars' | 'presentations' | 'publications';

function ActivityListEditor({
    title,
    addLabel,
    listKey,
    items,
    errors,
    onChange,
}: {
    title: string;
    addLabel: string;
    listKey: ActivityListKey;
    items: FormActivity[];
    errors: Record<string, string | undefined>;
    onChange: (items: FormActivity[]) => void;
}) {
    const updateItem = (index: number, patch: Partial<FormActivity>) => {
        const next = [...items];
        next[index] = { ...items[index], ...patch };
        onChange(next);
    };

    return (
        <div className="space-y-3">
            <h3 className="text-sm font-semibold tracking-wide text-foreground">
                {title}
            </h3>
            <div className="space-y-4">
                {items.map((item, index) => (
                    <div
                        key={index}
                        className="grid gap-4 rounded-xl border border-border/70 p-4 sm:grid-cols-2 sm:p-5"
                    >
                        <Field
                            label="Date"
                            error={errors[`vitae.${listKey}.${index}.date`]}
                        >
                            <BirthDatePicker
                                variant="compact"
                                value={item.date ?? ''}
                                onChange={(value) =>
                                    updateItem(index, { date: value })
                                }
                            />
                        </Field>
                        <LimitedField
                            label="Topic"
                            error={errors[`vitae.${listKey}.${index}.topic`]}
                            value={item.topic ?? ''}
                            maxLength={LIMIT.standard}
                            onChange={(value) =>
                                updateItem(index, { topic: value })
                            }
                        />
                        <LimitedField
                            label="Organisation"
                            error={
                                errors[`vitae.${listKey}.${index}.organization`]
                            }
                            value={item.organization ?? ''}
                            maxLength={LIMIT.standard}
                            onChange={(value) =>
                                updateItem(index, { organization: value })
                            }
                        />
                        <LimitedField
                            label="Location"
                            error={errors[`vitae.${listKey}.${index}.location`]}
                            value={item.location ?? ''}
                            maxLength={LIMIT.standard}
                            onChange={(value) =>
                                updateItem(index, { location: value })
                            }
                        />
                        <Field
                            label="Upload / link"
                            error={
                                errors[
                                    `vitae.${listKey}.${index}.evidence_type`
                                ]
                            }
                        >
                            <Select
                                value={
                                    item.evidence_type === 'file' ||
                                    item.evidence_type === 'link'
                                        ? item.evidence_type
                                        : undefined
                                }
                                onValueChange={(value) => {
                                    const evidenceType =
                                        value === 'file' || value === 'link'
                                            ? value
                                            : null;
                                    updateItem(index, {
                                        evidence_type: evidenceType,
                                        ...(evidenceType === 'file'
                                            ? { link_url: '' }
                                            : {}),
                                        ...(evidenceType === 'link'
                                            ? { certificate: null }
                                            : {}),
                                    });
                                }}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Select" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="file">
                                        Upload file
                                    </SelectItem>
                                    <SelectItem value="link">
                                        Website link
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </Field>
                        {item.evidence_type === 'file' && (
                            <DocumentUploadField
                                id={`${listKey}-certificate-${index}`}
                                label="Upload file"
                                error={
                                    errors[
                                        `vitae.${listKey}.${index}.certificate`
                                    ]
                                }
                                file={item.certificate ?? null}
                                existingPath={item.certificate_path}
                                onFileChange={(next) =>
                                    updateItem(index, { certificate: next })
                                }
                                onClearPending={() =>
                                    updateItem(index, { certificate: null })
                                }
                                hint="Upload a certificate or related document — JPG, PNG, or PDF, up to 10MB."
                            />
                        )}
                        {item.evidence_type === 'link' && (
                            <LimitedField
                                label="Website link"
                                error={
                                    errors[`vitae.${listKey}.${index}.link_url`]
                                }
                                value={item.link_url ?? ''}
                                maxLength={LIMIT.veryLong}
                                type="url"
                                placeholder="https://"
                                onChange={(value) =>
                                    updateItem(index, { link_url: value })
                                }
                            />
                        )}
                        {items.length > 1 && (
                            <div className="flex items-end sm:col-span-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() =>
                                        onChange(
                                            items.filter((_, i) => i !== index),
                                        )
                                    }
                                >
                                    Remove
                                </Button>
                            </div>
                        )}
                    </div>
                ))}
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => onChange([...items, emptyActivity()])}
                >
                    {addLabel}
                </Button>
            </div>
        </div>
    );
}

function PhotoUploadField({
    id,
    label,
    error,
    file,
    existingUrl,
    maxBytes,
    onChange,
    onClearPending,
}: {
    id: string;
    label: string;
    error?: string;
    file: File | null;
    existingUrl: string | null;
    maxBytes: number;
    onChange: (file: File | null, clientError?: string) => void;
    onClearPending: () => void;
}) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [clientError, setClientError] = useState<string | undefined>();

    useEffect(() => {
        if (!file) {
            setPreviewUrl(null);

            return;
        }

        const objectUrl = URL.createObjectURL(file);
        setPreviewUrl(objectUrl);

        return () => URL.revokeObjectURL(objectUrl);
    }, [file]);

    const displayUrl = previewUrl ?? existingUrl;
    const displayName = file?.name ?? (existingUrl ? 'Current photo' : null);
    const maxMb = Math.round(maxBytes / (1024 * 1024));
    const shownError = clientError ?? error;

    return (
        <Field label={label} error={shownError}>
            {displayUrl ? (
                <div
                    className={cn(
                        'overflow-hidden rounded-xl border bg-foreground/[0.03]',
                        shownError
                            ? 'border-destructive/60'
                            : 'border-border/80',
                    )}
                >
                    <div className="relative aspect-[3/4] max-h-56 w-full bg-muted/30 sm:aspect-square sm:max-h-48">
                        <img
                            src={displayUrl}
                            alt={label}
                            className="size-full object-cover"
                        />
                    </div>
                    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-border/60 px-3 py-2.5">
                        <p className="min-w-0 truncate text-xs text-muted-foreground">
                            {displayName}
                        </p>
                        <div className="flex shrink-0 items-center gap-2">
                            <label
                                htmlFor={id}
                                className="cursor-pointer text-xs font-medium text-primary hover:underline"
                            >
                                Replace
                            </label>
                            {file && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setClientError(undefined);
                                        onClearPending();
                                    }}
                                    className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-foreground"
                                >
                                    <X className="size-3.5" />
                                    Clear
                                </button>
                            )}
                        </div>
                    </div>
                    <input
                        id={id}
                        type="file"
                        accept="image/jpeg,image/png"
                        className="sr-only"
                        onChange={(e) => {
                            const next = e.target.files?.[0] ?? null;
                            e.target.value = '';

                            if (!next) {
                                return;
                            }

                            const validationError = validatePhotoFile(
                                next,
                                maxBytes,
                            );

                            if (validationError) {
                                setClientError(validationError);
                                onChange(null, validationError);

                                return;
                            }

                            setClientError(undefined);
                            onChange(next);
                        }}
                    />
                </div>
            ) : (
                <label
                    htmlFor={id}
                    className={cn(
                        'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed bg-foreground/[0.03] px-4 py-8 text-center transition-colors hover:border-primary/40 hover:bg-foreground/[0.05]',
                        shownError
                            ? 'border-destructive/60'
                            : 'border-foreground/15',
                    )}
                >
                    <ImageIcon className="size-8 text-muted-foreground/70" />
                    <span className="text-sm font-medium text-foreground/90">
                        Click to upload photo
                    </span>
                    <span className="text-xs text-muted-foreground">
                        JPG or PNG, up to {maxMb}MB
                    </span>
                    <input
                        id={id}
                        type="file"
                        accept="image/jpeg,image/png"
                        className="sr-only"
                        onChange={(e) => {
                            const next = e.target.files?.[0] ?? null;
                            e.target.value = '';

                            if (!next) {
                                return;
                            }

                            const validationError = validatePhotoFile(
                                next,
                                maxBytes,
                            );

                            if (validationError) {
                                setClientError(validationError);
                                onChange(null, validationError);

                                return;
                            }

                            setClientError(undefined);
                            onChange(next);
                        }}
                    />
                </label>
            )}
        </Field>
    );
}

export default function StudentCv({ cv, options }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [tab, setTab] = useState<'edit' | 'preview'>('edit');
    const [previewLocale, setPreviewLocale] = useState<CvLocale>('en');
    const [printVariant, setPrintVariant] = useState<'basic' | 'full'>('basic');
    const [translatedCv, setTranslatedCv] = useState<CvProfile | null>(null);
    const [isTranslating, setIsTranslating] = useState(false);
    const [translateError, setTranslateError] = useState<string | null>(null);
    const [downloadMenuOpen, setDownloadMenuOpen] = useState(false);
    const downloadMenuRef = useRef<HTMLDivElement>(null);
    const [stepId, setStepId] = useState<CvStepId>(
        () => readCvDraftStep() ?? 'personal',
    );
    const [draftNotice, setDraftNotice] = useState(false);

    const stepIndex = CV_STEPS.findIndex((step) => step.id === stepId);
    const currentStep = CV_STEPS[stepIndex] ?? CV_STEPS[0];
    const isFirstStep = stepIndex <= 0;
    const isLastStep = stepIndex >= CV_STEPS.length - 1;

    const goNext = () => {
        if (!isLastStep) {
            setStepId(CV_STEPS[stepIndex + 1].id);
        }
    };

    const goBack = () => {
        if (!isFirstStep) {
            setStepId(CV_STEPS[stepIndex - 1].id);
        }
    };

    const initial = useMemo<FormData>(() => {
        const fromServer: FormData = {
            first_name: cv.first_name ?? '',
            last_name: cv.last_name ?? '',
            mobile_whatsapp: cv.mobile_whatsapp ?? '',
            date_of_birth: cv.date_of_birth ?? '',
            place_of_birth: cv.place_of_birth ?? '',
            civil_status: cv.civil_status ?? '',
            gender: cv.gender ?? '',
            secondary_email: cv.secondary_email ?? '',
            secondary_mobile: cv.secondary_mobile ?? '',
            current_address: cv.current_address ?? '',
            permanent_address: cv.permanent_address ?? '',
            nationality: cv.nationality ?? '',
            passport_or_id_number: cv.passport_or_id_number ?? '',
            drivers_license: cv.drivers_license ?? '',
            introduction: cv.introduction ?? '',
            hobbies: cv.hobbies ?? '',
            skills: (cv.skills ?? []).join(', '),
            gap_reasons: cv.gap_reasons ?? '',
            current_status: cv.current_status ?? '',
            pursuing_details: cv.pursuing_details ?? '',
            headline: cv.headline ?? '',
            passport_photo: null,
            full_body_photo: null,
            passport_document: null,
            drivers_license_document: null,
            vitae: {
                is_newbie: cv.vitae.is_newbie,
                experiences:
                    cv.vitae.experiences.length > 0
                        ? cv.vitae.experiences.map((item) => ({
                              ...emptyExperience(),
                              ...item,
                              certificate: null,
                          }))
                        : cv.vitae.is_newbie
                          ? []
                          : [emptyExperience()],
                qualifications:
                    cv.vitae.qualifications.length > 0
                        ? cv.vitae.qualifications.map((item) => ({
                              ...emptyQualification(),
                              ...item,
                              certificate: null,
                          }))
                        : [emptyQualification()],
                trainings:
                    cv.vitae.trainings.length > 0
                        ? cv.vitae.trainings.map((item) => ({
                              ...emptyTraining(),
                              ...item,
                              certificate: null,
                          }))
                        : [],
                seminars: normalizeActivityList(
                    cv.vitae.seminars,
                    cv.vitae.trainings,
                ),
                presentations: normalizeActivityList(cv.vitae.presentations),
                publications: normalizeActivityList(cv.vitae.publications),
                certifications:
                    (cv.vitae.certifications ?? []).length > 0
                        ? cv.vitae.certifications.map((item) => ({
                              ...emptyCertification(),
                              ...item,
                              certificate: null,
                          }))
                        : [emptyCertification()],
                licenses:
                    cv.vitae.licenses.length > 0
                        ? cv.vitae.licenses.map((item) => ({
                              ...emptyLicense(),
                              ...item,
                              certificate: null,
                          }))
                        : [emptyLicense()],
                bilingual:
                    cv.vitae.bilingual.length > 0
                        ? ensureBilingualRows(cv.vitae.bilingual)
                        : defaultBilingualRows(),
            },
        };

        const draft = readCvDraft();

        if (!draft) {
            return fromServer;
        }

        return {
            ...fromServer,
            ...draft,
            passport_photo: null,
            full_body_photo: null,
            passport_document: null,
            drivers_license_document: null,
            vitae: {
                ...fromServer.vitae,
                ...draft.vitae,
                experiences: (draft.vitae.experiences ?? []).map((item) => ({
                    ...emptyExperience(),
                    ...item,
                    certificate: null,
                })),
                qualifications: (draft.vitae.qualifications ?? []).map(
                    (item) => ({
                        ...emptyQualification(),
                        ...item,
                        certificate: null,
                    }),
                ),
                trainings: (draft.vitae.trainings ?? []).map((item) => ({
                    ...emptyTraining(),
                    ...item,
                    certificate: null,
                })),
                seminars: normalizeActivityList(
                    draft.vitae.seminars,
                    draft.vitae.trainings,
                ),
                presentations: normalizeActivityList(draft.vitae.presentations),
                publications: normalizeActivityList(draft.vitae.publications),
                certifications:
                    (draft.vitae.certifications ?? []).length > 0
                        ? draft.vitae.certifications.map((item) => ({
                              ...emptyCertification(),
                              ...item,
                              certificate: null,
                          }))
                        : [emptyCertification()],
                licenses:
                    (draft.vitae.licenses ?? []).length > 0
                        ? draft.vitae.licenses.map((item) => ({
                              ...emptyLicense(),
                              ...item,
                              certificate: null,
                          }))
                        : [emptyLicense()],
                bilingual:
                    (draft.vitae.bilingual ?? []).length > 0
                        ? ensureBilingualRows(draft.vitae.bilingual)
                        : fromServer.vitae.bilingual,
            },
        };
    }, [cv]);

    const form = useForm<FormData>('student-cv', initial).dontRemember(
        'passport_photo',
        'full_body_photo',
        'passport_document',
        'drivers_license_document',
    );

    useEffect(() => {
        const vitae = form.data.vitae;
        const needsCertifications = vitae.certifications.length === 0;
        const needsLicenses = vitae.licenses.length === 0;
        const needsExperience =
            !vitae.is_newbie && vitae.experiences.length === 0;

        const needsBilingual = vitae.bilingual.length < 4;
        const needsQualifications = vitae.qualifications.length === 0;

        if (
            !needsCertifications &&
            !needsLicenses &&
            !needsExperience &&
            !needsBilingual &&
            !needsQualifications
        ) {
            return;
        }

        form.setData('vitae', {
            ...vitae,
            certifications: needsCertifications
                ? [emptyCertification()]
                : vitae.certifications,
            licenses: needsLicenses ? [emptyLicense()] : vitae.licenses,
            experiences: needsExperience
                ? [emptyExperience()]
                : vitae.experiences,
            bilingual: needsBilingual
                ? ensureBilingualRows(vitae.bilingual)
                : vitae.bilingual,
            qualifications: needsQualifications
                ? [emptyQualification()]
                : vitae.qualifications,
        });
    }, [
        form.data.vitae.certifications.length,
        form.data.vitae.licenses.length,
        form.data.vitae.experiences.length,
        form.data.vitae.bilingual.length,
        form.data.vitae.qualifications.length,
        form.data.vitae.is_newbie,
    ]);

    useEffect(() => {
        if (readCvDraft()) {
            setDraftNotice(true);
        }
    }, []);

    useEffect(() => {
        writeCvDraft(form.data, stepId);
    }, [form.data, stepId]);

    const completionPercent = useMemo(() => {
        const data = form.data;
        const filled = (value: unknown): boolean => {
            if (typeof value === 'string') {
                return value.trim().length > 0;
            }

            return value !== null && value !== undefined && value !== false;
        };

        const hasPhoto =
            data.passport_photo !== null || Boolean(cv.passport_photo_url);

        const sectionDone = [
            // Personal
            filled(data.first_name) &&
                filled(data.last_name) &&
                filled(data.date_of_birth) &&
                filled(data.place_of_birth) &&
                filled(data.civil_status) &&
                filled(data.gender) &&
                hasPhoto,
            // Contact
            filled(data.mobile_whatsapp) &&
                filled(data.current_address) &&
                filled(data.permanent_address),
            // Certification
            data.vitae.certifications.some((cert) => filled(cert.name)) ||
                data.vitae.licenses.some((license) => filled(license.title)),
            // Experience
            data.vitae.is_newbie ||
                data.vitae.experiences.some(
                    (job) =>
                        filled(job.organization) && filled(job.designation),
                ),
            // Bilingual Vitae
            data.vitae.bilingual.filter((entry) => filled(entry.language))
                .length >= 2,
            // Qualification
            data.vitae.qualifications.some((item) => filled(item.title)),
            // Seminar / presentation / publication
            [
                ...(data.vitae.seminars ?? []),
                ...(data.vitae.presentations ?? []),
                ...(data.vitae.publications ?? []),
            ].some((item) => filled(item.topic)) ||
                data.vitae.trainings.some((item) => filled(item.topic)),
            // Introduction
            filled(data.introduction) && filled(data.skills),
        ];

        const completed = sectionDone.filter(Boolean).length;

        return Math.round((completed / sectionDone.length) * 100);
    }, [form.data, cv.passport_photo_url]);

    const previewCv: CvProfile = {
        ...cv,
        first_name: form.data.first_name || cv.first_name,
        last_name: form.data.last_name || cv.last_name,
        name:
            `${form.data.first_name} ${form.data.last_name}`.trim() || cv.name,
        mobile_whatsapp: form.data.mobile_whatsapp || null,
        date_of_birth: form.data.date_of_birth || null,
        place_of_birth: form.data.place_of_birth || null,
        civil_status: form.data.civil_status || null,
        gender: form.data.gender || null,
        secondary_email: form.data.secondary_email || null,
        secondary_mobile: form.data.secondary_mobile || null,
        current_address: form.data.current_address || null,
        permanent_address: form.data.permanent_address || null,
        nationality: form.data.nationality || null,
        passport_or_id_number: form.data.passport_or_id_number || null,
        drivers_license: form.data.drivers_license || null,
        introduction: form.data.introduction || null,
        hobbies: form.data.hobbies || null,
        skills: String(form.data.skills ?? '')
            .split(',')
            .map((skill) => skill.trim())
            .filter(Boolean),
        gap_reasons: form.data.gap_reasons || null,
        current_status: form.data.current_status || null,
        pursuing_details: form.data.pursuing_details || null,
        headline: form.data.headline || null,
        passport_photo_url: form.data.passport_photo
            ? URL.createObjectURL(form.data.passport_photo)
            : cv.passport_photo_url,
        vitae: {
            is_newbie: form.data.vitae.is_newbie,
            experiences: (form.data.vitae.experiences ?? []).map(
                ({ certificate: _c, ...rest }) => rest,
            ),
            qualifications: (form.data.vitae.qualifications ?? []).map(
                ({ certificate: _c, ...rest }) => rest,
            ),
            trainings: (form.data.vitae.trainings ?? []).map(
                ({ certificate: _c, ...rest }) => rest,
            ),
            seminars: (form.data.vitae.seminars ?? []).map(
                ({ certificate: _c, ...rest }) => rest,
            ),
            presentations: (form.data.vitae.presentations ?? []).map(
                ({ certificate: _c, ...rest }) => rest,
            ),
            publications: (form.data.vitae.publications ?? []).map(
                ({ certificate: _c, ...rest }) => rest,
            ),
            certifications: (form.data.vitae.certifications ?? []).map(
                ({ certificate: _c, ...rest }) => rest,
            ),
            licenses: (form.data.vitae.licenses ?? []).map(
                ({ certificate: _c, ...rest }) => rest,
            ),
            bilingual: (form.data.vitae.bilingual ?? []).map(
                ({ certificate: _c, ...rest }) => rest,
            ),
        },
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const uploadErrors: Partial<Record<keyof FormData, string>> = {};

        if (form.data.passport_photo) {
            const message = validatePhotoFile(
                form.data.passport_photo,
                PASSPORT_PHOTO_MAX_BYTES,
            );

            if (message) {
                uploadErrors.passport_photo = message;
            }
        }

        if (form.data.full_body_photo) {
            const message = validatePhotoFile(
                form.data.full_body_photo,
                FULL_BODY_PHOTO_MAX_BYTES,
            );

            if (message) {
                uploadErrors.full_body_photo = message;
            }
        }

        if (form.data.passport_document) {
            const message = validatePdfFile(form.data.passport_document);

            if (message) {
                uploadErrors.passport_document = message;
            }
        }

        if (form.data.drivers_license_document) {
            const message = validateDocumentFile(
                form.data.drivers_license_document,
            );

            if (message) {
                uploadErrors.drivers_license_document = message;
            }
        }

        const phoneErrors: Partial<Record<keyof FormData, string>> = {};
        const whatsappError = validatePhone(form.data.mobile_whatsapp);

        if (whatsappError) {
            phoneErrors.mobile_whatsapp = whatsappError;
        }

        const secondaryMobileError = validatePhone(form.data.secondary_mobile);

        if (secondaryMobileError) {
            phoneErrors.secondary_mobile = secondaryMobileError;
        }

        if (Object.keys(uploadErrors).length > 0) {
            form.clearErrors();
            form.setError({ ...uploadErrors, ...phoneErrors });
            setStepId('personal');
            setTab('edit');

            return;
        }

        if (Object.keys(phoneErrors).length > 0) {
            form.clearErrors();
            form.setError(phoneErrors);
            setStepId('contact');
            setTab('edit');

            return;
        }

        form.transform((data) => ({
            ...data,
            mobile_whatsapp: normalizePhone(data.mobile_whatsapp),
            secondary_mobile: normalizePhone(data.secondary_mobile),
        }));
        form.post(cvUpdate.url(), {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                clearCvDraft();
                setDraftNotice(false);
                form.setDefaults(stripFileFields(form.data));
                setTab('preview');
            },
            onError: (errors) => {
                const personalErrorKeys = [
                    'first_name',
                    'last_name',
                    'date_of_birth',
                    'place_of_birth',
                    'civil_status',
                    'gender',
                    'nationality',
                    'passport_or_id_number',
                    'drivers_license',
                    'passport_photo',
                    'full_body_photo',
                    'passport_document',
                    'drivers_license_document',
                ];
                const contactErrorKeys = [
                    'mobile_whatsapp',
                    'secondary_email',
                    'secondary_mobile',
                    'current_address',
                    'permanent_address',
                ];

                if (
                    personalErrorKeys.some((key) =>
                        Object.prototype.hasOwnProperty.call(errors, key),
                    )
                ) {
                    setStepId('personal');
                    setTab('edit');
                } else if (
                    contactErrorKeys.some((key) =>
                        Object.prototype.hasOwnProperty.call(errors, key),
                    )
                ) {
                    setStepId('contact');
                    setTab('edit');
                }
            },
        });
    };

    const fetchGermanCv = async (): Promise<CvProfile> => {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

        const response = await fetch(cvTranslate.url(), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ locale: 'de' }),
        });

        if (!response.ok) {
            let message =
                'Unable to prepare the German CV right now. Please try again.';

            try {
                const payload = (await response.json()) as {
                    message?: string;
                };

                if (payload.message) {
                    message = payload.message;
                }
            } catch {
                // keep default message
            }

            throw new Error(message);
        }

        const payload = (await response.json()) as { cv: CvProfile };

        return payload.cv;
    };

    const ensureGermanCv = async (): Promise<CvProfile> => {
        setIsTranslating(true);
        setTranslateError(null);

        try {
            const germanCv = await fetchGermanCv();
            setTranslatedCv(germanCv);

            return germanCv;
        } catch (error) {
            const message =
                error instanceof Error
                    ? error.message
                    : 'Unable to prepare the German CV right now. Please try again.';
            setTranslateError(message);

            throw error;
        } finally {
            setIsTranslating(false);
        }
    };

    const ensureGermanPreview = async (): Promise<CvProfile> => {
        const germanCv = await ensureGermanCv();
        setPreviewLocale('de');
        setPrintVariant('basic');
        setTab('preview');

        return germanCv;
    };

    const printCv = () => {
        printCvDocument(printVariant);
    };

    const handleDownloadBasicEnglish = () => {
        setDownloadMenuOpen(false);
        setPreviewLocale('en');
        setPrintVariant('basic');
        setTranslateError(null);
        setTab('preview');
        printCv();
    };

    const handleDownloadBasicGerman = async () => {
        setDownloadMenuOpen(false);

        try {
            await ensureGermanPreview();
            printCv();
        } catch {
            // error already stored in translateError
        }
    };

    const handleDownloadFullEnglish = () => {
        setDownloadMenuOpen(false);
        setPreviewLocale('en');
        setPrintVariant('full');
        setTranslateError(null);
        printCv();
    };

    const handleDownloadFullGerman = async () => {
        setDownloadMenuOpen(false);

        try {
            await ensureGermanCv();
            setPreviewLocale('de');
            setPrintVariant('full');
            printCv();
        } catch {
            // error already stored in translateError
        }
    };

    const handlePreviewLocale = async (locale: CvLocale) => {
        if (locale === 'en') {
            setPreviewLocale('en');
            setPrintVariant('basic');
            setTranslateError(null);
            setTab('preview');

            return;
        }

        try {
            await ensureGermanPreview();
        } catch {
            // error already stored in translateError
        }
    };

    useEffect(() => {
        if (!downloadMenuOpen) {
            return;
        }

        const onPointerDown = (event: MouseEvent) => {
            if (
                downloadMenuRef.current &&
                !downloadMenuRef.current.contains(event.target as Node)
            ) {
                setDownloadMenuOpen(false);
            }
        };

        document.addEventListener('mousedown', onPointerDown);

        return () => document.removeEventListener('mousedown', onPointerDown);
    }, [downloadMenuOpen]);

    const displayCv =
        previewLocale === 'de' && translatedCv ? translatedCv : previewCv;

    return (
        <>
            <PrivateSeoHead title="Your CV" />

            <div
                data-cv-print={printVariant}
                className="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-6 sm:px-6 lg:px-10 print:max-w-none print:gap-0 print:p-0"
            >
                <div className="cv-print-hide flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Your CV
                        </h1>
                        <p className="max-w-xl text-sm leading-relaxed text-muted-foreground">
                            Complete one section at a time. Recruiters and
                            trainers will see your Basic CV preview.
                        </p>
                        <div className="max-w-md space-y-2 pt-1">
                            <div className="flex items-center justify-between gap-3 text-xs">
                                <span className="font-medium text-muted-foreground">
                                    Profile completion
                                </span>
                                <span className="font-semibold text-foreground tabular-nums">
                                    {completionPercent}%
                                </span>
                            </div>
                            <div
                                className="h-2 overflow-hidden rounded-full bg-muted"
                                role="progressbar"
                                aria-valuenow={completionPercent}
                                aria-valuemin={0}
                                aria-valuemax={100}
                                aria-label="CV profile completion"
                            >
                                <div
                                    className="h-full rounded-full bg-primary transition-[width] duration-300 ease-out"
                                    style={{ width: `${completionPercent}%` }}
                                />
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="inline-flex rounded-xl border border-border p-1">
                            <button
                                type="button"
                                className={cn(
                                    'rounded-lg px-4 py-2 text-sm font-medium',
                                    tab === 'edit'
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                                onClick={() => setTab('edit')}
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                className={cn(
                                    'rounded-lg px-4 py-2 text-sm font-medium',
                                    tab === 'preview'
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                                onClick={() => {
                                    setPrintVariant('basic');
                                    setTab('preview');
                                }}
                            >
                                Preview
                            </button>
                        </div>
                        {completionPercent === 100 && (
                            <div ref={downloadMenuRef} className="relative">
                                <button
                                    type="button"
                                    onClick={() =>
                                        setDownloadMenuOpen((open) => !open)
                                    }
                                    disabled={isTranslating}
                                    className="inline-flex items-center gap-2 rounded-xl border border-border px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-muted disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {isTranslating ? (
                                        <Spinner className="size-4" />
                                    ) : (
                                        <Download className="size-4" />
                                    )}
                                    {isTranslating
                                        ? 'Preparing German CV…'
                                        : 'Download'}
                                    <ChevronDown className="size-4 opacity-70" />
                                </button>
                                {downloadMenuOpen && !isTranslating && (
                                    <div className="absolute end-0 z-20 mt-2 w-56 overflow-hidden rounded-xl border border-border bg-background shadow-lg">
                                        <p className="px-4 pt-2.5 pb-1 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                                            Basic CV
                                        </p>
                                        <button
                                            type="button"
                                            onClick={handleDownloadBasicEnglish}
                                            className="flex w-full px-4 py-2.5 text-left text-sm hover:bg-muted"
                                        >
                                            English
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                void handleDownloadBasicGerman();
                                            }}
                                            className="flex w-full px-4 py-2.5 text-left text-sm hover:bg-muted"
                                        >
                                            German
                                        </button>
                                        <div className="my-1 border-t border-border" />
                                        <p className="px-4 pt-2 pb-1 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                                            Full CV
                                        </p>
                                        <button
                                            type="button"
                                            onClick={handleDownloadFullEnglish}
                                            className="flex w-full px-4 py-2.5 text-left text-sm hover:bg-muted"
                                        >
                                            English
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                void handleDownloadFullGerman();
                                            }}
                                            className="flex w-full px-4 py-2.5 pb-3 text-left text-sm hover:bg-muted"
                                        >
                                            German
                                        </button>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                {tab === 'preview' && (
                    <div className="cv-print-hide flex flex-wrap items-center gap-2">
                        <span className="text-xs font-medium text-muted-foreground">
                            Preview language
                        </span>
                        <div className="inline-flex rounded-xl border border-border p-1">
                            <button
                                type="button"
                                disabled={isTranslating}
                                className={cn(
                                    'rounded-lg px-3 py-1.5 text-xs font-medium',
                                    previewLocale === 'en'
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                                onClick={() => {
                                    void handlePreviewLocale('en');
                                }}
                            >
                                EN
                            </button>
                            <button
                                type="button"
                                disabled={isTranslating}
                                className={cn(
                                    'rounded-lg px-3 py-1.5 text-xs font-medium',
                                    previewLocale === 'de'
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                                onClick={() => {
                                    void handlePreviewLocale('de');
                                }}
                            >
                                DE
                            </button>
                        </div>
                    </div>
                )}

                {translateError && (
                    <p className="cv-print-hide rounded-xl border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {translateError}
                    </p>
                )}

                {flash?.success && (
                    <p className="cv-print-hide rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                        {flash.success}
                    </p>
                )}

                {draftNotice && tab === 'edit' && (
                    <p className="cv-print-hide rounded-xl border border-border/80 bg-card/50 px-4 py-3 text-sm text-muted-foreground">
                        Your unsaved CV answers were restored. Re-select any
                        photos or documents you had chosen before refreshing —
                        browsers cannot keep those automatically.
                    </p>
                )}

                {tab === 'preview' ? (
                    <BasicCvProfile cv={displayCv} locale={previewLocale} />
                ) : (
                    <form onSubmit={submit} className="cv-print-hide space-y-6">
                        <nav
                            aria-label="CV sections"
                            className="sticky top-[var(--app-top-h,0px)] z-20 -mx-2 border-b border-border/60 bg-background/90 px-2 py-3 backdrop-blur-xl sm:static sm:mx-0 sm:rounded-2xl sm:border sm:border-border/80 sm:bg-card/30 sm:p-4 sm:backdrop-blur-none"
                        >
                            <div className="mb-2 flex items-center justify-between px-1 text-xs font-medium text-muted-foreground">
                                <span className="tracking-wide uppercase">
                                    Section {stepIndex + 1} of {CV_STEPS.length}
                                </span>
                                <span>
                                    {Math.round(
                                        ((stepIndex + 1) / CV_STEPS.length) *
                                            100,
                                    )}
                                    %
                                </span>
                            </div>
                            <div
                                className="mb-3 h-1.5 overflow-hidden rounded-full bg-muted"
                                role="progressbar"
                                aria-valuemin={0}
                                aria-valuemax={CV_STEPS.length}
                                aria-valuenow={stepIndex + 1}
                            >
                                <div
                                    className="h-full rounded-full bg-primary transition-all duration-300"
                                    style={{
                                        width: `${((stepIndex + 1) / CV_STEPS.length) * 100}%`,
                                    }}
                                />
                            </div>
                            <div className="-mx-2 flex scrollbar-none gap-2 overflow-x-auto px-2 pb-1 sm:mx-0 sm:px-0">
                                {CV_STEPS.map((step, index) => {
                                    const active = step.id === stepId;
                                    const done = index < stepIndex;

                                    return (
                                        <button
                                            key={step.id}
                                            type="button"
                                            ref={(node) => {
                                                if (active && node) {
                                                    node.scrollIntoView({
                                                        inline: 'center',
                                                        block: 'nearest',
                                                    });
                                                }
                                            }}
                                            onClick={() => {
                                                setStepId(step.id);
                                                window.scrollTo({ top: 0 });
                                            }}
                                            className={cn(
                                                'shrink-0 rounded-full border px-4 py-2 text-sm font-medium transition-colors active:scale-95',
                                                active
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : done
                                                      ? 'border-primary/40 bg-primary/10 text-foreground'
                                                      : 'border-border text-muted-foreground hover:border-foreground/30 hover:text-foreground',
                                            )}
                                        >
                                            {step.title}
                                        </button>
                                    );
                                })}
                            </div>
                        </nav>

                        {stepId === 'personal' && (
                            <Section
                                title={currentStep.title}
                                description={currentStep.description}
                            >
                                <div className="grid gap-5 sm:grid-cols-2 sm:gap-6">
                                    <LimitedField
                                        label="First name"
                                        error={form.errors.first_name}
                                        value={form.data.first_name}
                                        maxLength={LIMIT.standard}
                                        onChange={(value) =>
                                            form.setData('first_name', value)
                                        }
                                    />
                                    <LimitedField
                                        label="Last name"
                                        error={form.errors.last_name}
                                        value={form.data.last_name}
                                        maxLength={LIMIT.standard}
                                        onChange={(value) =>
                                            form.setData('last_name', value)
                                        }
                                    />
                                    <Field
                                        label="Date of birth"
                                        error={form.errors.date_of_birth}
                                    >
                                        <BirthDatePicker
                                            variant="compact"
                                            value={form.data.date_of_birth}
                                            onChange={(value) =>
                                                form.setData(
                                                    'date_of_birth',
                                                    value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <LimitedField
                                        label="Place of birth (City, State)"
                                        error={form.errors.place_of_birth}
                                        value={form.data.place_of_birth}
                                        maxLength={LIMIT.standard}
                                        onChange={(value) =>
                                            form.setData(
                                                'place_of_birth',
                                                value,
                                            )
                                        }
                                    />
                                    <Field
                                        label="Civil status"
                                        error={form.errors.civil_status}
                                    >
                                        <Select
                                            value={
                                                form.data.civil_status ||
                                                undefined
                                            }
                                            onValueChange={(value) =>
                                                form.setData(
                                                    'civil_status',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.civilStatuses.map(
                                                    (status) => (
                                                        <SelectItem
                                                            key={status}
                                                            value={status}
                                                        >
                                                            {status}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field
                                        label="Gender"
                                        error={form.errors.gender}
                                    >
                                        <Select
                                            value={
                                                form.data.gender || undefined
                                            }
                                            onValueChange={(value) =>
                                                form.setData('gender', value)
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.genders.map(
                                                    (gender) => (
                                                        <SelectItem
                                                            key={gender}
                                                            value={gender}
                                                        >
                                                            {gender}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <LimitedField
                                        label="Nationality"
                                        error={form.errors.nationality}
                                        value={form.data.nationality}
                                        maxLength={LIMIT.medium}
                                        onChange={(value) =>
                                            form.setData('nationality', value)
                                        }
                                    />
                                    <TextFieldWithUpload
                                        id="passport-document"
                                        label="Passport / ID number"
                                        error={
                                            form.errors.passport_or_id_number ??
                                            form.errors.passport_document
                                        }
                                        value={form.data.passport_or_id_number}
                                        maxLength={LIMIT.medium}
                                        onChange={(value) =>
                                            form.setData(
                                                'passport_or_id_number',
                                                value,
                                            )
                                        }
                                        file={form.data.passport_document}
                                        existingUrl={cv.passport_document_url}
                                        existingPath={cv.passport_document_path}
                                        onFileChange={(file) =>
                                            form.setData(
                                                'passport_document',
                                                file,
                                            )
                                        }
                                        onClearPending={() =>
                                            form.setData(
                                                'passport_document',
                                                null,
                                            )
                                        }
                                        accept={ACCEPTED_PDF_ACCEPT}
                                        hint="Upload a PDF of your passport or ID — PDF only, up to 10MB."
                                        validateFile={validatePdfFile}
                                    />
                                    <TextFieldWithUpload
                                        id="drivers-license-document"
                                        label="Driver's license"
                                        error={
                                            form.errors.drivers_license ??
                                            form.errors.drivers_license_document
                                        }
                                        value={form.data.drivers_license}
                                        maxLength={LIMIT.standard}
                                        placeholder="Number and 2W/4W if any"
                                        onChange={(value) =>
                                            form.setData(
                                                'drivers_license',
                                                value,
                                            )
                                        }
                                        file={
                                            form.data.drivers_license_document
                                        }
                                        existingUrl={
                                            cv.drivers_license_document_url
                                        }
                                        existingPath={
                                            cv.drivers_license_document_path
                                        }
                                        onFileChange={(file) =>
                                            form.setData(
                                                'drivers_license_document',
                                                file,
                                            )
                                        }
                                        onClearPending={() =>
                                            form.setData(
                                                'drivers_license_document',
                                                null,
                                            )
                                        }
                                    />
                                    <Field label="Candidate ID">
                                        <Input
                                            value={cv.candidate_id ?? ''}
                                            disabled
                                        />
                                    </Field>{' '}
                                    <PhotoUploadField
                                        id="passport-photo"
                                        label="Passport-size photo"
                                        error={form.errors.passport_photo}
                                        file={form.data.passport_photo}
                                        existingUrl={cv.passport_photo_url}
                                        maxBytes={PASSPORT_PHOTO_MAX_BYTES}
                                        onChange={(file) =>
                                            form.setData('passport_photo', file)
                                        }
                                        onClearPending={() =>
                                            form.setData('passport_photo', null)
                                        }
                                    />
                                    <PhotoUploadField
                                        id="full-body-photo"
                                        label="Full professional photo (head to toe)"
                                        error={form.errors.full_body_photo}
                                        file={form.data.full_body_photo}
                                        existingUrl={cv.full_body_photo_url}
                                        maxBytes={FULL_BODY_PHOTO_MAX_BYTES}
                                        onChange={(file) =>
                                            form.setData(
                                                'full_body_photo',
                                                file,
                                            )
                                        }
                                        onClearPending={() =>
                                            form.setData(
                                                'full_body_photo',
                                                null,
                                            )
                                        }
                                    />
                                </div>
                            </Section>
                        )}

                        {stepId === 'contact' && (
                            <Section
                                title={CV_STEPS[1].title}
                                description={CV_STEPS[1].description}
                            >
                                <div className="space-y-8">
                                    <div className="grid gap-5 sm:grid-cols-2 sm:gap-6">
                                        <Field label="Email ID">
                                            <Input value={cv.email} disabled />
                                            <p className="text-xs text-muted-foreground">
                                                To change your email, go to
                                                Profile settings.
                                            </p>
                                        </Field>
                                        <LimitedField
                                            label="Mobile (WhatsApp no.)"
                                            error={form.errors.mobile_whatsapp}
                                            value={form.data.mobile_whatsapp}
                                            maxLength={LIMIT.short}
                                            inputMode="tel"
                                            autoComplete="tel"
                                            placeholder="e.g. +919832581198"
                                            onChange={(value) =>
                                                form.setData(
                                                    'mobile_whatsapp',
                                                    value,
                                                )
                                            }
                                        />
                                        <p className="-mt-3 text-xs text-muted-foreground sm:col-span-2">
                                            Include country code with + (e.g.
                                            +91 for India).
                                        </p>
                                        <LimitedTextarea
                                            label="Current address"
                                            error={form.errors.current_address}
                                            value={form.data.current_address}
                                            maxLength={LIMIT.long}
                                            onChange={(value) =>
                                                form.setData(
                                                    'current_address',
                                                    value,
                                                )
                                            }
                                        />
                                        <LimitedTextarea
                                            label="Permanent address"
                                            error={
                                                form.errors.permanent_address
                                            }
                                            value={form.data.permanent_address}
                                            maxLength={LIMIT.long}
                                            onChange={(value) =>
                                                form.setData(
                                                    'permanent_address',
                                                    value,
                                                )
                                            }
                                        />
                                    </div>

                                    <div className="space-y-5">
                                        <h3 className="text-base font-semibold tracking-tight">
                                            Emergency Contact -
                                            parent/guardian/spouse (email Id)
                                            and their (mobile no.)
                                        </h3>
                                        <div className="grid gap-5 sm:grid-cols-2 sm:gap-6">
                                            <LimitedField
                                                label="(email Id)"
                                                error={
                                                    form.errors.secondary_email
                                                }
                                                type="email"
                                                value={
                                                    form.data.secondary_email
                                                }
                                                maxLength={LIMIT.standard}
                                                onChange={(value) =>
                                                    form.setData(
                                                        'secondary_email',
                                                        value,
                                                    )
                                                }
                                            />
                                            <LimitedField
                                                label="(mobile no.)"
                                                error={
                                                    form.errors.secondary_mobile
                                                }
                                                value={
                                                    form.data.secondary_mobile
                                                }
                                                maxLength={LIMIT.short}
                                                inputMode="tel"
                                                autoComplete="tel"
                                                placeholder="e.g. +919832581198"
                                                onChange={(value) =>
                                                    form.setData(
                                                        'secondary_mobile',
                                                        value,
                                                    )
                                                }
                                            />
                                        </div>
                                    </div>
                                </div>
                            </Section>
                        )}

                        {stepId === 'certification' && (
                            <>
                                <Section
                                    title="Professional certification"
                                    description="Name, short description, and optional certificate upload for each professional certification."
                                >
                                    <div className="space-y-3">
                                        {form.data.vitae.certifications.map(
                                            (cert, index) => (
                                                <div
                                                    key={index}
                                                    className="grid gap-3 rounded-lg border border-border/70 p-3 sm:grid-cols-2"
                                                >
                                                    <LimitedField
                                                        label="Name"
                                                        error={
                                                            form.errors[
                                                                `vitae.certifications.${index}.name`
                                                            ]
                                                        }
                                                        value={cert.name ?? ''}
                                                        maxLength={
                                                            LIMIT.standard
                                                        }
                                                        onChange={(value) => {
                                                            const certifications =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .certifications,
                                                                ];
                                                            certifications[
                                                                index
                                                            ] = {
                                                                ...cert,
                                                                name: value,
                                                            };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    certifications,
                                                                },
                                                            );
                                                        }}
                                                    />
                                                    <LimitedField
                                                        label="Short description"
                                                        error={
                                                            form.errors[
                                                                `vitae.certifications.${index}.description`
                                                            ]
                                                        }
                                                        value={
                                                            cert.description ??
                                                            ''
                                                        }
                                                        maxLength={LIMIT.note}
                                                        onChange={(value) => {
                                                            const certifications =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .certifications,
                                                                ];
                                                            certifications[
                                                                index
                                                            ] = {
                                                                ...cert,
                                                                description:
                                                                    value,
                                                            };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    certifications,
                                                                },
                                                            );
                                                        }}
                                                    />
                                                    <DocumentUploadField
                                                        id={`certification-file-${index}`}
                                                        label="Upload certificate"
                                                        error={
                                                            form.errors[
                                                                `vitae.certifications.${index}.certificate`
                                                            ]
                                                        }
                                                        file={
                                                            cert.certificate ??
                                                            null
                                                        }
                                                        existingPath={
                                                            cert.certificate_path
                                                        }
                                                        onFileChange={(
                                                            next,
                                                        ) => {
                                                            const certifications =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .certifications,
                                                                ];
                                                            certifications[
                                                                index
                                                            ] = {
                                                                ...cert,
                                                                certificate:
                                                                    next,
                                                            };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    certifications,
                                                                },
                                                            );
                                                        }}
                                                        onClearPending={() => {
                                                            const certifications =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .certifications,
                                                                ];
                                                            certifications[
                                                                index
                                                            ] = {
                                                                ...cert,
                                                                certificate:
                                                                    null,
                                                            };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    certifications,
                                                                },
                                                            );
                                                        }}
                                                        hint="Upload a scan or photo of the certificate — JPG, PNG, or PDF, up to 10MB."
                                                    />
                                                    {form.data.vitae
                                                        .certifications.length >
                                                        1 && (
                                                        <div className="flex items-end">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                onClick={() => {
                                                                    form.setData(
                                                                        'vitae',
                                                                        {
                                                                            ...form
                                                                                .data
                                                                                .vitae,
                                                                            certifications:
                                                                                form.data.vitae.certifications.filter(
                                                                                    (
                                                                                        _,
                                                                                        i,
                                                                                    ) =>
                                                                                        i !==
                                                                                        index,
                                                                                ),
                                                                        },
                                                                    );
                                                                }}
                                                            >
                                                                Remove
                                                            </Button>
                                                        </div>
                                                    )}
                                                </div>
                                            ),
                                        )}
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                form.setData('vitae', {
                                                    ...form.data.vitae,
                                                    certifications: [
                                                        ...form.data.vitae
                                                            .certifications,
                                                        emptyCertification(),
                                                    ],
                                                })
                                            }
                                        >
                                            Add more
                                        </Button>
                                    </div>
                                </Section>

                                <Section
                                    title="License"
                                    description="Profession, license number, and optional license upload."
                                >
                                    <div className="space-y-3">
                                        {form.data.vitae.licenses.map(
                                            (license, index) => (
                                                <div
                                                    key={index}
                                                    className="grid gap-3 rounded-lg border border-border/70 p-3 sm:grid-cols-2"
                                                >
                                                    <LimitedField
                                                        label="Profession"
                                                        error={
                                                            form.errors[
                                                                `vitae.licenses.${index}.title`
                                                            ]
                                                        }
                                                        value={
                                                            license.title ?? ''
                                                        }
                                                        maxLength={
                                                            LIMIT.standard
                                                        }
                                                        onChange={(value) => {
                                                            const licenses = [
                                                                ...form.data
                                                                    .vitae
                                                                    .licenses,
                                                            ];
                                                            licenses[index] = {
                                                                ...license,
                                                                title: value,
                                                            };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    licenses,
                                                                },
                                                            );
                                                        }}
                                                    />
                                                    <LimitedField
                                                        label="License no."
                                                        error={
                                                            form.errors[
                                                                `vitae.licenses.${index}.registration_number`
                                                            ]
                                                        }
                                                        value={
                                                            license.registration_number ??
                                                            ''
                                                        }
                                                        maxLength={
                                                            LIMIT.standard
                                                        }
                                                        onChange={(value) => {
                                                            const licenses = [
                                                                ...form.data
                                                                    .vitae
                                                                    .licenses,
                                                            ];
                                                            licenses[index] = {
                                                                ...license,
                                                                registration_number:
                                                                    value,
                                                            };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    licenses,
                                                                },
                                                            );
                                                        }}
                                                    />
                                                    <DocumentUploadField
                                                        id={`license-file-${index}`}
                                                        label="Upload license"
                                                        error={
                                                            form.errors[
                                                                `vitae.licenses.${index}.certificate`
                                                            ]
                                                        }
                                                        file={
                                                            license.certificate ??
                                                            null
                                                        }
                                                        existingPath={
                                                            license.certificate_path
                                                        }
                                                        onFileChange={(
                                                            next,
                                                        ) => {
                                                            const licenses = [
                                                                ...form.data
                                                                    .vitae
                                                                    .licenses,
                                                            ];
                                                            licenses[index] = {
                                                                ...license,
                                                                certificate:
                                                                    next,
                                                            };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    licenses,
                                                                },
                                                            );
                                                        }}
                                                        onClearPending={() => {
                                                            const licenses = [
                                                                ...form.data
                                                                    .vitae
                                                                    .licenses,
                                                            ];
                                                            licenses[index] = {
                                                                ...license,
                                                                certificate:
                                                                    null,
                                                            };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    licenses,
                                                                },
                                                            );
                                                        }}
                                                        hint="Upload a scan or photo of the license — JPG, PNG, or PDF, up to 10MB."
                                                    />
                                                    {form.data.vitae.licenses
                                                        .length > 1 && (
                                                        <div className="flex items-end">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                onClick={() => {
                                                                    form.setData(
                                                                        'vitae',
                                                                        {
                                                                            ...form
                                                                                .data
                                                                                .vitae,
                                                                            licenses:
                                                                                form.data.vitae.licenses.filter(
                                                                                    (
                                                                                        _,
                                                                                        i,
                                                                                    ) =>
                                                                                        i !==
                                                                                        index,
                                                                                ),
                                                                        },
                                                                    );
                                                                }}
                                                            >
                                                                Remove
                                                            </Button>
                                                        </div>
                                                    )}
                                                </div>
                                            ),
                                        )}
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                form.setData('vitae', {
                                                    ...form.data.vitae,
                                                    licenses: [
                                                        ...form.data.vitae
                                                            .licenses,
                                                        emptyLicense(),
                                                    ],
                                                })
                                            }
                                        >
                                            Add license
                                        </Button>
                                    </div>
                                </Section>
                            </>
                        )}

                        {stepId === 'experience' && (
                            <Section
                                title={CV_STEPS[3].title}
                                description={CV_STEPS[3].description}
                            >
                                <Field label="Are you a fresher?">
                                    <Select
                                        value={
                                            form.data.vitae.is_newbie
                                                ? 'yes'
                                                : 'no'
                                        }
                                        onValueChange={(value) => {
                                            const isNewbie = value === 'yes';
                                            form.setData('vitae', {
                                                ...form.data.vitae,
                                                is_newbie: isNewbie,
                                                experiences:
                                                    !isNewbie &&
                                                    form.data.vitae.experiences
                                                        .length === 0
                                                        ? [emptyExperience()]
                                                        : form.data.vitae
                                                              .experiences,
                                            });
                                        }}
                                    >
                                        <SelectTrigger className="w-full sm:max-w-xs">
                                            <SelectValue placeholder="Select" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="yes">
                                                Yes
                                            </SelectItem>
                                            <SelectItem value="no">
                                                No
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </Field>

                                {form.data.vitae.is_newbie ? (
                                    <LimitedTextarea
                                        label="What are your current plans?"
                                        error={form.errors.gap_reasons}
                                        value={form.data.gap_reasons}
                                        maxLength={LIMIT.veryLong}
                                        placeholder="Share what you are focusing on right now — studies, training, or next steps."
                                        onChange={(value) =>
                                            form.setData('gap_reasons', value)
                                        }
                                    />
                                ) : (
                                    <div className="space-y-4">
                                        {form.data.vitae.experiences.map(
                                            (job, index) => (
                                                <div
                                                    key={index}
                                                    className="grid gap-4 rounded-xl border border-border/70 p-4 sm:grid-cols-2 sm:p-5"
                                                >
                                                    <LimitedField
                                                        label="Start Date (M/Y)"
                                                        value={
                                                            job.start_date ?? ''
                                                        }
                                                        maxLength={LIMIT.short}
                                                        placeholder="MM/YYYY"
                                                        onChange={(value) => {
                                                            const experiences =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .experiences,
                                                                ];
                                                            experiences[index] =
                                                                {
                                                                    ...job,
                                                                    start_date:
                                                                        value,
                                                                };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    experiences,
                                                                },
                                                            );
                                                        }}
                                                    />
                                                    <LimitedField
                                                        label="End Date (M/Y)"
                                                        value={
                                                            job.end_date ?? ''
                                                        }
                                                        maxLength={LIMIT.short}
                                                        placeholder="MM/YYYY"
                                                        onChange={(value) => {
                                                            const experiences =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .experiences,
                                                                ];
                                                            experiences[index] =
                                                                {
                                                                    ...job,
                                                                    end_date:
                                                                        value,
                                                                };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    experiences,
                                                                },
                                                            );
                                                        }}
                                                    />
                                                    <LimitedField
                                                        label="Designation"
                                                        value={
                                                            job.designation ??
                                                            ''
                                                        }
                                                        maxLength={
                                                            LIMIT.standard
                                                        }
                                                        onChange={(value) => {
                                                            const experiences =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .experiences,
                                                                ];
                                                            experiences[index] =
                                                                {
                                                                    ...job,
                                                                    designation:
                                                                        value,
                                                                };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    experiences,
                                                                },
                                                            );
                                                        }}
                                                    />
                                                    <LimitedField
                                                        label="Department"
                                                        value={
                                                            job.department ?? ''
                                                        }
                                                        maxLength={
                                                            LIMIT.standard
                                                        }
                                                        onChange={(value) => {
                                                            const experiences =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .experiences,
                                                                ];
                                                            experiences[index] =
                                                                {
                                                                    ...job,
                                                                    department:
                                                                        value,
                                                                };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    experiences,
                                                                },
                                                            );
                                                        }}
                                                    />
                                                    <LimitedField
                                                        label="Organization name"
                                                        value={
                                                            job.organization ??
                                                            ''
                                                        }
                                                        maxLength={
                                                            LIMIT.standard
                                                        }
                                                        onChange={(value) => {
                                                            const experiences =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .experiences,
                                                                ];
                                                            experiences[index] =
                                                                {
                                                                    ...job,
                                                                    organization:
                                                                        value,
                                                                };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    experiences,
                                                                },
                                                            );
                                                        }}
                                                    />
                                                    <LimitedField
                                                        label="City"
                                                        value={job.city ?? ''}
                                                        maxLength={
                                                            LIMIT.standard
                                                        }
                                                        onChange={(value) => {
                                                            const experiences =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .experiences,
                                                                ];
                                                            experiences[index] =
                                                                {
                                                                    ...job,
                                                                    city: value,
                                                                };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    experiences,
                                                                },
                                                            );
                                                        }}
                                                    />
                                                    <LimitedField
                                                        label="State"
                                                        value={job.state ?? ''}
                                                        maxLength={
                                                            LIMIT.standard
                                                        }
                                                        onChange={(value) => {
                                                            const experiences =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .experiences,
                                                                ];
                                                            experiences[index] =
                                                                {
                                                                    ...job,
                                                                    state: value,
                                                                };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    experiences,
                                                                },
                                                            );
                                                        }}
                                                    />
                                                    <LimitedTextarea
                                                        label="Cases Handled"
                                                        value={
                                                            job.cases_handled ??
                                                            ''
                                                        }
                                                        maxLength={
                                                            LIMIT.veryLong
                                                        }
                                                        onChange={(value) => {
                                                            const experiences =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .experiences,
                                                                ];
                                                            experiences[index] =
                                                                {
                                                                    ...job,
                                                                    cases_handled:
                                                                        value,
                                                                };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    experiences,
                                                                },
                                                            );
                                                        }}
                                                    />

                                                    <div className="space-y-3 sm:col-span-2">
                                                        <p className="text-sm font-medium text-foreground">
                                                            For Healthcare
                                                            professionals
                                                        </p>
                                                        <div className="grid gap-4 sm:grid-cols-2">
                                                            <LimitedField
                                                                label="Medical equipments used"
                                                                value={
                                                                    job.medical_equipment ??
                                                                    ''
                                                                }
                                                                maxLength={
                                                                    LIMIT.paragraph
                                                                }
                                                                onChange={(
                                                                    value,
                                                                ) => {
                                                                    const experiences =
                                                                        [
                                                                            ...form
                                                                                .data
                                                                                .vitae
                                                                                .experiences,
                                                                        ];
                                                                    experiences[
                                                                        index
                                                                    ] = {
                                                                        ...job,
                                                                        medical_equipment:
                                                                            value,
                                                                    };
                                                                    form.setData(
                                                                        'vitae',
                                                                        {
                                                                            ...form
                                                                                .data
                                                                                .vitae,
                                                                            experiences,
                                                                        },
                                                                    );
                                                                }}
                                                            />
                                                            <LimitedField
                                                                label="Bedded capacity"
                                                                value={
                                                                    job.bed_capacity ??
                                                                    ''
                                                                }
                                                                maxLength={
                                                                    LIMIT.standard
                                                                }
                                                                onChange={(
                                                                    value,
                                                                ) => {
                                                                    const experiences =
                                                                        [
                                                                            ...form
                                                                                .data
                                                                                .vitae
                                                                                .experiences,
                                                                        ];
                                                                    experiences[
                                                                        index
                                                                    ] = {
                                                                        ...job,
                                                                        bed_capacity:
                                                                            value,
                                                                    };
                                                                    form.setData(
                                                                        'vitae',
                                                                        {
                                                                            ...form
                                                                                .data
                                                                                .vitae,
                                                                            experiences,
                                                                        },
                                                                    );
                                                                }}
                                                            />
                                                        </div>
                                                    </div>

                                                    <DocumentUploadField
                                                        id={`experience-certificate-${index}`}
                                                        label="Upload Experience Certificate"
                                                        error={
                                                            form.errors[
                                                                `vitae.experiences.${index}.certificate`
                                                            ]
                                                        }
                                                        file={
                                                            job.certificate ??
                                                            null
                                                        }
                                                        existingPath={
                                                            job.certificate_path
                                                        }
                                                        onFileChange={(
                                                            next,
                                                        ) => {
                                                            const experiences =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .experiences,
                                                                ];
                                                            experiences[index] =
                                                                {
                                                                    ...job,
                                                                    certificate:
                                                                        next,
                                                                };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    experiences,
                                                                },
                                                            );
                                                        }}
                                                        onClearPending={() => {
                                                            const experiences =
                                                                [
                                                                    ...form.data
                                                                        .vitae
                                                                        .experiences,
                                                                ];
                                                            experiences[index] =
                                                                {
                                                                    ...job,
                                                                    certificate:
                                                                        null,
                                                                };
                                                            form.setData(
                                                                'vitae',
                                                                {
                                                                    ...form.data
                                                                        .vitae,
                                                                    experiences,
                                                                },
                                                            );
                                                        }}
                                                        hint="Upload a scan or photo of the experience certificate — JPG, PNG, or PDF, up to 10MB."
                                                    />

                                                    {form.data.vitae.experiences
                                                        .length > 1 && (
                                                        <div className="flex items-end sm:col-span-2">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                onClick={() =>
                                                                    form.setData(
                                                                        'vitae',
                                                                        {
                                                                            ...form
                                                                                .data
                                                                                .vitae,
                                                                            experiences:
                                                                                form.data.vitae.experiences.filter(
                                                                                    (
                                                                                        _,
                                                                                        i,
                                                                                    ) =>
                                                                                        i !==
                                                                                        index,
                                                                                ),
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                Remove
                                                            </Button>
                                                        </div>
                                                    )}
                                                </div>
                                            ),
                                        )}
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                form.setData('vitae', {
                                                    ...form.data.vitae,
                                                    experiences: [
                                                        ...form.data.vitae
                                                            .experiences,
                                                        emptyExperience(),
                                                    ],
                                                })
                                            }
                                        >
                                            Add more experience
                                        </Button>
                                    </div>
                                )}
                            </Section>
                        )}

                        {stepId === 'bilingual' && (
                            <Section
                                title={CV_STEPS[4].title}
                                description={CV_STEPS[4].description}
                            >
                                <div className="space-y-4">
                                    {form.data.vitae.bilingual.map(
                                        (entry, index) => {
                                            const knownLanguage =
                                                isKnownLanguageOption(
                                                    entry.language ?? '',
                                                );
                                            const languageSelectValue =
                                                knownLanguage
                                                    ? (entry.language ??
                                                      undefined)
                                                    : entry.language
                                                      ? LANGUAGE_PICKER_OTHER
                                                      : undefined;

                                            const updateBilingualEntry = (
                                                patch: Partial<FormBilingual>,
                                            ) => {
                                                const bilingual = [
                                                    ...form.data.vitae
                                                        .bilingual,
                                                ];
                                                bilingual[index] = {
                                                    ...entry,
                                                    ...patch,
                                                };
                                                form.setData('vitae', {
                                                    ...form.data.vitae,
                                                    bilingual,
                                                });
                                            };

                                            return (
                                                <div
                                                    key={index}
                                                    className="grid gap-4 rounded-xl border border-border/70 p-4 sm:grid-cols-2 sm:items-start sm:p-5 lg:grid-cols-3"
                                                >
                                                    <Field
                                                        label="Language Known"
                                                        error={
                                                            form.errors[
                                                                `vitae.bilingual.${index}.language`
                                                            ]
                                                        }
                                                        hint={characterLimitHint(
                                                            LIMIT.standard,
                                                        )}
                                                    >
                                                        <Select
                                                            value={
                                                                languageSelectValue
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) => {
                                                                if (
                                                                    value ===
                                                                    LANGUAGE_PICKER_OTHER
                                                                ) {
                                                                    updateBilingualEntry(
                                                                        {
                                                                            language:
                                                                                knownLanguage
                                                                                    ? ''
                                                                                    : (entry.language ??
                                                                                      ''),
                                                                        },
                                                                    );

                                                                    return;
                                                                }

                                                                const suggested =
                                                                    suggestedLanguageType(
                                                                        value,
                                                                    );
                                                                const nextType =
                                                                    entry.language_type ||
                                                                    suggested ||
                                                                    '';
                                                                updateBilingualEntry(
                                                                    {
                                                                        language:
                                                                            value,
                                                                        language_type:
                                                                            nextType,
                                                                        ...(nextType ===
                                                                        'Regional'
                                                                            ? {
                                                                                  certification_level:
                                                                                      '',
                                                                              }
                                                                            : {}),
                                                                    },
                                                                );
                                                            }}
                                                        >
                                                            <SelectTrigger className="w-full">
                                                                <SelectValue
                                                                    placeholder={bilingualLanguagePlaceholder(
                                                                        index,
                                                                    )}
                                                                />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem
                                                                    value={
                                                                        LANGUAGE_PICKER_OTHER
                                                                    }
                                                                >
                                                                    Other (type
                                                                    below)
                                                                </SelectItem>
                                                                {REGIONAL_LANGUAGES_IN.map(
                                                                    (name) => (
                                                                        <SelectItem
                                                                            key={`regional-${name}`}
                                                                            value={
                                                                                name
                                                                            }
                                                                        >
                                                                            {
                                                                                name
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                                {FOREIGN_LANGUAGE_OPTIONS.map(
                                                                    (name) => (
                                                                        <SelectItem
                                                                            key={`foreign-${name}`}
                                                                            value={
                                                                                name
                                                                            }
                                                                        >
                                                                            {
                                                                                name
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        {!knownLanguage ? (
                                                            <Input
                                                                className="mt-2"
                                                                value={
                                                                    entry.language ??
                                                                    ''
                                                                }
                                                                placeholder={bilingualLanguagePlaceholder(
                                                                    index,
                                                                )}
                                                                onChange={(
                                                                    event,
                                                                ) => {
                                                                    const value =
                                                                        sanitizeCvText(
                                                                            event
                                                                                .target
                                                                                .value,
                                                                        ).slice(
                                                                            0,
                                                                            LIMIT.standard,
                                                                        );
                                                                    updateBilingualEntry(
                                                                        {
                                                                            language:
                                                                                value,
                                                                        },
                                                                    );
                                                                }}
                                                            />
                                                        ) : null}
                                                    </Field>
                                                    <Field
                                                        label="Language type"
                                                        error={
                                                            form.errors[
                                                                `vitae.bilingual.${index}.language_type`
                                                            ]
                                                        }
                                                    >
                                                        <Select
                                                            value={
                                                                entry.language_type ||
                                                                undefined
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) => {
                                                                updateBilingualEntry(
                                                                    {
                                                                        language_type:
                                                                            value,
                                                                        ...(value ===
                                                                        'Regional'
                                                                            ? {
                                                                                  certification_level:
                                                                                      '',
                                                                              }
                                                                            : {}),
                                                                    },
                                                                );
                                                            }}
                                                        >
                                                            <SelectTrigger className="w-full">
                                                                <SelectValue placeholder="Select type" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {LANGUAGE_TYPES.map(
                                                                    (type) => (
                                                                        <SelectItem
                                                                            key={
                                                                                type
                                                                            }
                                                                            value={
                                                                                type
                                                                            }
                                                                        >
                                                                            {
                                                                                type
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                    </Field>
                                                    <Field
                                                        label="Language Proficiency"
                                                        error={
                                                            form.errors[
                                                                `vitae.bilingual.${index}.proficiency`
                                                            ]
                                                        }
                                                    >
                                                        <Select
                                                            value={
                                                                entry.proficiency ||
                                                                undefined
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) => {
                                                                updateBilingualEntry(
                                                                    {
                                                                        proficiency:
                                                                            value,
                                                                    },
                                                                );
                                                            }}
                                                        >
                                                            <SelectTrigger className="w-full">
                                                                <SelectValue placeholder="Select level" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {LANGUAGE_PROFICIENCY_LEVELS.map(
                                                                    (level) => (
                                                                        <SelectItem
                                                                            key={
                                                                                level
                                                                            }
                                                                            value={
                                                                                level
                                                                            }
                                                                        >
                                                                            {
                                                                                level
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                    </Field>
                                                    {entry.language_type !==
                                                        'Regional' && (
                                                        <Field
                                                            label="Certification level"
                                                            error={
                                                                form.errors[
                                                                    `vitae.bilingual.${index}.certification_level`
                                                                ]
                                                            }
                                                            hint="Optional CEFR level (A1–C2)"
                                                        >
                                                            <Select
                                                                value={
                                                                    entry.certification_level ||
                                                                    '__none__'
                                                                }
                                                                onValueChange={(
                                                                    value,
                                                                ) => {
                                                                    updateBilingualEntry(
                                                                        {
                                                                            certification_level:
                                                                                value ===
                                                                                '__none__'
                                                                                    ? ''
                                                                                    : value,
                                                                        },
                                                                    );
                                                                }}
                                                            >
                                                                <SelectTrigger className="w-full">
                                                                    <SelectValue placeholder="None" />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="__none__">
                                                                        None
                                                                    </SelectItem>
                                                                    {CERTIFICATION_LEVELS.map(
                                                                        (
                                                                            level,
                                                                        ) => (
                                                                            <SelectItem
                                                                                key={
                                                                                    level
                                                                                }
                                                                                value={
                                                                                    level
                                                                                }
                                                                            >
                                                                                {
                                                                                    level
                                                                                }
                                                                            </SelectItem>
                                                                        ),
                                                                    )}
                                                                </SelectContent>
                                                            </Select>
                                                        </Field>
                                                    )}
                                                    <DocumentUploadField
                                                        id={`bilingual-certificate-${index}`}
                                                        label="Upload Certificates"
                                                        error={
                                                            form.errors[
                                                                `vitae.bilingual.${index}.certificate`
                                                            ]
                                                        }
                                                        file={
                                                            entry.certificate ??
                                                            null
                                                        }
                                                        existingPath={
                                                            entry.certificate_path
                                                        }
                                                        onFileChange={(
                                                            next,
                                                        ) => {
                                                            updateBilingualEntry(
                                                                {
                                                                    certificate:
                                                                        next,
                                                                },
                                                            );
                                                        }}
                                                        onClearPending={() => {
                                                            updateBilingualEntry(
                                                                {
                                                                    certificate:
                                                                        null,
                                                                },
                                                            );
                                                        }}
                                                        hint="JPG, PNG, or PDF, up to 10MB."
                                                    />
                                                    {form.data.vitae.bilingual
                                                        .length > 4 && (
                                                        <div className="flex items-end sm:col-span-2 lg:col-span-3">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                onClick={() => {
                                                                    form.setData(
                                                                        'vitae',
                                                                        {
                                                                            ...form
                                                                                .data
                                                                                .vitae,
                                                                            bilingual:
                                                                                form.data.vitae.bilingual.filter(
                                                                                    (
                                                                                        _,
                                                                                        i,
                                                                                    ) =>
                                                                                        i !==
                                                                                        index,
                                                                                ),
                                                                        },
                                                                    );
                                                                }}
                                                            >
                                                                Remove
                                                            </Button>
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        },
                                    )}
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            form.setData('vitae', {
                                                ...form.data.vitae,
                                                bilingual: [
                                                    ...form.data.vitae
                                                        .bilingual,
                                                    emptyBilingual(),
                                                ],
                                            })
                                        }
                                    >
                                        Add language
                                    </Button>
                                </div>
                            </Section>
                        )}

                        {stepId === 'qualification' && (
                            <Section
                                title={CV_STEPS[5].title}
                                description={CV_STEPS[5].description}
                            >
                                <div className="space-y-4">
                                    {form.data.vitae.qualifications.map(
                                        (item, index) => (
                                            <div
                                                key={index}
                                                className="grid gap-4 rounded-xl border border-border/70 p-4 sm:grid-cols-2 sm:p-5"
                                            >
                                                <LimitedField
                                                    label="Start date - M/Y"
                                                    value={
                                                        item.start_date ?? ''
                                                    }
                                                    maxLength={LIMIT.short}
                                                    placeholder="MM/YYYY"
                                                    onChange={(value) => {
                                                        const qualifications = [
                                                            ...form.data.vitae
                                                                .qualifications,
                                                        ];
                                                        qualifications[index] =
                                                            {
                                                                ...item,
                                                                start_date:
                                                                    value,
                                                            };
                                                        form.setData('vitae', {
                                                            ...form.data.vitae,
                                                            qualifications,
                                                        });
                                                    }}
                                                />
                                                <LimitedField
                                                    label="End date (M/Y)"
                                                    value={item.end_date ?? ''}
                                                    maxLength={LIMIT.short}
                                                    placeholder="MM/YYYY"
                                                    onChange={(value) => {
                                                        const qualifications = [
                                                            ...form.data.vitae
                                                                .qualifications,
                                                        ];
                                                        qualifications[index] =
                                                            {
                                                                ...item,
                                                                end_date: value,
                                                            };
                                                        form.setData('vitae', {
                                                            ...form.data.vitae,
                                                            qualifications,
                                                        });
                                                    }}
                                                />
                                                <LimitedField
                                                    label="Qualification"
                                                    value={item.title ?? ''}
                                                    maxLength={LIMIT.standard}
                                                    onChange={(value) => {
                                                        const qualifications = [
                                                            ...form.data.vitae
                                                                .qualifications,
                                                        ];
                                                        qualifications[index] =
                                                            {
                                                                ...item,
                                                                title: value,
                                                            };
                                                        form.setData('vitae', {
                                                            ...form.data.vitae,
                                                            qualifications,
                                                        });
                                                    }}
                                                />
                                                <LimitedField
                                                    label="College/School"
                                                    value={item.college ?? ''}
                                                    maxLength={LIMIT.standard}
                                                    onChange={(value) => {
                                                        const qualifications = [
                                                            ...form.data.vitae
                                                                .qualifications,
                                                        ];
                                                        qualifications[index] =
                                                            {
                                                                ...item,
                                                                college: value,
                                                            };
                                                        form.setData('vitae', {
                                                            ...form.data.vitae,
                                                            qualifications,
                                                        });
                                                    }}
                                                />
                                                <LimitedField
                                                    label="University/Board"
                                                    value={
                                                        item.university ?? ''
                                                    }
                                                    maxLength={LIMIT.standard}
                                                    onChange={(value) => {
                                                        const qualifications = [
                                                            ...form.data.vitae
                                                                .qualifications,
                                                        ];
                                                        qualifications[index] =
                                                            {
                                                                ...item,
                                                                university:
                                                                    value,
                                                            };
                                                        form.setData('vitae', {
                                                            ...form.data.vitae,
                                                            qualifications,
                                                        });
                                                    }}
                                                />
                                                <LimitedField
                                                    label="City"
                                                    value={item.city ?? ''}
                                                    maxLength={LIMIT.standard}
                                                    onChange={(value) => {
                                                        const qualifications = [
                                                            ...form.data.vitae
                                                                .qualifications,
                                                        ];
                                                        qualifications[index] =
                                                            {
                                                                ...item,
                                                                city: value,
                                                            };
                                                        form.setData('vitae', {
                                                            ...form.data.vitae,
                                                            qualifications,
                                                        });
                                                    }}
                                                />
                                                <LimitedField
                                                    label="State"
                                                    value={item.state ?? ''}
                                                    maxLength={LIMIT.standard}
                                                    onChange={(value) => {
                                                        const qualifications = [
                                                            ...form.data.vitae
                                                                .qualifications,
                                                        ];
                                                        qualifications[index] =
                                                            {
                                                                ...item,
                                                                state: value,
                                                            };
                                                        form.setData('vitae', {
                                                            ...form.data.vitae,
                                                            qualifications,
                                                        });
                                                    }}
                                                />
                                                <LimitedField
                                                    label="Country"
                                                    value={item.country ?? ''}
                                                    maxLength={LIMIT.standard}
                                                    onChange={(value) => {
                                                        const qualifications = [
                                                            ...form.data.vitae
                                                                .qualifications,
                                                        ];
                                                        qualifications[index] =
                                                            {
                                                                ...item,
                                                                country: value,
                                                            };
                                                        form.setData('vitae', {
                                                            ...form.data.vitae,
                                                            qualifications,
                                                        });
                                                    }}
                                                />
                                                <LimitedField
                                                    label="Language of instruction"
                                                    value={
                                                        item.language_of_instruction ??
                                                        ''
                                                    }
                                                    maxLength={LIMIT.medium}
                                                    onChange={(value) => {
                                                        const qualifications = [
                                                            ...form.data.vitae
                                                                .qualifications,
                                                        ];
                                                        qualifications[index] =
                                                            {
                                                                ...item,
                                                                language_of_instruction:
                                                                    value,
                                                            };
                                                        form.setData('vitae', {
                                                            ...form.data.vitae,
                                                            qualifications,
                                                        });
                                                    }}
                                                />
                                                <DocumentUploadField
                                                    id={`qualification-file-${index}`}
                                                    label="Upload certificate"
                                                    error={
                                                        form.errors[
                                                            `vitae.qualifications.${index}.certificate`
                                                        ]
                                                    }
                                                    file={
                                                        item.certificate ?? null
                                                    }
                                                    existingPath={
                                                        item.certificate_path
                                                    }
                                                    onFileChange={(next) => {
                                                        const qualifications = [
                                                            ...form.data.vitae
                                                                .qualifications,
                                                        ];
                                                        qualifications[index] =
                                                            {
                                                                ...item,
                                                                certificate:
                                                                    next,
                                                            };
                                                        form.setData('vitae', {
                                                            ...form.data.vitae,
                                                            qualifications,
                                                        });
                                                    }}
                                                    onClearPending={() => {
                                                        const qualifications = [
                                                            ...form.data.vitae
                                                                .qualifications,
                                                        ];
                                                        qualifications[index] =
                                                            {
                                                                ...item,
                                                                certificate:
                                                                    null,
                                                            };
                                                        form.setData('vitae', {
                                                            ...form.data.vitae,
                                                            qualifications,
                                                        });
                                                    }}
                                                    hint="Upload your degree or mark sheet — JPG, PNG, or PDF, up to 10MB."
                                                />
                                                {form.data.vitae.qualifications
                                                    .length > 1 && (
                                                    <div className="flex items-end">
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() =>
                                                                form.setData(
                                                                    'vitae',
                                                                    {
                                                                        ...form
                                                                            .data
                                                                            .vitae,
                                                                        qualifications:
                                                                            form.data.vitae.qualifications.filter(
                                                                                (
                                                                                    _,
                                                                                    i,
                                                                                ) =>
                                                                                    i !==
                                                                                    index,
                                                                            ),
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            Remove
                                                        </Button>
                                                    </div>
                                                )}
                                            </div>
                                        ),
                                    )}
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            form.setData('vitae', {
                                                ...form.data.vitae,
                                                qualifications: [
                                                    ...form.data.vitae
                                                        .qualifications,
                                                    emptyQualification(),
                                                ],
                                            })
                                        }
                                    >
                                        Add qualification
                                    </Button>
                                </div>
                            </Section>
                        )}

                        {stepId === 'training' && (
                            <Section
                                title={CV_STEPS[6].title}
                                description={CV_STEPS[6].description}
                            >
                                <div className="space-y-8">
                                    <ActivityListEditor
                                        title="Seminar"
                                        addLabel="Add seminar"
                                        listKey="seminars"
                                        items={form.data.vitae.seminars ?? []}
                                        errors={form.errors}
                                        onChange={(seminars) =>
                                            form.setData('vitae', {
                                                ...form.data.vitae,
                                                seminars,
                                            })
                                        }
                                    />
                                    <ActivityListEditor
                                        title="Presentation"
                                        addLabel="Add presentation"
                                        listKey="presentations"
                                        items={
                                            form.data.vitae.presentations ?? []
                                        }
                                        errors={form.errors}
                                        onChange={(presentations) =>
                                            form.setData('vitae', {
                                                ...form.data.vitae,
                                                presentations,
                                            })
                                        }
                                    />
                                    <ActivityListEditor
                                        title="Publication"
                                        addLabel="Add publication"
                                        listKey="publications"
                                        items={
                                            form.data.vitae.publications ?? []
                                        }
                                        errors={form.errors}
                                        onChange={(publications) =>
                                            form.setData('vitae', {
                                                ...form.data.vitae,
                                                publications,
                                            })
                                        }
                                    />
                                </div>
                            </Section>
                        )}

                        {stepId === 'intro' && (
                            <Section
                                title={CV_STEPS[7].title}
                                description={CV_STEPS[7].description}
                            >
                                <div className="space-y-8">
                                    <div className="space-y-3">
                                        <h3 className="text-sm font-semibold tracking-wide text-foreground">
                                            Introduction
                                        </h3>
                                        <LimitedTextarea
                                            label="Introduction"
                                            error={form.errors.introduction}
                                            value={form.data.introduction}
                                            maxLength={LIMIT.introduction}
                                            rows={5}
                                            placeholder="Write a short introduction about yourself."
                                            onChange={(value) =>
                                                form.setData(
                                                    'introduction',
                                                    value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="space-y-3">
                                        <h3 className="text-sm font-semibold tracking-wide text-foreground">
                                            Skills
                                        </h3>
                                        <LimitedField
                                            label="Skills"
                                            error={form.errors.skills}
                                            value={form.data.skills}
                                            maxLength={LIMIT.long}
                                            placeholder="Patient education, Teamwork, Leadership"
                                            onChange={(value) =>
                                                form.setData('skills', value)
                                            }
                                        />
                                    </div>
                                    <div className="space-y-3">
                                        <h3 className="text-sm font-semibold tracking-wide text-foreground">
                                            Hobbies
                                        </h3>
                                        <LimitedField
                                            label="Hobbies"
                                            error={form.errors.hobbies}
                                            value={form.data.hobbies}
                                            maxLength={LIMIT.long}
                                            placeholder="Reading, Travel, Music"
                                            onChange={(value) =>
                                                form.setData('hobbies', value)
                                            }
                                        />
                                    </div>
                                </div>
                            </Section>
                        )}

                        <div className="sticky bottom-[var(--app-bottom-h,0px)] z-10 -mx-2 flex items-center gap-3 border-t border-border/60 bg-background/90 px-4 py-3 backdrop-blur-xl sm:static sm:mx-0 sm:justify-between sm:rounded-2xl sm:border sm:bg-card/30 sm:p-5 sm:backdrop-blur-none">
                            <Button
                                type="button"
                                variant="outline"
                                className="max-sm:h-12 max-sm:rounded-full max-sm:px-5"
                                disabled={isFirstStep}
                                onClick={goBack}
                            >
                                Back
                            </Button>
                            <div className="flex flex-1 gap-3 sm:flex-none sm:flex-wrap">
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="max-sm:h-12 max-sm:flex-1 max-sm:rounded-full"
                                    disabled={form.processing}
                                >
                                    {form.processing && <Spinner />}
                                    Save
                                </Button>
                                {isLastStep ? (
                                    <Button
                                        type="button"
                                        className="max-sm:h-12 max-sm:flex-1 max-sm:rounded-full"
                                        onClick={() => {
                                            setPrintVariant('basic');
                                            setTab('preview');
                                        }}
                                    >
                                        Preview
                                    </Button>
                                ) : (
                                    <Button
                                        type="button"
                                        className="max-sm:h-12 max-sm:flex-1 max-sm:rounded-full"
                                        onClick={() => {
                                            goNext();
                                            window.scrollTo({ top: 0 });
                                        }}
                                    >
                                        Continue
                                    </Button>
                                )}
                            </div>
                        </div>
                    </form>
                )}

                {printVariant === 'full' && (
                    <div className="cv-print-only" aria-hidden>
                        <FullCvProfile cv={displayCv} locale={previewLocale} />
                    </div>
                )}
            </div>
        </>
    );
}

StudentCv.layout = {
    breadcrumbs: [
        {
            title: 'Your CV',
            href: cvEdit(),
        },
    ],
};
