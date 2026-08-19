import { ChevronLeft, ChevronRight } from 'lucide-react';
import {
    forwardRef,
    useCallback,
    useEffect,
    useRef,
    useState,
} from 'react';
import type { ReactNode } from 'react';
import HTMLFlipBook from 'react-pageflip';
import CvCreatedAtFooter from '@app/components/cv-created-at-footer';
import { groupBilingualForDisplay } from '@app/lib/bilingual-display';
import {
    cvLabels,
    localizeCivilStatus,
    type CvLocale,
} from '@app/lib/cv-labels';
import { toStorageSrc } from '@app/lib/cv-media';
import type {
    CvActivityEntry,
    CvCertification,
    CvExperience,
    CvLicense,
    CvProfile,
    CvQualification,
    CvTraining,
} from '@app/types/cv';
import { cn } from '@/lib/utils';

const ACCENT = '#003399';
const PANEL = '#F2F2F2';
const A4_RATIO = 297 / 210;
const FLIPBOOK_PAGE_COUNT = 3;

type Props = {
    cv: CvProfile;
    locale?: CvLocale;
    presentation?: 'stack' | 'flipbook';
};

function joinParts(parts: Array<string | null | undefined>): string {
    return parts
        .map((part) => (typeof part === 'string' ? part.trim() : ''))
        .filter(Boolean)
        .join(', ');
}

function formatDateRange(
    start: string | null | undefined,
    end: string | null | undefined,
): string {
    const from = (start ?? '').trim();
    const to = (end ?? '').trim();

    if (from && to) {
        return `${from} – ${to}`;
    }

    return from || to || '';
}

function hasText(value: string | null | undefined): boolean {
    return Boolean(value && value.trim());
}

function sheetClassName(
    presentation: 'stack' | 'flipbook',
    extra = '',
): string {
    return cn(
        'cv-print-sheet cv-full-sheet flex w-full flex-col border border-border border-t-[3px] bg-white text-neutral-900',
        presentation === 'stack'
            ? 'min-h-[297mm] overflow-hidden rounded-2xl shadow-sm print:max-w-none print:overflow-visible print:rounded-none print:border-0 print:border-t-[3px] print:shadow-none'
            : 'h-full max-h-full overflow-hidden rounded-none shadow-none',
        extra,
    );
}

function DetailRow({
    label,
    value,
}: {
    label: string;
    value: ReactNode;
}) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return (
        <div className="cv-full-detail-row grid grid-cols-[minmax(8.5rem,10.5rem)_1fr] items-baseline gap-x-8 sm:grid-cols-[minmax(10rem,11.5rem)_1fr]">
            <dt className="cv-full-detail-label text-[11px] font-medium tracking-[0.06em] text-neutral-500 uppercase">
                {label}
            </dt>
            <dd className="cv-full-detail-value min-w-0 text-[14px] leading-snug font-medium whitespace-pre-line text-neutral-800">
                {value}
            </dd>
        </div>
    );
}

function SectionHeading({ children }: { children: ReactNode }) {
    return (
        <h2
            className="cv-full-section-heading text-sm font-bold tracking-wide text-neutral-500 uppercase"
            style={{ borderBottomColor: ACCENT }}
        >
            {children}
        </h2>
    );
}

function SheetBody({
    children,
    className = '',
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <div className={`cv-full-sheet-body ${className}`.trim()}>
            {children}
        </div>
    );
}

function PassportPhoto({
    url,
    alt,
    fallback,
}: {
    url: string | null;
    alt: string;
    fallback: string;
}) {
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        setFailed(false);
    }, [url]);

    const src = toStorageSrc(url);

    return (
        <div className="cv-full-passport-photo size-36 shrink-0 overflow-hidden rounded-sm bg-neutral-200 ring-1 ring-black/10 sm:size-40">
            {src && !failed ? (
                <img
                    src={src}
                    alt={alt}
                    className="block size-full object-cover"
                    onError={() => setFailed(true)}
                />
            ) : (
                <div
                    className="flex size-full items-center justify-center px-3 text-center text-xs tracking-wide text-neutral-500 uppercase"
                    style={{ backgroundColor: PANEL }}
                >
                    {fallback}
                </div>
            )}
        </div>
    );
}

function FullBodyPage({
    url,
    alt,
    placeholder,
    locale,
    presentation,
}: {
    url: string | null;
    alt: string;
    placeholder: string;
    locale: CvLocale;
    presentation: 'stack' | 'flipbook';
}) {
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        setFailed(false);
    }, [url]);

    const src = toStorageSrc(url);
    const showImage = Boolean(src && !failed);

    return (
        <div
            className={sheetClassName(
                presentation,
                'cv-full-sheet-photo' +
                    (presentation === 'stack' ? ' max-w-[210mm]' : ''),
            )}
            style={{ borderTopColor: ACCENT }}
        >
            <SheetBody className="flex min-h-0 flex-1 flex-col items-center justify-center">
                {showImage ? (
                    <img
                        src={src!}
                        alt={alt}
                        className="cv-full-body-image max-h-full max-w-full object-contain"
                        onError={() => setFailed(true)}
                    />
                ) : (
                    <div
                        className={cn(
                            'flex w-full flex-1 items-center justify-center rounded-sm border border-dashed border-neutral-300 bg-neutral-50 px-6 text-center text-sm text-neutral-500',
                            presentation === 'stack'
                                ? 'min-h-[200mm] print:min-h-0'
                                : 'min-h-0',
                        )}
                    >
                        {placeholder}
                    </div>
                )}
            </SheetBody>
            <CvCreatedAtFooter
                locale={locale}
                className="cv-full-sheet-footer"
            />
        </div>
    );
}

function QualificationEntry({
    item,
    labels,
}: {
    item: CvQualification;
    labels: (typeof cvLabels)['en'];
}) {
    const dates = formatDateRange(item.start_date, item.end_date);
    const collegeLine = joinParts([
        item.college,
        item.city,
        item.state,
        item.country,
    ]);
    const universityLine = joinParts([
        item.university || item.board,
        item.city,
        item.state,
        item.country,
    ]);

    if (
        !dates &&
        !hasText(item.title) &&
        !collegeLine &&
        !universityLine &&
        !hasText(item.language_of_instruction)
    ) {
        return null;
    }

    return (
        <div className="cv-full-entry grid grid-cols-1 gap-1.5 sm:grid-cols-[minmax(9.5rem,11.5rem)_1fr] sm:gap-x-8">
            <p className="text-[13px] font-medium text-neutral-500">{dates}</p>
            <div className="space-y-1.5 text-[14px] leading-[1.55] text-neutral-800">
                {hasText(item.title) && (
                    <p className="font-medium text-neutral-900">{item.title}</p>
                )}
                {collegeLine && <p className="text-neutral-700">{collegeLine}</p>}
                {universityLine && universityLine !== collegeLine && (
                    <p className="text-neutral-700">{universityLine}</p>
                )}
                {hasText(item.language_of_instruction) && (
                    <p className="text-[13px] text-neutral-500">
                        {labels.languageOfInstruction}:{' '}
                        {item.language_of_instruction}
                    </p>
                )}
            </div>
        </div>
    );
}

function TrainingEntry({
    date,
    topic,
    place,
}: {
    date: string | null | undefined;
    topic: string | null | undefined;
    place: string | null | undefined;
}) {
    if (!hasText(date) && !hasText(topic) && !hasText(place)) {
        return null;
    }

    return (
        <div className="cv-full-entry grid grid-cols-1 gap-1.5 sm:grid-cols-[minmax(9.5rem,11.5rem)_1fr] sm:gap-x-8">
            <p className="text-[13px] font-medium text-neutral-500">
                {(date ?? '').trim()}
            </p>
            <div className="space-y-1.5 text-[14px] leading-[1.55] text-neutral-800">
                {hasText(topic) && (
                    <p className="font-medium text-neutral-900">{topic}</p>
                )}
                {hasText(place) && (
                    <p className="text-neutral-700">{place}</p>
                )}
            </div>
        </div>
    );
}

function ExperienceEntry({
    job,
    labels,
}: {
    job: CvExperience;
    labels: (typeof cvLabels)['en'];
}) {
    const dates =
        formatDateRange(job.start_date, job.end_date) ||
        (job.duration ?? '').trim();
    const role = joinParts([job.designation, job.department]);
    const org = joinParts([job.organization, job.city, job.state]);

    const details: Array<{ label: string; value: string }> = [];
    if (hasText(job.bed_capacity)) {
        details.push({ label: labels.bedCapacity, value: job.bed_capacity! });
    }
    if (hasText(job.departments_offered)) {
        details.push({
            label: labels.departmentsOffered,
            value: job.departments_offered!,
        });
    }
    if (hasText(job.accreditations)) {
        details.push({
            label: labels.accreditations,
            value: job.accreditations!,
        });
    }
    if (hasText(job.critical_cases)) {
        details.push({
            label: labels.criticalCases,
            value: job.critical_cases!,
        });
    }
    if (hasText(job.medical_equipment)) {
        details.push({
            label: labels.medicalEquipment,
            value: job.medical_equipment!,
        });
    }
    if (hasText(job.cases_handled)) {
        details.push({ label: labels.casesHandled, value: job.cases_handled! });
    }

    if (!dates && !role && !org && details.length === 0) {
        return null;
    }

    return (
        <div className="cv-full-entry space-y-2.5">
            {dates && (
                <p className="text-[13px] font-medium text-neutral-500">
                    {dates}
                </p>
            )}
            {role && (
                <p className="text-[14px] font-medium text-neutral-900">{role}</p>
            )}
            {org && (
                <p className="text-[14px] leading-[1.55] text-neutral-700">
                    {org}
                </p>
            )}
            {details.map((detail) => (
                <p
                    key={detail.label}
                    className="text-[13px] leading-[1.55] text-neutral-600"
                >
                    <span className="text-neutral-500">{detail.label}:</span>{' '}
                    {detail.value}
                </p>
            ))}
            {!job.has_experience_letter && (
                <p className="text-[13px] italic text-neutral-400">
                    {labels.noExperienceLetter}
                </p>
            )}
        </div>
    );
}

function activityPlace(entry: CvActivityEntry): string {
    return joinParts([entry.organization, entry.location]);
}

function trainingPlace(entry: CvTraining): string {
    return (entry.organization_location ?? '').trim();
}

type SheetModel = {
    cv: CvProfile;
    locale: CvLocale;
    labels: (typeof cvLabels)['en'];
    displayName: string;
    civilStatus: string | null;
    qualifications: CvQualification[];
    trainings: CvTraining[];
    seminars: CvActivityEntry[];
    licenses: CvLicense[];
    certifications: CvCertification[];
    presentations: CvActivityEntry[];
    publications: CvActivityEntry[];
    experiences: CvExperience[];
    isFresher: boolean;
    languageGroups: ReturnType<typeof groupBilingualForDisplay>;
    fallbackLanguages: string[];
    skills: string[];
    hobbies: string[];
};

function PersonalSheet({
    model,
    presentation,
}: {
    model: SheetModel;
    presentation: 'stack' | 'flipbook';
}) {
    const { cv, labels, displayName, civilStatus, locale } = model;

    return (
        <div
            className={sheetClassName(presentation, 'cv-full-sheet-personal')}
            style={{ borderTopColor: ACCENT }}
        >
            <SheetBody className="cv-full-personal-layout">
                <div className="cv-full-personal-main min-w-0">
                    {displayName ? (
                        <h1
                            className="cv-full-personal-name mb-6 pb-3 text-xl font-bold tracking-tight text-neutral-800 sm:text-2xl print:mb-4 print:text-lg"
                            style={{
                                borderBottom: `2px solid ${ACCENT}`,
                            }}
                        >
                            {displayName}
                        </h1>
                    ) : null}

                    <dl className="cv-full-personal-list flex w-full min-w-0 flex-col">
                        <DetailRow
                            label={labels.lastName}
                            value={cv.last_name}
                        />
                        <DetailRow
                            label={labels.firstName}
                            value={cv.first_name}
                        />
                        <DetailRow label={labels.dob} value={cv.date_of_birth} />
                        <DetailRow
                            label={labels.citizenship}
                            value={cv.nationality}
                        />
                        <DetailRow
                            label={labels.civilStatus}
                            value={civilStatus}
                        />
                        <DetailRow
                            label={labels.placeOfBirth}
                            value={cv.place_of_birth}
                        />
                        <DetailRow
                            label={labels.currentAddress}
                            value={cv.current_address}
                        />
                        <DetailRow
                            label={labels.homeAddress}
                            value={cv.permanent_address}
                        />
                        <DetailRow
                            label={labels.emailAddress}
                            value={
                                cv.email ? (
                                    <a
                                        href={`mailto:${cv.email}`}
                                        className="text-neutral-800 underline underline-offset-[3px] transition-colors"
                                        style={{
                                            textDecorationColor: ACCENT,
                                        }}
                                    >
                                        {cv.email}
                                    </a>
                                ) : null
                            }
                        />
                        <DetailRow
                            label={labels.mobileNumber}
                            value={cv.mobile_whatsapp}
                        />
                        <DetailRow
                            label={labels.driversLicense}
                            value={cv.drivers_license}
                        />
                    </dl>
                </div>

                <div className="cv-full-passport-wrap shrink-0">
                    <PassportPhoto
                        url={cv.passport_photo_url}
                        alt={labels.passportPhotoAlt}
                        fallback={labels.noPhoto}
                    />
                </div>
            </SheetBody>
            <CvCreatedAtFooter
                locale={locale}
                className="cv-full-sheet-footer"
            />
        </div>
    );
}

function ContentSheet({
    model,
    presentation,
}: {
    model: SheetModel;
    presentation: 'stack' | 'flipbook';
}) {
    const {
        cv,
        locale,
        labels,
        qualifications,
        trainings,
        seminars,
        licenses,
        certifications,
        presentations,
        publications,
        experiences,
        isFresher,
        languageGroups,
        fallbackLanguages,
        skills,
        hobbies,
    } = model;

    return (
        <div
            className={sheetClassName(
                presentation,
                cn(
                    'cv-full-sheet-content',
                    presentation === 'stack' &&
                        'print:max-w-none print:min-h-0 print:overflow-visible print:rounded-none print:border-0 print:border-t-[3px] print:shadow-none',
                    presentation === 'flipbook' && 'overflow-y-auto',
                ),
            )}
            style={{ borderTopColor: ACCENT }}
        >
            <SheetBody className="cv-full-content-stack flex min-h-0 flex-1 flex-col">
                {hasText(cv.introduction) && (
                    <section className="cv-full-section space-y-3">
                        <SectionHeading>{labels.profile}</SectionHeading>
                        <p className="cv-full-prose text-[14px] leading-[1.65] whitespace-pre-line text-neutral-700">
                            {cv.introduction}
                        </p>
                    </section>
                )}

                {qualifications.length > 0 && (
                    <section className="cv-full-section space-y-5">
                        <SectionHeading>
                            {labels.educationalBackground}
                        </SectionHeading>
                        <div className="cv-full-entry-list space-y-6">
                            {qualifications.map((item, index) => (
                                <QualificationEntry
                                    key={`qual-${index}`}
                                    item={item}
                                    labels={labels}
                                />
                            ))}
                        </div>
                    </section>
                )}

                {(trainings.length > 0 || seminars.length > 0) && (
                    <section className="cv-full-section space-y-5">
                        <SectionHeading>
                            {labels.trainingAndSeminars}
                        </SectionHeading>
                        <div className="cv-full-entry-list space-y-6">
                            {trainings.map((item, index) => (
                                <TrainingEntry
                                    key={`train-${index}`}
                                    date={item.date}
                                    topic={item.topic}
                                    place={trainingPlace(item)}
                                />
                            ))}
                            {seminars.map((item, index) => (
                                <TrainingEntry
                                    key={`sem-${index}`}
                                    date={item.date}
                                    topic={item.topic}
                                    place={activityPlace(item)}
                                />
                            ))}
                        </div>
                    </section>
                )}

                {licenses.length > 0 && (
                    <section className="cv-full-section space-y-5">
                        <SectionHeading>
                            {labels.licensesAndQualifications}
                        </SectionHeading>
                        <ul className="list-disc space-y-4 pl-5 text-[14px] leading-[1.55] text-neutral-700 marker:text-neutral-300">
                            {licenses.map((item, index) => (
                                <li
                                    key={`lic-${index}`}
                                    className="cv-full-entry space-y-1 pl-1"
                                >
                                    {hasText(item.title) && (
                                        <p className="font-medium text-neutral-900">
                                            {item.title}
                                        </p>
                                    )}
                                    {hasText(item.registration_number) && (
                                        <p>
                                            {labels.registrationNumber}:{' '}
                                            {item.registration_number}
                                        </p>
                                    )}
                                    {hasText(item.issued_date) && (
                                        <p>
                                            {labels.issuedDate}:{' '}
                                            {item.issued_date}
                                        </p>
                                    )}
                                    {hasText(item.valid_till) && (
                                        <p>
                                            {labels.validTill}:{' '}
                                            {item.valid_till}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {certifications.length > 0 && (
                    <section className="cv-full-section space-y-5">
                        <SectionHeading>{labels.certifications}</SectionHeading>
                        <ul className="list-disc space-y-4 pl-5 text-[14px] leading-[1.55] text-neutral-700 marker:text-neutral-300">
                            {certifications.map((item, index) => (
                                <li
                                    key={`cert-${index}`}
                                    className="cv-full-entry space-y-1 pl-1"
                                >
                                    {hasText(item.name) && (
                                        <p className="font-medium text-neutral-900">
                                            {item.name}
                                        </p>
                                    )}
                                    {hasText(item.description) && (
                                        <p>{item.description}</p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {presentations.length > 0 && (
                    <section className="cv-full-section space-y-5">
                        <SectionHeading>{labels.presentations}</SectionHeading>
                        <div className="cv-full-entry-list space-y-6">
                            {presentations.map((item, index) => (
                                <TrainingEntry
                                    key={`pres-${index}`}
                                    date={item.date}
                                    topic={item.topic}
                                    place={activityPlace(item)}
                                />
                            ))}
                        </div>
                    </section>
                )}

                {publications.length > 0 && (
                    <section className="cv-full-section space-y-5">
                        <SectionHeading>{labels.publications}</SectionHeading>
                        <div className="cv-full-entry-list space-y-6">
                            {publications.map((item, index) => (
                                <TrainingEntry
                                    key={`pub-${index}`}
                                    date={item.date}
                                    topic={item.topic}
                                    place={activityPlace(item)}
                                />
                            ))}
                        </div>
                    </section>
                )}

                <section className="cv-full-section space-y-5">
                    <SectionHeading>
                        {labels.professionalExperience}
                    </SectionHeading>
                    {isFresher ? (
                        <p className="text-[14px] leading-[1.65] text-neutral-700">
                            {labels.fullFresherNoExperience}
                        </p>
                    ) : (
                        <div className="cv-full-entry-list space-y-7">
                            {experiences.map((job, index) => (
                                <ExperienceEntry
                                    key={`exp-${index}`}
                                    job={job}
                                    labels={labels}
                                />
                            ))}
                        </div>
                    )}
                </section>

                {hasText(cv.gap_reasons) && (
                    <section className="cv-full-section space-y-3">
                        <SectionHeading>{labels.reasonsForGap}</SectionHeading>
                        <p className="text-[14px] leading-[1.65] whitespace-pre-line text-neutral-700">
                            {cv.gap_reasons}
                        </p>
                    </section>
                )}

                {(languageGroups.length > 0 ||
                    fallbackLanguages.length > 0) && (
                    <section className="cv-full-section space-y-5">
                        <SectionHeading>{labels.languageSkills}</SectionHeading>
                        <div className="space-y-2 text-[14px] leading-[1.55] text-neutral-700">
                            <p className="font-bold text-neutral-800">
                                {labels.languagesKnown}
                            </p>
                            {languageGroups.length > 0 ? (
                                <ul className="list-none space-y-1.5">
                                    {languageGroups.map((group, index) => (
                                        <li key={`lang-group-${index}`}>
                                            {group.label
                                                ? `${group.label}: ${group.text}`
                                                : group.text}
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <ul className="list-disc space-y-2.5 pl-5 marker:text-neutral-300">
                                    {fallbackLanguages.map((item, index) => (
                                        <li
                                            key={`lang-f-${index}`}
                                            className="pl-1"
                                        >
                                            {item}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </section>
                )}

                {skills.length > 0 && (
                    <section className="cv-full-section space-y-5">
                        <SectionHeading>{labels.skills}</SectionHeading>
                        <ul className="list-disc space-y-2.5 pl-5 text-[14px] leading-[1.55] text-neutral-700 marker:text-neutral-300">
                            {skills.map((item, index) => (
                                <li key={`skill-${index}`} className="pl-1">
                                    {item}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {hobbies.length > 0 && (
                    <section className="cv-full-section space-y-5">
                        <SectionHeading>{labels.hobbies}</SectionHeading>
                        <ul className="list-disc space-y-2.5 pl-5 text-[14px] leading-[1.55] text-neutral-700 marker:text-neutral-300">
                            {hobbies.map((item, index) => (
                                <li key={`hobby-${index}`} className="pl-1">
                                    {item}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </SheetBody>
            <CvCreatedAtFooter
                locale={locale}
                className="cv-full-sheet-footer"
            />
        </div>
    );
}

function FullCvSheets({
    model,
    presentation,
}: {
    model: SheetModel;
    presentation: 'stack' | 'flipbook';
}) {
    return (
        <>
            <PersonalSheet model={model} presentation={presentation} />
            <FullBodyPage
                url={model.cv.full_body_photo_url}
                alt={model.labels.fullBodyPhotoAlt}
                placeholder={model.labels.fullBodyPlaceholder}
                locale={model.locale}
                presentation={presentation}
            />
            <ContentSheet model={model} presentation={presentation} />
        </>
    );
}

const FlipPage = forwardRef<
    HTMLDivElement,
    { children: ReactNode }
>(function FlipPage({ children }, ref) {
    return (
        <div ref={ref} className="h-full w-full overflow-hidden bg-white">
            {children}
        </div>
    );
});

function FullCvFlipbook({ model }: { model: SheetModel }) {
    const containerRef = useRef<HTMLDivElement>(null);
    const bookRef = useRef<{
        pageFlip: () => {
            flipNext: () => void;
            flipPrev: () => void;
            getCurrentPageIndex: () => number;
            getPageCount: () => number;
        };
    } | null>(null);
    const [dims, setDims] = useState({ width: 595, height: 842 });
    const [pageIndex, setPageIndex] = useState(0);
    const [ready, setReady] = useState(false);

    useEffect(() => {
        const el = containerRef.current;
        if (!el) {
            return;
        }

        const update = () => {
            const available = el.clientWidth;
            const width = Math.max(280, Math.min(Math.floor(available), 794));
            const height = Math.round(width * A4_RATIO);
            setDims({ width, height });
            setReady(true);
        };

        update();
        const observer = new ResizeObserver(update);
        observer.observe(el);

        return () => observer.disconnect();
    }, []);

    const onFlip = useCallback((event: { data: number }) => {
        setPageIndex(event.data);
    }, []);

    const flipPrev = () => {
        bookRef.current?.pageFlip()?.flipPrev();
    };

    const flipNext = () => {
        bookRef.current?.pageFlip()?.flipNext();
    };

    return (
        <div className="cv-full-flipbook mx-auto flex w-full max-w-[210mm] flex-col gap-4">
            <div ref={containerRef} className="w-full">
                {ready ? (
                    <div
                        className="mx-auto overflow-hidden rounded-sm shadow-md ring-1 ring-black/10"
                        style={{ width: dims.width, height: dims.height }}
                    >
                        <HTMLFlipBook
                            key={`${model.locale}-${dims.width}`}
                            ref={bookRef}
                            className="cv-full-flipbook-book"
                            style={{}}
                            width={dims.width}
                            height={dims.height}
                            size="fixed"
                            minWidth={280}
                            maxWidth={794}
                            minHeight={Math.round(280 * A4_RATIO)}
                            maxHeight={Math.round(794 * A4_RATIO)}
                            drawShadow
                            flippingTime={700}
                            usePortrait
                            startZIndex={0}
                            autoSize={false}
                            maxShadowOpacity={0.35}
                            showCover={false}
                            mobileScrollSupport
                            clickEventForward
                            useMouseEvents
                            swipeDistance={30}
                            showPageCorners
                            disableFlipByClick={false}
                            startPage={0}
                            onFlip={onFlip}
                        >
                            <FlipPage>
                                <PersonalSheet
                                    model={model}
                                    presentation="flipbook"
                                />
                            </FlipPage>
                            <FlipPage>
                                <FullBodyPage
                                    url={model.cv.full_body_photo_url}
                                    alt={model.labels.fullBodyPhotoAlt}
                                    placeholder={
                                        model.labels.fullBodyPlaceholder
                                    }
                                    locale={model.locale}
                                    presentation="flipbook"
                                />
                            </FlipPage>
                            <FlipPage>
                                <ContentSheet
                                    model={model}
                                    presentation="flipbook"
                                />
                            </FlipPage>
                        </HTMLFlipBook>
                    </div>
                ) : (
                    <div
                        className="mx-auto w-full max-w-[210mm] animate-pulse rounded-sm bg-muted/40"
                        style={{ aspectRatio: `210 / 297` }}
                    />
                )}
            </div>

            <div className="cv-print-hide flex items-center justify-center gap-3">
                <button
                    type="button"
                    onClick={flipPrev}
                    disabled={pageIndex <= 0}
                    className="inline-flex size-9 items-center justify-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:pointer-events-none disabled:opacity-40"
                    aria-label="Previous page"
                >
                    <ChevronLeft className="size-4" />
                </button>
                <p className="min-w-[5.5rem] text-center text-sm text-muted-foreground">
                    {pageIndex + 1} / {FLIPBOOK_PAGE_COUNT}
                </p>
                <button
                    type="button"
                    onClick={flipNext}
                    disabled={pageIndex >= FLIPBOOK_PAGE_COUNT - 1}
                    className="inline-flex size-9 items-center justify-center rounded-lg border border-border text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:pointer-events-none disabled:opacity-40"
                    aria-label="Next page"
                >
                    <ChevronRight className="size-4" />
                </button>
            </div>
        </div>
    );
}

function buildSheetModel(cv: CvProfile, locale: CvLocale): SheetModel {
    const labels = cvLabels[locale];
    const vitae = cv.vitae ?? {
        is_newbie: false,
        experiences: [],
        qualifications: [],
        trainings: [],
        seminars: [],
        presentations: [],
        publications: [],
        certifications: [],
        licenses: [],
        bilingual: [],
    };

    const qualifications = (vitae.qualifications ?? []).filter(
        (item) =>
            hasText(item.title) ||
            hasText(item.college) ||
            hasText(item.university) ||
            hasText(item.start_date) ||
            hasText(item.end_date),
    );

    const trainings = (vitae.trainings ?? []).filter(
        (item) =>
            hasText(item.topic) ||
            hasText(item.date) ||
            hasText(item.organization_location),
    );

    const seminars = (vitae.seminars ?? []).filter(
        (item) =>
            hasText(item.topic) ||
            hasText(item.date) ||
            hasText(item.organization) ||
            hasText(item.location),
    );

    const licenses = (vitae.licenses ?? []).filter(
        (item) =>
            hasText(item.title) ||
            hasText(item.registration_number) ||
            hasText(item.issued_date) ||
            hasText(item.valid_till),
    );

    const certifications = (vitae.certifications ?? []).filter(
        (item) => hasText(item.name) || hasText(item.description),
    );

    const presentations = (vitae.presentations ?? []).filter(
        (item) =>
            hasText(item.topic) ||
            hasText(item.date) ||
            hasText(item.organization) ||
            hasText(item.location),
    );

    const publications = (vitae.publications ?? []).filter(
        (item) =>
            hasText(item.topic) ||
            hasText(item.date) ||
            hasText(item.organization) ||
            hasText(item.location),
    );

    const experiences = (vitae.experiences ?? []).filter(
        (job) =>
            hasText(job.designation) ||
            hasText(job.organization) ||
            hasText(job.duration) ||
            hasText(job.start_date) ||
            hasText(job.end_date),
    );

    const isFresher = Boolean(vitae.is_newbie) || experiences.length === 0;

    const bilingual = (vitae.bilingual ?? []).filter(
        (item) => hasText(item.language) || hasText(item.proficiency),
    );
    const languageGroups = groupBilingualForDisplay(bilingual, labels);

    const fallbackLanguages = (cv.languages ?? []).filter((item) =>
        hasText(item),
    );

    const skills = (Array.isArray(cv.skills) ? cv.skills : []).filter((item) =>
        hasText(item),
    );

    const hobbies = String(cv.hobbies ?? '')
        .split(/[,;\n]+/)
        .map((item) => item.trim())
        .filter(Boolean);

    const civilStatus = localizeCivilStatus(cv.civil_status, locale);
    const displayName =
        [cv.first_name, cv.last_name].filter(Boolean).join(' ') ||
        [cv.last_name, cv.first_name].filter(Boolean).join(' ') ||
        '';

    return {
        cv,
        locale,
        labels,
        displayName,
        civilStatus,
        qualifications,
        trainings,
        seminars,
        licenses,
        certifications,
        presentations,
        publications,
        experiences,
        isFresher,
        languageGroups,
        fallbackLanguages,
        skills,
        hobbies,
    };
}

export default function FullCvProfile({
    cv,
    locale = 'en',
    presentation = 'stack',
}: Props) {
    const model = buildSheetModel(cv, locale);

    if (presentation === 'flipbook') {
        return <FullCvFlipbook model={model} />;
    }

    return (
        <div className="cv-full-document cv-print-full mx-auto flex w-full max-w-[210mm] flex-col gap-6 print:max-w-none print:gap-0">
            {/* Repeats on every printed page; hidden on screen */}
            <div className="cv-full-fixed-footer" aria-hidden>
                <CvCreatedAtFooter locale={locale} />
            </div>

            <FullCvSheets model={model} presentation="stack" />
        </div>
    );
}
