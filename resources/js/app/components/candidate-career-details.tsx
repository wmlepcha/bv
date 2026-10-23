import {
    Award,
    ChevronDown,
    BadgeCheck,
    BookOpen,
    Briefcase,
    GraduationCap,
    Languages,
    Mic,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { useMediaQuery } from '@/hooks/use-media-query';
import { cn } from '@/lib/utils';
import type { CvProfile } from '@app/types/cv';

type Row = [label: string, value: string | null | undefined];

function range(start: string | null, end: string | null): string | null {
    return [start, end].filter(Boolean).join(' – ') || null;
}

function Card({
    title,
    subtitle,
    meta,
    rows = [],
}: {
    title: string;
    subtitle?: string | null;
    meta?: string | null;
    rows?: Row[];
}) {
    const isDesktop = useMediaQuery('(min-width: 1024px)');
    const [expanded, setExpanded] = useState<boolean | null>(null);
    const filled = rows.filter(([, value]) => value);
    const expandable = filled.length > 0;
    const open = expandable && (expanded ?? isDesktop);

    const header = (
        <>
            <div className="min-w-0 flex-1 text-left">
                <p className="font-semibold">{title}</p>
                {subtitle && (
                    <p className="text-sm text-muted-foreground">{subtitle}</p>
                )}
                {meta && (
                    <p className="mt-1.5 w-fit rounded-full bg-muted px-2.5 py-0.5 text-xs text-muted-foreground">
                        {meta}
                    </p>
                )}
            </div>
            {expandable && (
                <ChevronDown
                    className={cn(
                        'mt-1 size-5 shrink-0 text-muted-foreground transition-transform',
                        open && 'rotate-180',
                    )}
                />
            )}
        </>
    );

    return (
        <li className="overflow-hidden rounded-3xl border border-border bg-card/60 shadow-sm">
            {expandable ? (
                <button
                    type="button"
                    aria-expanded={open}
                    onClick={() => setExpanded(!open)}
                    className="flex w-full items-start gap-3 p-4 transition-colors active:bg-muted/60"
                >
                    {header}
                </button>
            ) : (
                <div className="flex items-start gap-3 p-4">{header}</div>
            )}
            {open && (
                <dl className="grid gap-x-6 gap-y-3 border-t border-border/60 p-4 text-sm sm:grid-cols-2">
                    {filled.map(([label, value]) => (
                        <div key={label}>
                            <dt className="text-xs text-muted-foreground">
                                {label}
                            </dt>
                            <dd>{value}</dd>
                        </div>
                    ))}
                </dl>
            )}
        </li>
    );
}

function Section({
    id,
    title,
    count,
    icon: Icon,
    children,
}: {
    id: string;
    title: string;
    count: number;
    icon: LucideIcon;
    children: ReactNode;
}) {
    if (count === 0) {
        return null;
    }

    return (
        <section
            id={id}
            className="flex scroll-mt-[calc(var(--app-top-h,0px)+var(--student-header-h,0px)+4.5rem)] flex-col gap-3"
        >
            <h2 className="flex items-center gap-2.5 text-sm font-semibold tracking-wide uppercase">
                <span className="flex size-8 items-center justify-center rounded-xl bg-brand-cool/15 text-brand-cool">
                    <Icon className="size-4" />
                </span>
                {title}
                <span className="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                    {count}
                </span>
            </h2>
            <ul className="grid gap-3">{children}</ul>
        </section>
    );
}

export default function CandidateCareerDetails({ cv }: { cv: CvProfile }) {
    const { vitae } = cv;
    const activities = [
        ...vitae.seminars.map((entry) => ({ ...entry, kind: 'Seminar' })),
        ...vitae.presentations.map((entry) => ({
            ...entry,
            kind: 'Presentation',
        })),
        ...vitae.publications.map((entry) => ({
            ...entry,
            kind: 'Publication',
        })),
    ];

    const jumps = [
        {
            id: 'work',
            label: 'Work',
            icon: Briefcase,
            count: vitae.experiences.length,
        },
        {
            id: 'education',
            label: 'Education',
            icon: GraduationCap,
            count: vitae.qualifications.length,
        },
        {
            id: 'licenses',
            label: 'Licenses',
            icon: BadgeCheck,
            count: vitae.licenses.length,
        },
        {
            id: 'certifications',
            label: 'Certs',
            icon: Award,
            count: vitae.certifications.length,
        },
        {
            id: 'trainings',
            label: 'Training',
            icon: BookOpen,
            count: vitae.trainings.length,
        },
        {
            id: 'activities',
            label: 'Seminars',
            icon: Mic,
            count: activities.length,
        },
        {
            id: 'languages',
            label: 'Languages',
            icon: Languages,
            count: vitae.bilingual.length,
        },
    ].filter((jump) => jump.count > 0);
    const isEmpty =
        vitae.qualifications.length +
            vitae.experiences.length +
            vitae.trainings.length +
            activities.length +
            vitae.certifications.length +
            vitae.licenses.length +
            vitae.bilingual.length ===
        0;

    if (isEmpty) {
        return (
            <div className="rounded-3xl border border-dashed border-border px-6 py-10 text-center text-sm text-muted-foreground">
                No education or work history submitted yet.
            </div>
        );
    }

    return (
        <div className="flex flex-col gap-8">
            <nav
                aria-label="Career sections"
                className="sticky top-[calc(var(--app-top-h,0px)+var(--student-header-h,0px))] z-10 -mx-2 -mt-3 flex scrollbar-none gap-2 overflow-x-auto bg-background/90 px-2 py-2.5 backdrop-blur-md lg:hidden"
            >
                {jumps.map(({ id, label, icon: Icon, count }) => (
                    <button
                        key={id}
                        type="button"
                        onClick={() =>
                            document.getElementById(id)?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start',
                            })
                        }
                        className="flex shrink-0 items-center gap-2 rounded-full border border-border bg-card px-4 py-2 text-sm font-medium shadow-sm transition-transform active:scale-95"
                    >
                        <Icon className="size-4 text-brand-cool" />
                        {label}
                        <span className="rounded-full bg-primary/10 px-1.5 text-xs font-bold text-primary">
                            {count}
                        </span>
                    </button>
                ))}
            </nav>

            <Section
                id="work"
                title="Work experience"
                icon={Briefcase}
                count={vitae.experiences.length}
            >
                {vitae.experiences.map((item, index) => (
                    <Card
                        key={index}
                        title={item.designation ?? 'Position'}
                        subtitle={[item.organization, item.city, item.state]
                            .filter(Boolean)
                            .join(', ')}
                        meta={
                            range(item.start_date, item.end_date) ??
                            item.duration
                        }
                        rows={[
                            ['Department', item.department],
                            ['Cases handled', item.cases_handled],
                            ['Critical cases', item.critical_cases],
                            ['Bed capacity', item.bed_capacity],
                            ['Medical equipment', item.medical_equipment],
                            ['Departments offered', item.departments_offered],
                            ['Accreditations', item.accreditations],
                            [
                                'Experience letter',
                                item.has_experience_letter ? 'Available' : null,
                            ],
                        ]}
                    />
                ))}
            </Section>

            <Section
                id="education"
                title="Education"
                icon={GraduationCap}
                count={vitae.qualifications.length}
            >
                {vitae.qualifications.map((item, index) => (
                    <Card
                        key={index}
                        title={item.title ?? 'Qualification'}
                        subtitle={[item.college, item.university]
                            .filter(Boolean)
                            .join(', ')}
                        meta={range(item.start_date, item.end_date)}
                        rows={[
                            [
                                'Location',
                                [item.city, item.state, item.country]
                                    .filter(Boolean)
                                    .join(', '),
                            ],
                            ['Board', item.board],
                            [
                                'Language of instruction',
                                item.language_of_instruction,
                            ],
                        ]}
                    />
                ))}
            </Section>

            <Section
                id="licenses"
                title="Licenses"
                icon={BadgeCheck}
                count={vitae.licenses.length}
            >
                {vitae.licenses.map((item, index) => (
                    <Card
                        key={index}
                        title={item.title ?? 'License'}
                        rows={[
                            ['Registration number', item.registration_number],
                            ['Issued', item.issued_date],
                            ['Valid till', item.valid_till],
                        ]}
                    />
                ))}
            </Section>

            <Section
                id="certifications"
                title="Certifications"
                icon={Award}
                count={vitae.certifications.length}
            >
                {vitae.certifications.map((item, index) => (
                    <Card
                        key={index}
                        title={item.name ?? 'Certification'}
                        subtitle={item.description}
                    />
                ))}
            </Section>

            <Section
                id="trainings"
                title="Trainings"
                icon={BookOpen}
                count={vitae.trainings.length}
            >
                {vitae.trainings.map((item, index) => (
                    <Card
                        key={index}
                        title={item.topic ?? 'Training'}
                        subtitle={item.organization_location}
                        meta={item.date}
                    />
                ))}
            </Section>

            <Section
                id="activities"
                title="Seminars, talks & publications"
                icon={Mic}
                count={activities.length}
            >
                {activities.map((item, index) => (
                    <Card
                        key={index}
                        title={item.topic ?? item.kind}
                        subtitle={[item.organization, item.location]
                            .filter(Boolean)
                            .join(', ')}
                        meta={[item.kind, item.date]
                            .filter(Boolean)
                            .join(' · ')}
                    />
                ))}
            </Section>

            <Section
                id="languages"
                title="Languages"
                icon={Languages}
                count={vitae.bilingual.length}
            >
                {vitae.bilingual.map((item, index) => (
                    <Card
                        key={index}
                        title={item.language ?? 'Language'}
                        rows={[
                            ['Type', item.language_type],
                            ['Proficiency', item.proficiency],
                            ['Certification level', item.certification_level],
                            ['Certificates', item.certificates],
                        ]}
                    />
                ))}
            </Section>
        </div>
    );
}
