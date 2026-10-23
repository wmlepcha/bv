import { User } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { CvProfile } from '@app/types/cv';
import type { LanguageProgress } from '@app/types/recruiter';

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(`${value}T00:00:00`);

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleDateString('en-GB', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          });
}

function courseStatus(
    course: NonNullable<LanguageProgress['course']>,
): { label: string; className: string } | null {
    if (course.elapsed_percent === null || !course.starts_on) {
        return null;
    }

    if (new Date(`${course.starts_on}T00:00:00`) > new Date()) {
        return { label: 'Upcoming', className: 'bg-sky-600/15 text-sky-600' };
    }

    return course.elapsed_percent >= 100
        ? { label: 'Completed', className: 'bg-muted text-muted-foreground' }
        : {
              label: 'In progress',
              className: 'bg-emerald-600/15 text-emerald-600',
          };
}

const LEVEL_COLORS: Record<string, string> = {
    A1: 'bg-sky-600',
    A2: 'bg-emerald-600',
    B1: 'bg-amber-600',
    B2: 'bg-violet-600',
};

const STATUS_STYLES: Record<string, string> = {
    graded: 'bg-emerald-600/15 text-emerald-600',
    submitted: 'bg-sky-600/15 text-sky-600',
    pending: 'bg-muted text-muted-foreground',
    overdue: 'bg-destructive/15 text-destructive',
};

function Card({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="rounded-3xl border border-border bg-card/40 p-4 sm:p-6">
            <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                {title}
            </h2>
            <div className="mt-4">{children}</div>
        </section>
    );
}

function Bar({ percent }: { percent: number }) {
    return (
        <div className="h-2 overflow-hidden rounded-full bg-muted">
            <div
                className="h-full rounded-full bg-brand-cool"
                style={{ width: `${percent}%` }}
            />
        </div>
    );
}

function Stat({ label, value }: { label: string; value: string | number }) {
    return (
        <div className="rounded-2xl border border-border bg-card/60 p-4">
            <p className="text-2xl font-semibold tabular-nums">{value}</p>
            <p className="text-xs text-muted-foreground">{label}</p>
        </div>
    );
}

export default function CandidateLanguageProgress({
    language,
    cv,
}: {
    language: LanguageProgress;
    cv: CvProfile;
}) {
    const { course, assignments, self_assessment: skills } = language;
    const status = course ? courseStatus(course) : null;

    return (
        <div className="flex flex-col gap-4">
            <Card title="Current course">
                {course ? (
                    <div className="flex flex-col gap-5">
                        <div className="flex items-center gap-4">
                            <div
                                className={cn(
                                    'flex size-16 shrink-0 flex-col items-center justify-center rounded-2xl text-white shadow-sm',
                                    LEVEL_COLORS[course.level] ??
                                        'bg-slate-600',
                                )}
                            >
                                <span className="text-2xl leading-none font-bold">
                                    {course.level}
                                </span>
                                <span className="mt-1 text-[10px] font-medium tracking-wide uppercase opacity-80">
                                    Level
                                </span>
                            </div>
                            <div className="min-w-0">
                                <p className="text-xl font-semibold">
                                    {course.language_label}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {course.batch_name}
                                    {course.center_name &&
                                        ` · ${course.center_name}`}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 rounded-2xl bg-muted/50 px-3 py-2.5">
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-cool/15 text-brand-cool">
                                <User className="size-4" />
                            </span>
                            <div className="min-w-0">
                                <p className="text-xs text-muted-foreground">
                                    Trainer
                                </p>
                                <p className="truncate text-sm font-medium">
                                    {course.trainer_name ?? '—'}
                                </p>
                            </div>
                            {status && (
                                <span
                                    className={cn(
                                        'ml-auto shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold',
                                        status.className,
                                    )}
                                >
                                    {status.label}
                                </span>
                            )}
                        </div>

                        <dl className="grid grid-cols-3 gap-2">
                            {(
                                [
                                    ['Enrolled', course.enrolled_on],
                                    ['Starts', course.starts_on],
                                    ['Ends', course.ends_on],
                                ] as const
                            ).map(([label, value]) => (
                                <div
                                    key={label}
                                    className="rounded-2xl border border-border bg-card/60 px-3 py-2.5"
                                >
                                    <dt className="text-[11px] text-muted-foreground">
                                        {label}
                                    </dt>
                                    <dd className="mt-0.5 text-[13px] leading-tight font-semibold">
                                        {formatDate(value)}
                                    </dd>
                                </div>
                            ))}
                        </dl>

                        {course.elapsed_percent !== null && (
                            <div>
                                <div className="mb-1.5 flex justify-between text-xs text-muted-foreground">
                                    <span>Course progress</span>
                                    <span className="font-semibold text-foreground">
                                        {course.elapsed_percent}%
                                    </span>
                                </div>
                                <Bar percent={course.elapsed_percent} />
                            </div>
                        )}
                    </div>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        Not enrolled in a batch yet.
                    </p>
                )}
            </Card>

            <div className="grid grid-cols-2 gap-3 lg:grid-cols-5 [&>*:last-child]:col-span-2 lg:[&>*:last-child]:col-span-1">
                <Stat label="Assignments" value={assignments.total} />
                <Stat label="Graded" value={assignments.graded} />
                <Stat label="Awaiting grading" value={assignments.submitted} />
                <Stat label="Not submitted" value={assignments.pending} />
                <Stat
                    label="Average score"
                    value={
                        assignments.average_percent === null
                            ? '—'
                            : `${assignments.average_percent}%`
                    }
                />
            </div>

            <Card title="Assignments">
                {assignments.items.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No assignments yet.
                    </p>
                ) : (
                    <ul className="divide-y divide-border">
                        {assignments.items.map((item) => (
                            <li
                                key={item.id}
                                className="grid grid-cols-[1fr_auto] items-center gap-x-3 gap-y-2 py-3 sm:flex sm:gap-4"
                            >
                                <div className="col-start-1 row-start-1 min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">
                                        {item.title}
                                    </p>
                                    {item.due_at && (
                                        <p className="text-xs text-muted-foreground">
                                            Due {item.due_at}
                                        </p>
                                    )}
                                </div>
                                {item.percent !== null && (
                                    <div className="col-start-1 row-start-2 w-full sm:w-40">
                                        <Bar percent={item.percent} />
                                    </div>
                                )}
                                <span className="col-start-2 row-start-2 text-sm font-semibold tabular-nums sm:w-16 sm:text-right">
                                    {item.score !== null
                                        ? `${item.score} / ${item.max_score}`
                                        : ''}
                                </span>
                                <span
                                    className={cn(
                                        'col-start-2 row-start-1 w-fit justify-self-end rounded-full px-2.5 py-1 text-xs font-medium capitalize',
                                        STATUS_STYLES[item.status],
                                    )}
                                >
                                    {item.status}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </Card>

            <div className="grid gap-4 md:grid-cols-2">
                <Card title="Self-assessment">
                    {skills ? (
                        <dl className="grid gap-3 text-sm">
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Confidence at {skills.level}
                                </dt>
                                <dd>{skills.confidence} / 5</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Most improved skill
                                </dt>
                                <dd>{skills.most_improved}</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground">
                                    Wants to improve
                                </dt>
                                <dd>{skills.needs_improvement}</dd>
                            </div>
                        </dl>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No course feedback submitted yet.
                        </p>
                    )}
                </Card>

                <Card title="Languages (from CV)">
                    {cv.vitae.bilingual.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No languages listed.
                        </p>
                    ) : (
                        <ul className="grid gap-2 text-sm">
                            {cv.vitae.bilingual.map((entry, index) => (
                                <li
                                    key={index}
                                    className="flex justify-between gap-4"
                                >
                                    <span className="font-medium">
                                        {entry.language}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {[
                                            entry.proficiency,
                                            entry.certification_level,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ') || '—'}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                    <p className="mt-4 text-xs text-muted-foreground">
                        Study materials saved:{' '}
                        <span className="font-medium text-foreground">
                            {language.study_bank_saved}
                        </span>
                    </p>
                </Card>
            </div>

            <Card title="Attendance">
                {language.attendance.total_sessions === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No classes have been recorded yet.
                    </p>
                ) : (
                    <div className="flex flex-col gap-4">
                        <div className="flex items-end justify-between gap-4">
                            <p className="text-4xl font-semibold tabular-nums">
                                {language.attendance.percentage === null
                                    ? '—'
                                    : `${language.attendance.percentage}%`}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {language.attendance.attended} of{' '}
                                {language.attendance.total_sessions -
                                    language.attendance.excused}{' '}
                                classes attended
                                {language.attendance.excused > 0 &&
                                    ` · ${language.attendance.excused} on leave`}
                            </p>
                        </div>
                        {language.attendance.percentage !== null && (
                            <Bar percent={language.attendance.percentage} />
                        )}
                        <ul
                            className="flex flex-wrap gap-1.5"
                            aria-label="Recent classes"
                        >
                            {language.attendance.recent.map((item, index) => (
                                <li
                                    key={`${item.date}-${index}`}
                                    title={`${item.date}: ${item.status}`}
                                    className={cn(
                                        'size-3.5 rounded-full',
                                        item.status === 'present' &&
                                            'bg-emerald-500',
                                        item.status === 'excused' &&
                                            'bg-sky-500',
                                        item.status === 'absent' &&
                                            'bg-destructive',
                                    )}
                                />
                            ))}
                        </ul>
                    </div>
                )}
            </Card>
        </div>
    );
}
