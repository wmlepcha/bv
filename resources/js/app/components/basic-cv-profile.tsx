import { Mail, MapPin, Phone  } from 'lucide-react';
import type {LucideIcon} from 'lucide-react';
import { useEffect, useState } from 'react';
import CvCreatedAtFooter from '@app/components/cv-created-at-footer';
import { groupBilingualForDisplay } from '@app/lib/bilingual-display';
import {
    cvLabels,
    localizeGender
    
} from '@app/lib/cv-labels';
import type {CvLocale} from '@app/lib/cv-labels';
import { toStorageSrc } from '@app/lib/cv-media';
import type { CvProfile } from '@app/types/cv';

const ACCENT = '#003399';

type Props = {
    cv: CvProfile;
    locale?: CvLocale;
};

function SectionHeading({ children }: { children: React.ReactNode }) {
    return (
        <h2 className="text-sm font-bold tracking-wide text-neutral-500 uppercase">
            {children}
        </h2>
    );
}

function SidebarHeading({ children }: { children: React.ReactNode }) {
    return (
        <p className="text-xs font-bold tracking-wide text-neutral-700 uppercase">
            {children}
        </p>
    );
}

function ContactRow({
    icon: Icon,
    children,
}: {
    icon: LucideIcon;
    children: React.ReactNode;
}) {
    return (
        <div className="flex items-start gap-2.5 text-sm leading-snug text-neutral-700">
            <Icon
                className="mt-0.5 size-4 shrink-0"
                style={{ color: ACCENT }}
                aria-hidden
            />
            <span className="min-w-0 break-words">{children}</span>
        </div>
    );
}

function PersonalFact({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <p className="text-sm leading-relaxed">
            <span className="text-neutral-500">{label}:</span>{' '}
            <span className="text-neutral-800">{value}</span>
        </p>
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
        <div className="size-36 shrink-0 overflow-hidden rounded-sm bg-neutral-200 ring-1 ring-black/10 print:size-28">
            {src && !failed ? (
                <img
                    src={src}
                    alt={alt}
                    className="block size-full object-cover"
                    onError={() => setFailed(true)}
                />
            ) : (
                <div className="flex size-full items-center justify-center px-2 text-center text-xs text-neutral-500">
                    {fallback}
                </div>
            )}
        </div>
    );
}

function PipeList({ items }: { items: string[] }) {
    return (
        <p className="cv-pipe-list text-sm leading-relaxed text-neutral-700">
            {items.map((item, index) => (
                <span key={`${item}-${index}`}>
                    {index > 0 && (
                        <span className="mx-1.5 text-neutral-400" aria-hidden>
                            |
                        </span>
                    )}
                    <span>{item}</span>
                </span>
            ))}
        </p>
    );
}

function TimelineList({ children }: { children: React.ReactNode }) {
    return (
        <ul className="cv-timeline relative space-y-5 print:space-y-3 before:absolute before:top-2 before:bottom-2 before:left-[4px] before:w-px before:bg-[#003399]">
            {children}
        </ul>
    );
}

function TimelineEntry({
    dateLine,
    title,
    subtitle,
    details,
}: {
    dateLine?: string | null;
    title: string;
    subtitle?: string | null;
    details?: React.ReactNode;
}) {
    return (
        <li className="cv-timeline-entry relative pl-5">
            <span
                className="absolute top-1.5 left-0 size-2.5 rounded-full"
                style={{ backgroundColor: ACCENT }}
                aria-hidden
            />
            {dateLine ? (
                <p
                    className="text-sm font-bold"
                    style={{ color: ACCENT }}
                >
                    {dateLine}
                </p>
            ) : null}
            <p className="text-sm font-bold text-neutral-900">{title}</p>
            {subtitle && (
                <p className="text-sm text-neutral-600">{subtitle}</p>
            )}
            {details}
        </li>
    );
}

export default function BasicCvProfile({ cv, locale = 'en' }: Props) {
    const labels = cvLabels[locale];
    const displayName =
        [cv.first_name, cv.last_name].filter(Boolean).join(' ') || cv.name;
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
    const experiences = vitae.is_newbie ? [] : (vitae.experiences ?? []);
    const qualifications = vitae.qualifications ?? [];
    const achievements = [
        ...(vitae.seminars ?? []),
        ...(vitae.presentations ?? []),
        ...(vitae.publications ?? []),
        ...(vitae.trainings ?? []),
    ]
        .map((entry) => {
            const topic = String(entry?.topic ?? '').trim();

            if (!topic) {
                return null;
            }

            const date = String(
                'date' in entry ? (entry.date ?? '') : '',
            ).trim();
            const location = [
                'organization' in entry ? entry.organization : null,
                'organization_location' in entry
                    ? entry.organization_location
                    : null,
                'location' in entry ? entry.location : null,
            ]
                .map((part) => String(part ?? '').trim())
                .filter(Boolean)
                .join(', ');

            return {
                topic,
                dateLine: [date, location].filter(Boolean).join(' '),
            };
        })
        .filter(
            (
                value,
            ): value is { topic: string; dateLine: string } =>
                value !== null,
        )
        .filter(
            (value, index, arr) =>
                arr.findIndex((item) => item.topic === value.topic) === index,
        );

    const bilingualEntries = vitae.bilingual ?? [];
    const languageGroups = groupBilingualForDisplay(bilingualEntries, labels);
    const fallbackLanguages = (cv.languages ?? []).filter(
        (value, index, arr): value is string =>
            Boolean(value) && arr.indexOf(value) === index,
    );
    const hasBilingualData = languageGroups.length > 0;

    const certifications = (vitae.certifications ?? [])
        .map((cert) => {
            const name = String(cert?.name ?? '').trim();

            if (!name) {
                return null;
            }

            return {
                name,
                description: String(cert?.description ?? '').trim() || null,
            };
        })
        .filter(
            (
                value,
            ): value is { name: string; description: string | null } =>
                value !== null,
        );

    const licenses = (vitae.licenses ?? [])
        .map((license) => {
            const title = String(license?.title ?? '').trim();

            if (!title) {
                return null;
            }

            const registration = String(
                license?.registration_number ?? '',
            ).trim();
            const period = [license?.issued_date, license?.valid_till]
                .map((part) => String(part ?? '').trim())
                .filter(Boolean)
                .join(' – ');

            return {
                title,
                subtitle: registration || null,
                dateLine: period || null,
            };
        })
        .filter(
            (
                value,
            ): value is {
                title: string;
                subtitle: string | null;
                dateLine: string | null;
            } => value !== null,
        );

    const skills = (Array.isArray(cv.skills) ? cv.skills : [])
        .filter(Boolean)
        .slice(0, 5);
    const hobbies = String(cv.hobbies ?? '')
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean)
        .slice(0, 5);

    return (
        <div className="cv-print-sheet cv-print-basic mx-auto flex w-full max-w-[210mm] min-h-[297mm] flex-col overflow-hidden rounded-2xl border border-border bg-white text-neutral-900 shadow-sm print:max-w-none print:min-h-[297mm] print:overflow-visible print:rounded-none print:border-0 print:shadow-none">
            <div className="grid min-h-0 flex-1 gap-0 print:grid-cols-[0.9fr_1.45fr] lg:grid-cols-[0.9fr_1.45fr]">
                <aside className="cv-sidebar space-y-6 bg-[#F2F2F2] p-7 sm:p-8 print:space-y-3 print:p-4">
                    <PassportPhoto
                        url={cv.passport_photo_url}
                        alt={`${displayName} ${labels.passportPhotoAlt}`}
                        fallback={labels.noPhoto}
                    />

                    <div className="space-y-1 text-left">
                        <h1 className="text-xl font-bold tracking-tight text-neutral-800 sm:text-2xl print:text-lg">
                            {displayName}
                        </h1>
                    </div>

                    <div className="space-y-0.5 text-left">
                        {cv.date_of_birth && (
                            <PersonalFact
                                label={labels.dob}
                                value={cv.date_of_birth}
                            />
                        )}
                        {cv.place_of_birth && (
                            <PersonalFact
                                label={labels.placeOfBirth}
                                value={cv.place_of_birth}
                            />
                        )}
                        {cv.nationality && (
                            <PersonalFact
                                label={labels.nationality}
                                value={cv.nationality}
                            />
                        )}
                        {cv.gender && (
                            <PersonalFact
                                label={labels.gender}
                                value={localizeGender(cv.gender, locale)}
                            />
                        )}
                    </div>

                    {(cv.current_address ||
                        cv.email ||
                        cv.mobile_whatsapp) && (
                        <div className="space-y-3">
                            <SidebarHeading>{labels.contact}</SidebarHeading>
                            <div className="space-y-2.5">
                                {cv.current_address && (
                                    <ContactRow icon={MapPin}>
                                        {cv.current_address}
                                    </ContactRow>
                                )}
                                {cv.email && (
                                    <ContactRow icon={Mail}>
                                        <a
                                            href={`mailto:${cv.email}`}
                                            className="underline decoration-neutral-400 underline-offset-2"
                                        >
                                            {cv.email}
                                        </a>
                                    </ContactRow>
                                )}
                                {cv.mobile_whatsapp && (
                                    <ContactRow icon={Phone}>
                                        {cv.mobile_whatsapp}
                                    </ContactRow>
                                )}
                            </div>
                        </div>
                    )}

                    <div className="space-y-2">
                        <SidebarHeading>{labels.skills}</SidebarHeading>
                        {skills.length === 0 ? (
                            <p className="text-sm text-neutral-500">
                                {labels.emptyDash}
                            </p>
                        ) : (
                            <PipeList items={skills} />
                        )}
                    </div>

                    <div className="space-y-2">
                        <SidebarHeading>{labels.hobbies}</SidebarHeading>
                        {hobbies.length === 0 ? (
                            <p className="text-sm text-neutral-500">
                                {labels.emptyDash}
                            </p>
                        ) : (
                            <PipeList items={hobbies} />
                        )}
                    </div>
                </aside>

                <div className="cv-main space-y-7 p-7 sm:p-8 print:space-y-3 print:p-4 lg:p-10">
                    {cv.introduction && (
                        <section className="space-y-3">
                            <SectionHeading>{labels.aboutMe}</SectionHeading>
                            <p className="text-sm leading-relaxed text-justify text-neutral-700">
                                {cv.introduction}
                            </p>
                        </section>
                    )}

                    <section className="space-y-3">
                        <SectionHeading>
                            {labels.workExperience}
                        </SectionHeading>
                        {vitae.is_newbie ? (
                            <div className="space-y-2 text-sm">
                                <p className="text-neutral-500">
                                    {labels.fresherNoExperience}
                                </p>
                                {cv.gap_reasons && (
                                    <p className="text-neutral-600">
                                        <span className="font-semibold text-neutral-800">
                                            {labels.currentPlans}
                                        </span>{' '}
                                        {cv.gap_reasons}
                                    </p>
                                )}
                            </div>
                        ) : experiences.length === 0 ? (
                            <p className="text-sm text-neutral-500">
                                {labels.noExperience}
                            </p>
                        ) : (
                            <TimelineList>
                                {experiences.map((job, index) => {
                                    const period =
                                        [job.start_date, job.end_date]
                                            .filter(Boolean)
                                            .join(' – ') ||
                                        job.duration ||
                                        '';
                                    const location = [job.city, job.state]
                                        .filter(Boolean)
                                        .join(', ');
                                    const dateLine = [period, location]
                                        .filter(Boolean)
                                        .join(' ');

                                    return (
                                        <TimelineEntry
                                            key={index}
                                            dateLine={dateLine}
                                            title={
                                                job.designation ||
                                                labels.organizationFallback
                                            }
                                            subtitle={[
                                                job.organization,
                                                job.department,
                                            ]
                                                .filter(Boolean)
                                                .join(' — ')}
                                            details={
                                                job.cases_handled ? (
                                                    <ul className="mt-1 list-disc space-y-0.5 pl-4 text-sm text-neutral-600">
                                                        <li>
                                                            {job.cases_handled}
                                                        </li>
                                                    </ul>
                                                ) : null
                                            }
                                        />
                                    );
                                })}
                            </TimelineList>
                        )}
                    </section>

                    <section className="space-y-3">
                        <SectionHeading>
                            {labels.educationAndTraining}
                        </SectionHeading>
                        {qualifications.length === 0 ? (
                            <p className="text-sm text-neutral-500">
                                {labels.noQualifications}
                            </p>
                        ) : (
                            <TimelineList>
                                {qualifications.map((item, index) => {
                                    const period = [
                                        item.start_date,
                                        item.end_date,
                                    ]
                                        .filter(Boolean)
                                        .join(' – ');
                                    const location = [
                                        item.city,
                                        item.state,
                                        item.country,
                                    ]
                                        .filter(Boolean)
                                        .join(', ');
                                    const dateLine = [period, location]
                                        .filter(Boolean)
                                        .join(' ');
                                    const institution = [
                                        item.college,
                                        item.university,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ');

                                    return (
                                        <TimelineEntry
                                            key={index}
                                            dateLine={dateLine}
                                            title={
                                                item.title ||
                                                labels.qualificationFallback
                                            }
                                            subtitle={institution}
                                        />
                                    );
                                })}
                            </TimelineList>
                        )}
                    </section>

                    <section className="space-y-3">
                        <SectionHeading>
                            {labels.languageSkills}
                        </SectionHeading>
                        {hasBilingualData ? (
                            <div className="space-y-2 text-sm text-neutral-700">
                                <p className="font-bold text-neutral-800">
                                    {labels.languagesKnown}
                                </p>
                                <ul className="space-y-0.5">
                                    {languageGroups.map((group, index) => (
                                        <li key={`lang-group-${index}`}>
                                            {group.label
                                                ? `${group.label}: ${group.text}`
                                                : group.text}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ) : fallbackLanguages.length > 0 ? (
                            <div className="space-y-2 text-sm text-neutral-700">
                                <p className="font-bold text-neutral-800">
                                    {labels.languagesKnown}
                                </p>
                                <PipeList items={fallbackLanguages} />
                            </div>
                        ) : (
                            <p className="text-sm text-neutral-500">
                                {labels.emptyDash}
                            </p>
                        )}
                    </section>

                    <section className="space-y-3">
                        <SectionHeading>
                            {labels.certification}
                        </SectionHeading>
                        {certifications.length === 0 ? (
                            <p className="text-sm text-neutral-500">
                                {labels.emptyDash}
                            </p>
                        ) : (
                            <TimelineList>
                                {certifications.map((item, index) => (
                                    <TimelineEntry
                                        key={`cert-${index}`}
                                        title={item.name}
                                        subtitle={item.description}
                                    />
                                ))}
                            </TimelineList>
                        )}
                    </section>

                    <section className="space-y-3">
                        <SectionHeading>{labels.license}</SectionHeading>
                        {licenses.length === 0 ? (
                            <p className="text-sm text-neutral-500">
                                {labels.emptyDash}
                            </p>
                        ) : (
                            <TimelineList>
                                {licenses.map((item, index) => (
                                    <TimelineEntry
                                        key={`license-${index}`}
                                        dateLine={item.dateLine}
                                        title={item.title}
                                        subtitle={item.subtitle}
                                    />
                                ))}
                            </TimelineList>
                        )}
                    </section>

                    <section className="space-y-3">
                        <SectionHeading>{labels.achievements}</SectionHeading>
                        {achievements.length === 0 ? (
                            <p className="text-sm text-neutral-500">
                                {labels.emptyDash}
                            </p>
                        ) : (
                            <TimelineList>
                                {achievements.map((item, index) => (
                                    <TimelineEntry
                                        key={`achievement-${index}`}
                                        dateLine={item.dateLine}
                                        title={item.topic}
                                    />
                                ))}
                            </TimelineList>
                        )}
                    </section>
                </div>
            </div>
            <CvCreatedAtFooter locale={locale} />
        </div>
    );
}
