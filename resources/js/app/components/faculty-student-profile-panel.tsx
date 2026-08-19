import { useState } from 'react';
import {
    ExternalLink,
    FileText,
    Image as ImageIcon,
    Mail,
    MapPin,
    Phone,
} from 'lucide-react';
import type { CvBilingualEntry, CvProfile } from '@app/types/cv';
import { BRAND_NAME_TM } from '@shared/lib/brand';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

type Props = {
    cv: CvProfile;
};

type DocumentItem = {
    label: string;
    href: string;
    kind: 'file' | 'image' | 'link';
};

type PreviewMode = 'image' | 'pdf';

type MediaPreview = {
    src: string;
    label: string;
    mode: PreviewMode;
};

type LanguageLevel =
    | 'Beginner'
    | 'Intermediate'
    | 'Professional'
    | 'Native'
    | 'Unknown';

type LanguageRow = {
    language: string;
    level: LanguageLevel;
    levelLabel: string;
    percent: number;
    barClassName: string;
};

const IMAGE_EXT_PATTERN = /\.(png|jpe?g|webp|gif|bmp|svg)(\?.*)?$/i;
const PDF_EXT_PATTERN = /\.pdf(\?.*)?$/i;

const LEVEL_META: Record<
    LanguageLevel,
    { percent: number; barClassName: string }
> = {
    Beginner: {
        percent: 25,
        barClassName: 'bg-brand-warm-light',
    },
    Intermediate: {
        percent: 50,
        barClassName: 'bg-brand-cool-light',
    },
    Professional: {
        percent: 75,
        barClassName: 'bg-brand-cool',
    },
    Native: {
        percent: 100,
        barClassName: 'bg-brand-cool-dark',
    },
    Unknown: {
        percent: 15,
        barClassName: 'bg-muted-foreground/40',
    },
};

function normalizeLevel(raw: string | null | undefined): LanguageLevel {
    const value = String(raw ?? '')
        .trim()
        .toLowerCase();

    if (!value) {
        return 'Unknown';
    }

    if (value.includes('native') || value.includes('mother')) {
        return 'Native';
    }

    if (value.includes('professional') || value.includes('fluent') || value.includes('advanced')) {
        return 'Professional';
    }

    if (value.includes('intermediate')) {
        return 'Intermediate';
    }

    if (value.includes('beginner') || value.includes('basic') || value.includes('elementary')) {
        return 'Beginner';
    }

    return 'Unknown';
}

function makeLanguageRow(language: string, proficiency: string | null | undefined): LanguageRow | null {
    const name = language.trim();

    if (!name) {
        return null;
    }

    const level = normalizeLevel(proficiency);
    const meta = LEVEL_META[level];
    const trimmedProficiency = String(proficiency ?? '').trim();

    return {
        language: name,
        level,
        levelLabel: level === 'Unknown' ? trimmedProficiency || '—' : level,
        percent: meta.percent,
        barClassName: meta.barClassName,
    };
}

function collectLanguageRows(cv: CvProfile): LanguageRow[] {
    const rows: LanguageRow[] = [];
    const seen = new Set<string>();

    const push = (row: LanguageRow | null) => {
        if (!row) {
            return;
        }

        const key = row.language.toLowerCase();

        if (seen.has(key)) {
            return;
        }

        seen.add(key);
        rows.push(row);
    };

    const bilingual = (cv.vitae?.bilingual ?? []) as CvBilingualEntry[];

    for (const entry of bilingual) {
        push(makeLanguageRow(String(entry.language ?? ''), entry.proficiency));
    }

    if (rows.length > 0) {
        return rows;
    }

    for (const raw of cv.languages ?? []) {
        const text = String(raw ?? '').trim();

        if (!text) {
            continue;
        }

        const parts = text.split(/\s*[—–-]\s*/);
        const language = parts[0]?.trim() ?? '';
        const proficiency = parts.slice(1).join(' — ').trim() || null;
        push(makeLanguageRow(language, proficiency));
    }

    return rows;
}

function pathFromHref(href: string): string {
    try {
        return new URL(href, 'http://localhost').pathname;
    } catch {
        return href;
    }
}

function previewModeFor(doc: DocumentItem): PreviewMode | null {
    if (doc.kind === 'link') {
        return null;
    }

    const path = pathFromHref(doc.href);

    if (doc.kind === 'image' || IMAGE_EXT_PATTERN.test(path)) {
        return 'image';
    }

    if (PDF_EXT_PATTERN.test(path) || doc.kind === 'file') {
        return 'pdf';
    }

    return null;
}

function storageUrl(path: string | null | undefined): string | null {
    if (!path) {
        return null;
    }

    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) {
        return path;
    }

    return `/storage/${path.replace(/^\/+/, '')}`;
}

function ageFromDob(dateOfBirth: string | null): string | null {
    if (!dateOfBirth) {
        return null;
    }

    const dob = new Date(`${dateOfBirth}T00:00:00`);

    if (Number.isNaN(dob.getTime())) {
        return null;
    }

    const now = new Date();
    let age = now.getFullYear() - dob.getFullYear();
    const monthDiff = now.getMonth() - dob.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < dob.getDate())) {
        age -= 1;
    }

    return age >= 0 ? `${age} years old` : null;
}

function splitList(value: string | null | undefined): string[] {
    if (!value?.trim()) {
        return [];
    }

    return value
        .split(/[|,;\n]+/)
        .map((part) => part.trim())
        .filter(Boolean);
}

function collectDocuments(cv: CvProfile): DocumentItem[] {
    const items: DocumentItem[] = [];

    if (cv.passport_document_url) {
        items.push({
            label: 'Passport / ID document',
            href: cv.passport_document_url,
            kind: 'file',
        });
    }

    if (cv.drivers_license_document_url) {
        items.push({
            label: "Driver's license",
            href: cv.drivers_license_document_url,
            kind: 'file',
        });
    }

    if (cv.passport_photo_url) {
        items.push({
            label: 'Passport photo',
            href: cv.passport_photo_url,
            kind: 'image',
        });
    }

    if (cv.full_body_photo_url) {
        items.push({
            label: 'Full-body photo',
            href: cv.full_body_photo_url,
            kind: 'image',
        });
    }

    for (const experience of cv.vitae.experiences) {
        const href = storageUrl(experience.certificate_path);

        if (href) {
            items.push({
                label: experience.organization
                    ? `Experience letter — ${experience.organization}`
                    : 'Experience letter',
                href,
                kind: 'file',
            });
        }
    }

    for (const training of cv.vitae.trainings) {
        const href = storageUrl(training.certificate_path);

        if (href) {
            items.push({
                label: training.topic
                    ? `Training — ${training.topic}`
                    : 'Training certificate',
                href,
                kind: 'file',
            });
        }
    }

    const activities = [
        ...cv.vitae.seminars,
        ...cv.vitae.presentations,
        ...cv.vitae.publications,
    ];

    for (const entry of activities) {
        const fileHref = storageUrl(entry.certificate_path);

        if (fileHref) {
            items.push({
                label: entry.topic ?? 'Activity evidence',
                href: fileHref,
                kind: 'file',
            });
        }

        if (entry.link_url) {
            items.push({
                label: entry.topic ? `Link — ${entry.topic}` : 'External link',
                href: entry.link_url,
                kind: 'link',
            });
        }
    }

    for (const cert of cv.vitae.certifications) {
        const href = storageUrl(cert.certificate_path);

        if (href) {
            items.push({
                label: cert.name ?? 'Certification',
                href,
                kind: 'file',
            });
        }
    }

    for (const license of cv.vitae.licenses) {
        const href = storageUrl(license.certificate_path);

        if (href) {
            items.push({
                label: license.title ?? 'License',
                href,
                kind: 'file',
            });
        }
    }

    for (const bilingual of cv.vitae.bilingual) {
        const href = storageUrl(bilingual.certificate_path);

        if (href) {
            items.push({
                label: bilingual.language
                    ? `Language certificate — ${bilingual.language}`
                    : 'Language certificate',
                href,
                kind: 'file',
            });
        }
    }

    return items;
}

function Fact({
    label,
    value,
}: {
    label: string;
    value: string | null | undefined;
}) {
    if (!value) {
        return null;
    }

    return (
        <div className="min-w-0">
            <p className="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p className="mt-1 truncate text-sm font-medium text-foreground">
                {value}
            </p>
        </div>
    );
}

function PillList({
    title,
    items,
}: {
    title: string;
    items: string[];
}) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div>
            <h3 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                {title}
            </h3>
            <ul className="mt-3 flex flex-wrap gap-2">
                {items.map((item) => (
                    <li
                        key={`${title}-${item}`}
                        className="rounded-full bg-brand-cool/15 px-3 py-1.5 text-sm font-medium text-brand-cool"
                    >
                        {item}
                    </li>
                ))}
            </ul>
        </div>
    );
}

type VitaeHighlightItem = {
    title: string;
    subtitle?: string | null;
    meta?: string | null;
};

function VitaeHighlightSection({
    title,
    items,
}: {
    title: string;
    items: VitaeHighlightItem[];
}) {
    return (
        <div>
            <h3 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                {title}
            </h3>
            {items.length === 0 ? (
                <p className="mt-3 text-sm text-muted-foreground">None listed.</p>
            ) : (
                <ul className="mt-3 space-y-3">
                    {items.map((item, index) => (
                        <li
                            key={`${title}-${item.title}-${index}`}
                            className="rounded-2xl border border-border/60 bg-muted/25 px-3.5 py-3"
                        >
                            <p className="text-sm font-semibold text-foreground">
                                {item.title}
                            </p>
                            {item.subtitle && (
                                <p className="mt-0.5 text-sm text-foreground/80">
                                    {item.subtitle}
                                </p>
                            )}
                            {item.meta && (
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {item.meta}
                                </p>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function collectEducation(cv: CvProfile): VitaeHighlightItem[] {
    return (cv.vitae?.qualifications ?? [])
        .map((item) => {
            const title = String(item.title ?? '').trim();

            if (!title) {
                return null;
            }

            const institution = [item.college, item.university]
                .map((part) => String(part ?? '').trim())
                .filter(Boolean)
                .join(' · ');
            const period = [item.start_date, item.end_date]
                .map((part) => String(part ?? '').trim())
                .filter(Boolean)
                .join(' – ');
            const location = [item.city, item.state, item.country]
                .map((part) => String(part ?? '').trim())
                .filter(Boolean)
                .join(', ');

            return {
                title,
                subtitle: institution || null,
                meta: [period, location].filter(Boolean).join(' · ') || null,
            };
        })
        .filter((item): item is VitaeHighlightItem => item !== null);
}

function collectWorkExperience(cv: CvProfile): VitaeHighlightItem[] {
    if (cv.vitae?.is_newbie) {
        return [];
    }

    return (cv.vitae?.experiences ?? [])
        .map((job) => {
            const title =
                String(job.designation ?? '').trim() ||
                String(job.organization ?? '').trim();

            if (!title) {
                return null;
            }

            const organization = String(job.organization ?? '').trim();
            const subtitle =
                organization && organization !== title ? organization : null;
            const period =
                String(job.duration ?? '').trim() ||
                [job.start_date, job.end_date]
                    .map((part) => String(part ?? '').trim())
                    .filter(Boolean)
                    .join(' – ');
            const location = [job.city, job.state]
                .map((part) => String(part ?? '').trim())
                .filter(Boolean)
                .join(', ');

            return {
                title,
                subtitle,
                meta: [period, location].filter(Boolean).join(' · ') || null,
            };
        })
        .filter((item): item is VitaeHighlightItem => item !== null);
}

function collectCertifications(cv: CvProfile): VitaeHighlightItem[] {
    return (cv.vitae?.certifications ?? [])
        .map((cert) => {
            const title = String(cert.name ?? '').trim();

            if (!title) {
                return null;
            }

            return {
                title,
                subtitle: String(cert.description ?? '').trim() || null,
            };
        })
        .filter((item): item is VitaeHighlightItem => item !== null);
}

function collectLicenses(cv: CvProfile): VitaeHighlightItem[] {
    return (cv.vitae?.licenses ?? [])
        .map((license) => {
            const title = String(license.title ?? '').trim();

            if (!title) {
                return null;
            }

            const registration = String(
                license.registration_number ?? '',
            ).trim();
            const period = [license.issued_date, license.valid_till]
                .map((part) => String(part ?? '').trim())
                .filter(Boolean)
                .join(' – ');

            return {
                title,
                subtitle: registration || null,
                meta: period || null,
            };
        })
        .filter((item): item is VitaeHighlightItem => item !== null);
}

function collectAchievements(cv: CvProfile): VitaeHighlightItem[] {
    const entries = [
        ...(cv.vitae?.seminars ?? []),
        ...(cv.vitae?.presentations ?? []),
        ...(cv.vitae?.publications ?? []),
        ...(cv.vitae?.trainings ?? []),
    ];

    const items: VitaeHighlightItem[] = [];
    const seen = new Set<string>();

    for (const entry of entries) {
        const title = String(entry.topic ?? '').trim();

        if (!title || seen.has(title.toLowerCase())) {
            continue;
        }

        seen.add(title.toLowerCase());

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

        items.push({
            title,
            meta: [date, location].filter(Boolean).join(' · ') || null,
        });
    }

    return items;
}

const LANGUAGE_SCALE = [
    'Beginner',
    'Intermediate',
    'Professional',
    'Native',
] as const;

function LanguagesKnownBars({ rows }: { rows: LanguageRow[] }) {
    if (rows.length === 0) {
        return null;
    }

    return (
        <div>
            <h3 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                {BRAND_NAME_TM} — Languages Known
            </h3>
            <ul className="mt-4 space-y-5">
                {rows.map((row) => (
                    <li key={row.language} className="space-y-2">
                        <span className="text-sm font-semibold text-foreground">
                            {row.language}
                        </span>
                        <div className="h-2.5 overflow-hidden rounded-full bg-muted">
                            <div
                                className={cn(
                                    'h-full rounded-full transition-[width] duration-500 ease-out',
                                    row.barClassName,
                                )}
                                style={{ width: `${row.percent}%` }}
                            />
                        </div>
                        <div className="grid grid-cols-4 gap-1">
                            {LANGUAGE_SCALE.map((level) => {
                                const active = row.level === level;

                                return (
                                    <span
                                        key={level}
                                        className={cn(
                                            'text-[10px] font-medium tracking-wide uppercase sm:text-[11px]',
                                            active
                                                ? 'font-semibold text-foreground'
                                                : 'text-muted-foreground/60',
                                        )}
                                    >
                                        {level}
                                    </span>
                                );
                            })}
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    );
}

const documentCardClassName = cn(
    'flex w-full items-center gap-3 rounded-2xl border border-brand-cool/20 bg-brand-cool/5 px-3.5 py-3 text-left text-sm transition-colors',
    'hover:border-brand-cool/40 hover:bg-brand-cool/10',
);

export default function FacultyStudentProfilePanel({ cv }: Props) {
    const [preview, setPreview] = useState<MediaPreview | null>(null);

    const title =
        [cv.first_name, cv.last_name].filter(Boolean).join(' ') || cv.name;
    const photoUrl = cv.full_body_photo_url || cv.passport_photo_url;
    const age = ageFromDob(cv.date_of_birth);
    const tag = cv.current_status || null;
    const hobbies = splitList(cv.hobbies);
    const languageRows = collectLanguageRows(cv);
    const education = collectEducation(cv);
    const workExperience = collectWorkExperience(cv);
    const certifications = collectCertifications(cv);
    const licenses = collectLicenses(cv);
    const achievements = collectAchievements(cv);
    const documents = collectDocuments(cv);
    // proficiency_summary is the flat "Language — Level · …" string; languages
    // are shown via progress bars instead.
    const backgroundParts = [
        cv.introduction,
        cv.gap_reasons,
        cv.pursuing_details,
    ].filter(Boolean) as string[];
    const contactItems = (
        [
            {
                label: 'Email',
                value: cv.email,
                href: cv.email ? `mailto:${cv.email}` : null,
                Icon: Mail,
            },
            {
                label: 'Phone',
                value: cv.mobile_whatsapp,
                href: cv.mobile_whatsapp
                    ? `tel:${cv.mobile_whatsapp.replace(/\s+/g, '')}`
                    : null,
                Icon: Phone,
            },
            {
                label: 'Address',
                value: cv.current_address,
                href: null,
                Icon: MapPin,
            },
        ] as const
    ).filter((item) => Boolean(item.value));

    return (
        <>
            <div className="overflow-hidden rounded-3xl border border-border/70 bg-card p-2 shadow-lg shadow-black/5">
                <div className="grid overflow-hidden rounded-[1.25rem] lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)]">
                    <div className="flex flex-col border-b border-border/60 bg-muted/30 lg:border-r lg:border-b-0">
                        <div className="relative aspect-[3/4] w-full overflow-hidden bg-brand-cool/10">
                            {photoUrl ? (
                                <img
                                    src={photoUrl}
                                    alt={`${title} full photo`}
                                    className="size-full object-cover object-top"
                                />
                            ) : (
                                <div className="flex size-full items-center justify-center text-sm text-muted-foreground">
                                    No full-body photo uploaded
                                </div>
                            )}

                            {contactItems.length > 0 && (
                                <div className="absolute inset-x-3 bottom-3 rounded-2xl border border-brand-cool/40 bg-[#E8F3FB]/95 p-3.5 shadow-sm backdrop-blur-md">
                                    <ul className="space-y-2.5">
                                        {contactItems.map(
                                            ({ label, value, href, Icon }) => {
                                                const body = (
                                                    <>
                                                        <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-cool/20 text-brand-cool">
                                                            <Icon className="size-3.5" />
                                                        </span>
                                                        <span className="min-w-0">
                                                            <span className="block text-[10px] font-semibold tracking-wide text-brand-cool-dark/70 uppercase">
                                                                {label}
                                                            </span>
                                                            <span className="block truncate text-sm font-semibold text-[#031835]">
                                                                {value}
                                                            </span>
                                                        </span>
                                                    </>
                                                );

                                                return (
                                                    <li key={label}>
                                                        {href ? (
                                                            <a
                                                                href={href}
                                                                className="flex items-center gap-2.5 rounded-xl transition-colors hover:bg-brand-cool/10"
                                                            >
                                                                {body}
                                                            </a>
                                                        ) : (
                                                            <div className="flex items-center gap-2.5">
                                                                {body}
                                                            </div>
                                                        )}
                                                    </li>
                                                );
                                            },
                                        )}
                                    </ul>
                                </div>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3 border-t border-border/60 bg-card px-4 py-4">
                            <Fact label="Age" value={age} />
                            <Fact
                                label="Status"
                                value={cv.current_status || cv.headline}
                            />
                        </div>

                        <div className="border-t border-border/60 bg-card px-4 py-4">
                            <h3 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                Submitted files & links
                            </h3>
                            {documents.length > 0 ? (
                                <ul className="mt-3 grid gap-2">
                                    {documents.map((doc) => {
                                        const previewMode = previewModeFor(doc);
                                        const Icon =
                                            previewMode === 'image' ||
                                            doc.kind === 'image'
                                                ? ImageIcon
                                                : doc.kind === 'link'
                                                  ? ExternalLink
                                                  : FileText;

                                        const content = (
                                            <>
                                                <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-cool/15 text-brand-cool shadow-sm">
                                                    <Icon className="size-4" />
                                                </span>
                                                <span className="min-w-0 truncate font-medium">
                                                    {doc.label}
                                                </span>
                                            </>
                                        );

                                        return (
                                            <li
                                                key={`${doc.label}-${doc.href}`}
                                            >
                                                {previewMode ? (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setPreview({
                                                                src: doc.href,
                                                                label: doc.label,
                                                                mode: previewMode,
                                                            })
                                                        }
                                                        className={documentCardClassName}
                                                    >
                                                        {content}
                                                    </button>
                                                ) : (
                                                    <a
                                                        href={doc.href}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className={documentCardClassName}
                                                    >
                                                        {content}
                                                    </a>
                                                )}
                                            </li>
                                        );
                                    })}
                                </ul>
                            ) : (
                                <p className="mt-3 text-sm text-muted-foreground">
                                    No documents or links uploaded yet.
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Entire right section: 8px padding from the photo column */}
                    <div className="flex flex-col gap-8 bg-card p-2 sm:p-6 lg:p-8">
                        <div className="flex flex-wrap items-center gap-3">
                            <h2 className="text-3xl font-semibold tracking-tight text-foreground">
                                {title}
                            </h2>
                            {tag && (
                                <span className="rounded-full bg-brand-cool/15 px-3 py-1 text-xs font-semibold text-brand-cool">
                                    {tag}
                                </span>
                            )}
                        </div>

                        <div>
                            <h3 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                Background
                            </h3>
                            {backgroundParts.length > 0 ? (
                                <div className="mt-3 rounded-2xl border border-border/60 bg-muted/35 p-4 shadow-sm sm:p-5">
                                    <div className="space-y-3 text-sm leading-relaxed text-foreground/90">
                                        {backgroundParts.map((part) => (
                                            <p key={part.slice(0, 48)}>{part}</p>
                                        ))}
                                    </div>
                                </div>
                            ) : (
                                <p className="mt-3 text-sm text-muted-foreground">
                                    No background details submitted yet.
                                </p>
                            )}
                        </div>

                        <LanguagesKnownBars rows={languageRows} />

                        <div className="grid gap-8 sm:grid-cols-2">
                            <VitaeHighlightSection
                                title="Education"
                                items={education}
                            />
                            <VitaeHighlightSection
                                title="Work experience"
                                items={workExperience}
                            />
                            <VitaeHighlightSection
                                title="Certification"
                                items={certifications}
                            />
                            <VitaeHighlightSection
                                title="License"
                                items={licenses}
                            />
                            <VitaeHighlightSection
                                title="Achievements"
                                items={achievements}
                            />
                        </div>

                        <div className="flex flex-col gap-8">
                            <PillList title="Skills" items={cv.skills} />
                            <PillList
                                title="Hobbies & interests"
                                items={hobbies}
                            />
                        </div>
                    </div>
                </div>
            </div>

            <Dialog
                open={preview !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPreview(null);
                    }
                }}
            >
                <DialogContent
                    className="gap-3 overflow-hidden border-border/60 bg-background/95 p-3 sm:max-w-4xl sm:p-4"
                    overlayClassName="bg-black/85 backdrop-blur-[2px]"
                >
                    <DialogHeader className="pr-8">
                        <DialogTitle>
                            {preview?.label ?? 'Document'}
                        </DialogTitle>
                        <DialogDescription className="sr-only">
                            On-page preview of{' '}
                            {preview?.label ?? 'the submitted file'}
                        </DialogDescription>
                    </DialogHeader>
                    {preview?.mode === 'image' && (
                        <div className="flex max-h-[85vh] items-center justify-center overflow-hidden rounded-xl bg-muted/30">
                            <img
                                src={preview.src}
                                alt={preview.label}
                                className="max-h-[85vh] w-full object-contain"
                            />
                        </div>
                    )}
                    {preview?.mode === 'pdf' && (
                        <iframe
                            src={preview.src}
                            title={preview.label}
                            className="h-[85vh] w-full rounded-xl border border-border/60 bg-white"
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
