import { Mail, MapPin, MessageCircle, Phone } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { CvBilingualEntry, CvProfile } from '@app/types/cv';
import { BRAND_NAME_TM } from '@shared/lib/brand';

type Props = {
    cv: CvProfile;
};

type LanguageLevel =
    'Beginner' | 'Intermediate' | 'Professional' | 'Native' | 'Unknown';

type LanguageRow = {
    language: string;
    level: LanguageLevel;
    levelLabel: string;
    percent: number;
    barClassName: string;
};

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

    if (
        value.includes('professional') ||
        value.includes('fluent') ||
        value.includes('advanced')
    ) {
        return 'Professional';
    }

    if (value.includes('intermediate')) {
        return 'Intermediate';
    }

    if (
        value.includes('beginner') ||
        value.includes('basic') ||
        value.includes('elementary')
    ) {
        return 'Beginner';
    }

    return 'Unknown';
}

function makeLanguageRow(
    language: string,
    proficiency: string | null | undefined,
): LanguageRow | null {
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
                <p className="mt-3 text-sm text-muted-foreground">
                    None listed.
                </p>
            ) : (
                <ul className="mt-3 grid gap-3 sm:grid-cols-2">
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
        .map((item): VitaeHighlightItem | null => {
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

export default function FacultyStudentProfilePanel({ cv }: Props) {
    const title =
        [cv.first_name, cv.last_name].filter(Boolean).join(' ') || cv.name;
    const photoUrl = cv.full_body_photo_url || cv.passport_photo_url;
    const age = ageFromDob(cv.date_of_birth);
    const tag = cv.current_status || null;
    const languageRows = collectLanguageRows(cv);
    const education = collectEducation(cv);
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

    const whatsappDigits = cv.mobile_whatsapp?.replace(/\D/g, '') ?? '';
    const actions = [
        {
            label: 'Call',
            Icon: Phone,
            href: cv.mobile_whatsapp
                ? `tel:${cv.mobile_whatsapp.replace(/\s+/g, '')}`
                : null,
        },
        {
            label: 'WhatsApp',
            Icon: MessageCircle,
            href: whatsappDigits ? `https://wa.me/${whatsappDigits}` : null,
        },
        {
            label: 'Email',
            Icon: Mail,
            href: cv.email ? `mailto:${cv.email}` : null,
        },
    ];

    return (
        <>
            <div className="flex flex-col gap-4 lg:hidden">
                <div className="relative aspect-[4/5] overflow-hidden rounded-3xl bg-brand-cool/10 shadow-lg shadow-black/10">
                    {photoUrl ? (
                        <img
                            src={photoUrl}
                            alt={`${title} photo`}
                            className="size-full object-cover object-top"
                        />
                    ) : (
                        <div className="flex size-full items-center justify-center text-sm text-muted-foreground">
                            No photo uploaded
                        </div>
                    )}
                    <div className="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-black/85 via-black/40 to-transparent" />
                    <div className="absolute inset-x-0 bottom-0 flex flex-col gap-1.5 p-5 text-white">
                        {tag && (
                            <span className="w-fit rounded-full bg-white/20 px-3 py-1 text-xs font-semibold backdrop-blur-md">
                                {tag}
                            </span>
                        )}
                        <h2 className="text-2xl leading-tight font-bold">
                            {title}
                        </h2>
                        {cv.headline && (
                            <p className="line-clamp-2 text-sm text-white/80">
                                {cv.headline}
                            </p>
                        )}
                        {(age || cv.current_address) && (
                            <p className="flex items-center gap-1.5 text-xs text-white/70">
                                {age}
                                {age && cv.current_address && ' · '}
                                {cv.current_address && (
                                    <>
                                        <MapPin className="size-3 shrink-0" />
                                        <span className="truncate">
                                            {cv.current_address}
                                        </span>
                                    </>
                                )}
                            </p>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-3 gap-3">
                    {actions.map(({ label, Icon, href }) => {
                        const className =
                            'flex flex-col items-center gap-1.5 rounded-2xl border border-border bg-card py-3 text-xs font-medium transition-transform active:scale-95';

                        return href ? (
                            <a
                                key={label}
                                href={href}
                                target={
                                    label === 'WhatsApp' ? '_blank' : undefined
                                }
                                rel="noreferrer"
                                className={className}
                            >
                                <span className="flex size-10 items-center justify-center rounded-full bg-brand-cool/15 text-brand-cool">
                                    <Icon className="size-5" />
                                </span>
                                {label}
                            </a>
                        ) : (
                            <span
                                key={label}
                                aria-disabled
                                className={`${className} opacity-40`}
                            >
                                <span className="flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                    <Icon className="size-5" />
                                </span>
                                {label}
                            </span>
                        );
                    })}
                </div>

                <section className="rounded-3xl border border-border bg-card p-4">
                    <h3 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Background
                    </h3>
                    {backgroundParts.length > 0 ? (
                        <div className="mt-3 space-y-3 text-sm leading-relaxed text-foreground/90">
                            {backgroundParts.map((part) => (
                                <p key={part.slice(0, 48)}>{part}</p>
                            ))}
                        </div>
                    ) : (
                        <p className="mt-3 text-sm text-muted-foreground">
                            No background details submitted yet.
                        </p>
                    )}
                </section>

                <section className="rounded-3xl border border-border bg-card p-4">
                    <LanguagesKnownBars rows={languageRows} />
                </section>

                <section className="rounded-3xl border border-border bg-card p-4">
                    <VitaeHighlightSection
                        title="Education"
                        items={education}
                    />
                </section>
            </div>

            <div className="hidden overflow-hidden rounded-3xl border border-border/70 bg-card p-2 shadow-lg shadow-black/5 lg:block">
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
                                            <p key={part.slice(0, 48)}>
                                                {part}
                                            </p>
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

                        <VitaeHighlightSection
                            title="Education"
                            items={education}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
