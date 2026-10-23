import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ChevronDown, Download } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useMediaQuery } from '@/hooks/use-media-query';
import { cn } from '@/lib/utils';
import { index as studentsIndex } from '@/routes/recruiter/students';
import AddToProjectMenu from '@app/components/add-to-project-menu';
import BasicCvProfile from '@app/components/basic-cv-profile';
import CandidateCareerDetails from '@app/components/candidate-career-details';
import CandidateDocumentsViewer from '@app/components/candidate-documents-viewer';
import CandidateLanguageProgress from '@app/components/candidate-language-progress';
import CandidatePersonalDetails from '@app/components/candidate-personal-details';
import FacultyStudentProfilePanel from '@app/components/faculty-student-profile-panel';
import FullCvProfile from '@app/components/full-cv-profile';
import type { CvLocale } from '@app/lib/cv-labels';
import { printCvDocument } from '@app/lib/cv-print';
import type { CvProfile } from '@app/types/cv';
import type {
    CandidateCard,
    LanguageProgress,
    ShortlistProject,
} from '@app/types/recruiter';
import InputError from '@shared/components/input-error';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Assignment = {
    batch_id: number;
    batch_name: string;
    center_name: string | null;
    language: string;
    language_label: string;
    level: string;
    teacher_name: string | null;
};

type TransferTarget = {
    id: number;
    name: string;
    center_name: string;
    language: string;
    level: string;
    teacher_name: string;
};

type FullProps = {
    restricted: false;
    summary: CandidateCard;
    cv: CvProfile;
    profileId: number;
    assignment: Assignment | null;
    language: LanguageProgress;
    transferTargets: TransferTarget[];
    canTransfer: boolean;
    shortlistProjects: ShortlistProject[] | null;
};

type RestrictedProps = {
    restricted: true;
    summary: CandidateCard;
    profileId: number;
};

type Props = FullProps | RestrictedProps;

type ViewVariant = 'basic' | 'full';
type StudentTab =
    | 'profile'
    | 'career'
    | 'personal'
    | 'language'
    | 'documents'
    | 'assignments'
    | 'cv';

const TABS: { id: StudentTab; label: string }[] = [
    { id: 'profile', label: 'Profile' },
    { id: 'career', label: 'Career' },
    { id: 'personal', label: 'Personal' },
    { id: 'language', label: 'BV Profile' },
    { id: 'documents', label: 'Documents' },
    { id: 'assignments', label: 'Transfer' },
    { id: 'cv', label: 'CV' },
];

function initialsFromName(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase();
}

export default function StudentShow(props: Props) {
    if (props.restricted) {
        return <RestrictedStudent summary={props.summary} />;
    }

    return <FullStudent {...props} />;
}

function RestrictedStudent({ summary }: { summary: CandidateCard }) {
    return (
        <>
            <PrivateSeoHead title={summary.name} />
            <div className="flex flex-col gap-4 p-2 sm:px-4">
                <Link
                    href={studentsIndex()}
                    className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:underline"
                >
                    <ArrowLeft className="size-4" />
                    Back to students
                </Link>
                <div className="rounded-3xl border border-border bg-card/40 px-6 py-10 text-center">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {summary.name}
                    </h1>
                    <p className="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                        This candidate has been hired. Their profile details are
                        no longer available.
                    </p>
                </div>
            </div>
        </>
    );
}

function FullStudent({
    cv,
    profileId,
    language,
    transferTargets = [],
    canTransfer = false,
    shortlistProjects = null,
}: FullProps) {
    const title =
        [cv.first_name, cv.last_name].filter(Boolean).join(' ') || cv.name;

    const [activeTab, setActiveTab] = useState<StudentTab>('profile');
    const [viewVariant, setViewVariant] = useState<ViewVariant>('basic');
    const [printVariant, setPrintVariant] = useState<ViewVariant>('basic');
    const [locale, setLocale] = useState<CvLocale>('en');
    const [translatedCv, setTranslatedCv] = useState<CvProfile | null>(null);
    const [isTranslating, setIsTranslating] = useState(false);
    const [translateError, setTranslateError] = useState<string | null>(null);
    const [downloadMenuOpen, setDownloadMenuOpen] = useState(false);
    const downloadMenuRef = useRef<HTMLDivElement>(null);
    const headerRef = useRef<HTMLDivElement>(null);
    const isPhone = useMediaQuery('(max-width: 639px)');
    const [headerHeight, setHeaderHeight] = useState(0);

    const transferForm = useForm({
        target_batch_id: '',
    });

    const displayCv = locale === 'de' && translatedCv ? translatedCv : cv;

    const translateUrl = `/recruiter/students/${profileId}/cv/translate`;

    const fetchGermanCv = async (): Promise<CvProfile> => {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

        const response = await fetch(translateUrl, {
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
                // keep default
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

    const handleDownload = async (
        variant: ViewVariant,
        nextLocale: CvLocale,
    ) => {
        setDownloadMenuOpen(false);

        try {
            if (nextLocale === 'de') {
                await ensureGermanCv();
            } else {
                setTranslateError(null);
            }

            setLocale(nextLocale);
            setPrintVariant(variant);
            setViewVariant(variant);
            // Pass the selected variant directly so page styles match even before re-render.
            printCvDocument(variant);
        } catch {
            // error already stored
        }
    };

    useEffect(() => {
        const header = headerRef.current;

        if (!header) {
            return;
        }

        const observer = new ResizeObserver(() =>
            setHeaderHeight(header.offsetHeight),
        );
        observer.observe(header);

        return () => observer.disconnect();
    }, []);

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

    return (
        <>
            <PrivateSeoHead title={title} />
            <div
                data-cv-print={printVariant}
                className="flex flex-col gap-6 p-2 print:gap-0 print:p-0"
                style={
                    {
                        '--student-header-h': `${headerHeight}px`,
                    } as React.CSSProperties
                }
            >
                <div
                    ref={headerRef}
                    className="cv-print-hide sticky top-[var(--app-top-h,0px)] z-20 -mx-2 flex flex-col gap-4 border-b border-border/60 bg-background px-2 py-3"
                >
                    <div className="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div className="flex min-w-0 flex-wrap items-center gap-3 sm:gap-4">
                            <Link
                                href={studentsIndex()}
                                className="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-border text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                aria-label="Back to students"
                            >
                                <ArrowLeft className="size-4" />
                            </Link>

                            <div className="flex min-w-0 items-center gap-3 max-sm:max-w-[calc(100%-9.5rem)]">
                                <Avatar className="size-12 shrink-0 rounded-2xl border border-border">
                                    {cv.passport_photo_url && (
                                        <AvatarImage
                                            src={cv.passport_photo_url}
                                            alt={title}
                                            className="rounded-2xl object-cover"
                                        />
                                    )}
                                    <AvatarFallback className="rounded-2xl text-sm font-semibold">
                                        {initialsFromName(title)}
                                    </AvatarFallback>
                                </Avatar>

                                <div className="min-w-0">
                                    <h1 className="truncate text-xl font-semibold tracking-tight sm:text-2xl">
                                        {title}
                                    </h1>
                                    <p className="mt-0.5 truncate text-sm text-muted-foreground">
                                        {cv.candidate_id
                                            ? `ID ${cv.candidate_id}`
                                            : 'Candidate ID pending'}
                                    </p>
                                </div>
                            </div>

                            <nav
                                className="flex w-full gap-1 overflow-x-auto border-b border-border pb-px sm:ml-2 sm:w-auto sm:border-b-0 sm:pb-0"
                                aria-label="Student sections"
                            >
                                {TABS.filter(
                                    (tab) =>
                                        tab.id !== 'assignments' || canTransfer,
                                ).map((tab) => (
                                    <button
                                        key={tab.id}
                                        type="button"
                                        onClick={(event) => {
                                            setActiveTab(tab.id);
                                            window.scrollTo({ top: 0 });
                                            event.currentTarget.scrollIntoView({
                                                inline: 'center',
                                                block: 'nearest',
                                                behavior: 'smooth',
                                            });
                                        }}
                                        className={cn(
                                            'shrink-0 border-b-2 px-3 py-2 text-sm font-medium transition-colors sm:rounded-lg sm:border-b-0 sm:px-3.5',
                                            activeTab === tab.id
                                                ? 'border-primary text-foreground sm:bg-primary sm:text-primary-foreground'
                                                : 'border-transparent text-muted-foreground hover:text-foreground sm:hover:bg-muted',
                                        )}
                                    >
                                        {tab.label}
                                    </button>
                                ))}
                            </nav>
                        </div>

                        <div className="flex flex-wrap items-center gap-2 self-start max-sm:absolute max-sm:top-3 max-sm:right-2 max-sm:flex-nowrap xl:self-auto">
                            {shortlistProjects !== null && (
                                <AddToProjectMenu
                                    profileId={profileId}
                                    projects={shortlistProjects}
                                />
                            )}
                            <div
                                ref={downloadMenuRef}
                                className="relative self-start xl:self-auto"
                            >
                                <button
                                    type="button"
                                    onClick={() =>
                                        setDownloadMenuOpen((open) => !open)
                                    }
                                    disabled={isTranslating}
                                    className="inline-flex items-center gap-2 rounded-xl bg-brand-cool px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-brand-cool-dark active:scale-95 disabled:cursor-not-allowed disabled:opacity-60 max-sm:size-10 max-sm:justify-center max-sm:rounded-full max-sm:p-0"
                                >
                                    {isTranslating ? (
                                        <Spinner className="size-4" />
                                    ) : (
                                        <Download className="size-4" />
                                    )}
                                    <span className="max-sm:sr-only">
                                        {isTranslating
                                            ? 'Preparing German CV…'
                                            : 'Download CV'}
                                    </span>
                                    <ChevronDown className="size-4 opacity-70 max-sm:hidden" />
                                </button>
                                {downloadMenuOpen && !isTranslating && (
                                    <div className="absolute end-0 z-20 mt-2 w-56 overflow-hidden rounded-xl border border-border bg-background shadow-lg">
                                        <p className="px-4 pt-2.5 pb-1 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                                            Basic CV
                                        </p>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                void handleDownload(
                                                    'basic',
                                                    'en',
                                                );
                                            }}
                                            className="flex w-full px-4 py-2.5 text-left text-sm hover:bg-muted"
                                        >
                                            English
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                void handleDownload(
                                                    'basic',
                                                    'de',
                                                );
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
                                            onClick={() => {
                                                void handleDownload(
                                                    'full',
                                                    'en',
                                                );
                                            }}
                                            className="flex w-full px-4 py-2.5 text-left text-sm hover:bg-muted"
                                        >
                                            English
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                void handleDownload(
                                                    'full',
                                                    'de',
                                                );
                                            }}
                                            className="flex w-full px-4 py-2.5 pb-3 text-left text-sm hover:bg-muted"
                                        >
                                            German
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {translateError && (
                    <p className="cv-print-hide rounded-xl border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {translateError}
                    </p>
                )}

                {activeTab === 'profile' && (
                    <div className="cv-print-hide">
                        <FacultyStudentProfilePanel cv={cv} />
                    </div>
                )}

                {activeTab === 'career' && (
                    <div className="cv-print-hide">
                        <CandidateCareerDetails cv={cv} />
                    </div>
                )}

                {activeTab === 'personal' && (
                    <div className="cv-print-hide">
                        <CandidatePersonalDetails cv={cv} />
                    </div>
                )}

                {activeTab === 'language' && (
                    <div className="cv-print-hide">
                        <CandidateLanguageProgress
                            language={language}
                            cv={cv}
                        />
                    </div>
                )}

                {activeTab === 'documents' && (
                    <div className="cv-print-hide">
                        <CandidateDocumentsViewer cv={cv} />
                    </div>
                )}

                {activeTab === 'assignments' && canTransfer && (
                    <div className="cv-print-hide space-y-6">
                        {canTransfer && (
                            <div className="rounded-3xl border border-border bg-card/40 px-6 py-6 sm:px-8">
                                <h2 className="text-lg font-semibold tracking-tight">
                                    Transfer to another faculty
                                </h2>
                                <p className="mt-2 max-w-xl text-sm text-muted-foreground">
                                    Move this candidate to another active batch
                                    with the same language and level.
                                </p>

                                {transferTargets.length === 0 ? (
                                    <p className="mt-4 text-sm text-muted-foreground">
                                        No other matching batches are available
                                        right now.
                                    </p>
                                ) : (
                                    <form
                                        className="mt-4 flex max-w-lg flex-col gap-4"
                                        onSubmit={(event) => {
                                            event.preventDefault();
                                            transferForm.post(
                                                `/recruiter/students/${profileId}/transfer`,
                                            );
                                        }}
                                    >
                                        <div className="grid gap-2">
                                            <Label htmlFor="target_batch_id">
                                                Target batch
                                            </Label>
                                            <select
                                                id="target_batch_id"
                                                className="flex h-10 w-full rounded-xl border border-input bg-background px-3 py-2 text-sm"
                                                value={
                                                    transferForm.data
                                                        .target_batch_id
                                                }
                                                onChange={(event) =>
                                                    transferForm.setData(
                                                        'target_batch_id',
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            >
                                                <option value="" disabled>
                                                    Select a batch
                                                </option>
                                                {transferTargets.map(
                                                    (target) => (
                                                        <option
                                                            key={target.id}
                                                            value={target.id}
                                                        >
                                                            {target.name} —{' '}
                                                            {target.center_name}{' '}
                                                            —{' '}
                                                            {
                                                                target.teacher_name
                                                            }{' '}
                                                            ({target.language}{' '}
                                                            {target.level})
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            <InputError
                                                message={
                                                    transferForm.errors
                                                        .target_batch_id
                                                }
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={
                                                transferForm.processing ||
                                                !transferForm.data
                                                    .target_batch_id
                                            }
                                        >
                                            {transferForm.processing
                                                ? 'Transferring…'
                                                : 'Transfer candidate'}
                                        </Button>
                                    </form>
                                )}
                            </div>
                        )}
                    </div>
                )}

                <div
                    className={cn(
                        'flex flex-col gap-4',
                        activeTab !== 'cv' && 'cv-print-only',
                    )}
                >
                    {activeTab === 'cv' && (
                        <div className="cv-print-hide flex items-center gap-2 max-sm:[&>div]:flex-1 max-sm:[&>div>button]:flex-1">
                            <div className="inline-flex rounded-xl border border-border p-1">
                                <button
                                    type="button"
                                    className={cn(
                                        'rounded-lg px-4 py-2 text-sm font-medium',
                                        viewVariant === 'basic'
                                            ? 'bg-primary text-primary-foreground'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                    onClick={() => {
                                        setViewVariant('basic');
                                        setPrintVariant('basic');
                                    }}
                                >
                                    Basic
                                </button>
                                <button
                                    type="button"
                                    className={cn(
                                        'rounded-lg px-4 py-2 text-sm font-medium',
                                        viewVariant === 'full'
                                            ? 'bg-primary text-primary-foreground'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                    onClick={() => {
                                        setViewVariant('full');
                                        setPrintVariant('full');
                                    }}
                                >
                                    Full
                                </button>
                            </div>

                            <div className="inline-flex rounded-xl border border-border p-1">
                                <button
                                    type="button"
                                    className={cn(
                                        'rounded-lg px-3 py-2 text-sm font-medium',
                                        locale === 'en'
                                            ? 'bg-muted text-foreground'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                    onClick={() => {
                                        setLocale('en');
                                        setTranslateError(null);
                                    }}
                                >
                                    EN
                                </button>
                                <button
                                    type="button"
                                    className={cn(
                                        'rounded-lg px-3 py-2 text-sm font-medium',
                                        locale === 'de'
                                            ? 'bg-muted text-foreground'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                    disabled={isTranslating}
                                    onClick={() => {
                                        void (async () => {
                                            try {
                                                await ensureGermanCv();
                                                setLocale('de');
                                            } catch {
                                                // error already stored
                                            }
                                        })();
                                    }}
                                >
                                    DE
                                </button>
                            </div>
                        </div>
                    )}

                    {viewVariant === 'basic' ? (
                        <BasicCvProfile cv={displayCv} locale={locale} />
                    ) : (
                        <div className="cv-print-hide">
                            <FullCvProfile
                                cv={displayCv}
                                locale={locale}
                                presentation={isPhone ? 'stack' : 'flipbook'}
                            />
                        </div>
                    )}

                    {viewVariant === 'basic' && printVariant === 'full' && (
                        <div className="cv-print-only" aria-hidden>
                            <FullCvProfile cv={displayCv} locale={locale} />
                        </div>
                    )}
                    {viewVariant === 'full' && printVariant === 'basic' && (
                        <div className="cv-print-only" aria-hidden>
                            <BasicCvProfile cv={displayCv} locale={locale} />
                        </div>
                    )}
                    {viewVariant === 'full' && printVariant === 'full' && (
                        <div className="cv-print-only" aria-hidden>
                            <FullCvProfile cv={displayCv} locale={locale} />
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

StudentShow.layout = {
    breadcrumbs: [],
};
