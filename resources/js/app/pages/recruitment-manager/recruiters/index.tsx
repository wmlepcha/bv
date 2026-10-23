import { Link, usePage } from '@inertiajs/react';
import { ChevronRight, MessageCircle, Plus, UserRoundX } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { show as projectShow } from '@/routes/recruitment-manager/projects';
import { create, edit } from '@/routes/recruitment-manager/recruiters';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Recruiter = {
    id: number;
    name: string;
    email: string;
    whatsapp_number: string | null;
    projects_count: number;
    projects: {
        id: number;
        name: string;
        country: string;
        sector: string;
        finalized: boolean;
        students_count: number;
        approved_count: number;
    }[];
};

function ProjectList({ projects }: { projects: Recruiter['projects'] }) {
    if (projects.length === 0) {
        return <p className="text-xs text-muted-foreground">No projects yet</p>;
    }

    return (
        <ul className="flex flex-col gap-1.5">
            {projects.map((project) => {
                const body = (
                    <>
                        <span className="min-w-0 flex-1">
                            <span className="block truncate text-sm font-medium capitalize">
                                {project.name}
                            </span>
                            <span className="block truncate text-[11px] text-muted-foreground capitalize">
                                {[project.country, project.sector]
                                    .filter(Boolean)
                                    .join(' · ') || '—'}
                            </span>
                        </span>
                        <span
                            className={cn(
                                'shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium tabular-nums',
                                project.finalized
                                    ? 'bg-emerald-500/15 text-emerald-600'
                                    : 'bg-muted text-muted-foreground',
                            )}
                        >
                            {project.finalized
                                ? `${project.approved_count}/${project.students_count} approved`
                                : 'Draft'}
                        </span>
                    </>
                );
                const row =
                    'flex items-center gap-2 rounded-xl bg-background px-3 py-2';

                return (
                    <li key={project.id}>
                        {project.finalized ? (
                            <Link
                                href={projectShow(project.id)}
                                className={cn(
                                    row,
                                    'transition-colors active:bg-muted md:hover:bg-muted',
                                )}
                            >
                                {body}
                            </Link>
                        ) : (
                            <div className={row}>{body}</div>
                        )}
                    </li>
                );
            })}
        </ul>
    );
}

export default function RecruitersIndex({
    recruiters,
}: {
    recruiters: Recruiter[];
}) {
    const { flash } = usePage().props as { flash?: { success?: string } };

    return (
        <>
            <PrivateSeoHead title="Recruiters" />
            <div className="flex flex-col gap-5 p-2 sm:px-4">
                <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Recruiters
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Recruiter accounts. They sign in through the
                            recruiter/staff login.
                        </p>
                    </div>
                    <Button asChild className="h-11 rounded-full md:h-10">
                        <Link href={create()}>
                            <Plus className="size-4" />
                            Add recruiter
                        </Link>
                    </Button>
                </div>

                {flash?.success && (
                    <p className="rounded-xl bg-emerald-600/10 px-4 py-2 text-sm text-emerald-600">
                        {flash.success}
                    </p>
                )}

                {recruiters.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-3xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        <UserRoundX className="size-8" />
                        No recruiters yet.
                    </div>
                ) : (
                    <>
                        <div className="flex flex-col gap-3 md:hidden">
                            {recruiters.map((recruiter) => (
                                <div
                                    key={recruiter.id}
                                    className="overflow-hidden rounded-3xl border border-border bg-card shadow-sm"
                                >
                                    <Link
                                        href={edit(recruiter.id)}
                                        className="flex items-center gap-3 p-4 active:bg-muted/50"
                                    >
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-warm/15 text-sm font-semibold text-brand-warm">
                                            {recruiter.name
                                                .split(' ')
                                                .slice(0, 2)
                                                .map((word) =>
                                                    word[0]?.toUpperCase(),
                                                )
                                                .join('')}
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate text-base font-semibold capitalize">
                                                {recruiter.name}
                                            </span>
                                            <span className="block truncate text-xs text-muted-foreground">
                                                {recruiter.email}
                                            </span>
                                            <span className="mt-1.5 flex flex-wrap gap-1.5">
                                                {recruiter.whatsapp_number && (
                                                    <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] text-emerald-600">
                                                        <MessageCircle className="size-3" />
                                                        {
                                                            recruiter.whatsapp_number
                                                        }
                                                    </span>
                                                )}
                                            </span>
                                        </span>
                                        <ChevronRight className="size-5 shrink-0 text-muted-foreground" />
                                    </Link>
                                    <div className="flex flex-col gap-2 border-t border-border/70 bg-muted/30 p-3">
                                        <p className="px-1 text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
                                            Projects ·{' '}
                                            {recruiter.projects_count}
                                        </p>
                                        <ProjectList
                                            projects={recruiter.projects}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="hidden overflow-hidden rounded-3xl border border-border bg-card shadow-sm md:block">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-border bg-muted/40 text-xs text-muted-foreground">
                                    <tr>
                                        <th className="p-4 font-medium">
                                            Recruiter
                                        </th>
                                        <th className="p-4 font-medium">
                                            WhatsApp
                                        </th>
                                        <th className="p-4 font-medium">
                                            Projects
                                        </th>
                                        <th className="p-4" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/70">
                                    {recruiters.map((recruiter) => (
                                        <tr
                                            key={recruiter.id}
                                            className="transition-colors hover:bg-muted/30"
                                        >
                                            <td className="p-4 align-top">
                                                <div className="flex items-center gap-3">
                                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-warm/15 text-sm font-semibold text-brand-warm">
                                                        {recruiter.name
                                                            .split(' ')
                                                            .slice(0, 2)
                                                            .map((word) =>
                                                                word[0]?.toUpperCase(),
                                                            )
                                                            .join('')}
                                                    </span>
                                                    <div className="min-w-0">
                                                        <p className="font-medium capitalize">
                                                            {recruiter.name}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {recruiter.email}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="p-4 text-muted-foreground">
                                                {recruiter.whatsapp_number ??
                                                    '—'}
                                            </td>
                                            <td className="w-80 p-4 align-top">
                                                <ProjectList
                                                    projects={
                                                        recruiter.projects
                                                    }
                                                />
                                            </td>
                                            <td className="p-4 text-right">
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                    className="rounded-full"
                                                >
                                                    <Link
                                                        href={edit(
                                                            recruiter.id,
                                                        )}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </div>
        </>
    );
}
