import { Link } from '@inertiajs/react';
import { AlertCircle, BriefcaseBusiness, ChevronRight } from 'lucide-react';
import { show } from '@/routes/recruitment-manager/projects';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Project = {
    id: number;
    name: string;
    recruiter: string;
    country: string;
    sector: string;
    timeline: string;
    students_count: number;
    sent_count: number;
    approved_count: number;
    updated_by_recruiter: boolean;
    finalized_at: string | null;
};

export default function ProjectsIndex({ projects }: { projects: Project[] }) {
    return (
        <>
            <PrivateSeoHead title="Recruiter submissions" />
            <div className="flex flex-col gap-5 p-2 sm:px-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Recruiter submissions
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Projects sent by recruiters. Add each student's
                        documents, then send them back for approval.
                    </p>
                </div>

                {projects.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        No recruiter has submitted a project yet.
                    </div>
                ) : (
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {projects.map((project) => {
                            const pct = (count: number) =>
                                project.students_count === 0
                                    ? 0
                                    : Math.round(
                                          (count / project.students_count) *
                                              100,
                                      );

                            return (
                                <Link
                                    key={project.id}
                                    href={show(project.id)}
                                    className="group flex flex-col gap-4 rounded-3xl border border-border bg-card p-4 shadow-sm transition-all active:scale-[0.98] md:hover:-translate-y-0.5 md:hover:border-brand-warm/50 md:hover:shadow-md"
                                >
                                    <div className="flex items-start gap-3">
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-warm/15 text-brand-warm">
                                            <BriefcaseBusiness className="size-5" />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <h2 className="truncate text-base font-semibold capitalize">
                                                {project.name}
                                            </h2>
                                            <p className="truncate text-xs text-muted-foreground">
                                                by {project.recruiter}
                                            </p>
                                        </div>
                                        <ChevronRight className="size-5 shrink-0 text-muted-foreground transition-transform md:group-hover:translate-x-0.5" />
                                    </div>

                                    <div className="flex flex-wrap gap-1.5">
                                        {project.updated_by_recruiter && (
                                            <span className="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] font-medium text-amber-600">
                                                <AlertCircle className="size-3" />
                                                Updated
                                            </span>
                                        )}
                                        {[
                                            project.country,
                                            project.sector,
                                            project.timeline,
                                        ]
                                            .filter(Boolean)
                                            .map((chip) => (
                                                <span
                                                    key={chip}
                                                    className="rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground capitalize"
                                                >
                                                    {chip}
                                                </span>
                                            ))}
                                    </div>

                                    <div className="flex flex-col gap-2 rounded-2xl bg-muted/40 p-3">
                                        <div className="flex items-baseline justify-between text-xs text-muted-foreground tabular-nums">
                                            <span>
                                                <b className="text-sm text-foreground">
                                                    {project.approved_count}
                                                </b>{' '}
                                                of {project.students_count}{' '}
                                                approved
                                            </span>
                                            <span>
                                                {project.sent_count} sent
                                            </span>
                                        </div>
                                        <div className="relative h-1.5 overflow-hidden rounded-full bg-border">
                                            <span
                                                className="absolute inset-y-0 left-0 rounded-full bg-brand-warm/40"
                                                style={{
                                                    width: `${pct(project.sent_count)}%`,
                                                }}
                                            />
                                            <span
                                                className="absolute inset-y-0 left-0 rounded-full bg-emerald-500"
                                                style={{
                                                    width: `${pct(project.approved_count)}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}
