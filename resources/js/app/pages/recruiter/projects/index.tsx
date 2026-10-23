import { Link } from '@inertiajs/react';
import { ChevronRight, FolderOpen, Plus } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { index, show } from '@/routes/recruiter/projects';
import ProjectFormDialog from '@app/components/project-form-dialog';
import type { ProjectStage, ProjectSummary } from '@app/types/recruiter';
import { PrivateSeoHead } from '@shared/components/seo-head';

const TABS: { stage: ProjectStage; label: string }[] = [
    { stage: 'draft', label: 'Drafts' },
    { stage: 'in_progress', label: 'In progress' },
    { stage: 'approved', label: 'Approved' },
];

export default function ProjectsIndex({
    projects,
}: {
    projects: ProjectSummary[];
}) {
    const [stage, setStage] = useState<ProjectStage>('draft');
    const visible = projects.filter((project) => project.stage === stage);

    return (
        <>
            <PrivateSeoHead title="Projects" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                            Projects
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground md:mt-2">
                            Create a folder per hiring need and shortlist
                            candidates into it.
                        </p>
                    </div>
                    <div className="hidden md:block">
                        <ProjectFormDialog>
                            <Button>
                                <Plus className="size-4" />
                                Create project
                            </Button>
                        </ProjectFormDialog>
                    </div>
                </div>

                <div
                    role="tablist"
                    aria-label="Project stage"
                    className="inline-flex w-full rounded-full border border-border bg-card p-1 shadow-sm md:w-fit [&>button]:flex-1 md:[&>button]:flex-none"
                >
                    {TABS.map((tab) => {
                        const count = projects.filter(
                            (project) => project.stage === tab.stage,
                        ).length;
                        const selected = stage === tab.stage;

                        return (
                            <button
                                key={tab.stage}
                                type="button"
                                role="tab"
                                aria-selected={selected}
                                onClick={() => setStage(tab.stage)}
                                className={cn(
                                    'inline-flex h-9 items-center justify-center gap-1.5 rounded-full px-4 text-[13px] whitespace-nowrap transition-colors md:text-sm',
                                    selected
                                        ? 'bg-primary font-medium text-primary-foreground shadow-sm'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {tab.label}
                                <span
                                    className={cn(
                                        'rounded-full px-1.5 text-xs font-bold',
                                        selected ? 'bg-white/25' : 'bg-muted',
                                    )}
                                >
                                    {count}
                                </span>
                            </button>
                        );
                    })}
                </div>

                {visible.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 rounded-3xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        <FolderOpen className="size-8 text-muted-foreground/60" />
                        Nothing here yet.
                    </div>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-3">
                        {visible.map((project) => (
                            <Link
                                key={project.id}
                                href={show(project.id)}
                                className="group flex flex-col gap-4 rounded-3xl border border-border bg-card/60 p-4 shadow-sm transition-all focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none active:scale-[0.98] sm:p-5 sm:hover:shadow-md"
                            >
                                <div className="flex items-center gap-3">
                                    <span className="flex size-11 items-center justify-center rounded-2xl bg-brand-cool/15 text-brand-cool">
                                        <FolderOpen className="size-5" />
                                    </span>
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold">
                                            {project.name}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground sm:hidden">
                                            {[project.country, project.sector]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {project.candidates_count}{' '}
                                            {project.candidates_count === 1
                                                ? 'candidate'
                                                : 'candidates'}
                                        </p>
                                    </div>
                                    <ChevronRight className="ml-auto size-5 shrink-0 text-muted-foreground/60 sm:hidden" />
                                </div>
                                {project.stage === 'in_progress' && (
                                    <div className="space-y-1.5">
                                        <div className="flex justify-between text-xs">
                                            <span className="text-muted-foreground">
                                                Approved
                                            </span>
                                            <span className="font-semibold tabular-nums">
                                                {project.approved_count}/
                                                {project.candidates_count}
                                            </span>
                                        </div>
                                        <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full bg-emerald-500"
                                                style={{
                                                    width: `${project.candidates_count > 0 ? (project.approved_count / project.candidates_count) * 100 : 0}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                )}
                                <dl className="hidden grid-cols-3 gap-2 text-xs sm:grid">
                                    {(
                                        [
                                            ['Country', project.country],
                                            ['Sector', project.sector],
                                            ['Timeline', project.timeline],
                                        ] as const
                                    ).map(([label, value]) => (
                                        <div key={label} className="min-w-0">
                                            <dt className="text-muted-foreground">
                                                {label}
                                            </dt>
                                            <dd className="truncate font-medium">
                                                {value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            <div className="fixed right-4 bottom-[calc(var(--app-bottom-h,0px)+1rem)] z-30 md:hidden print:hidden">
                <ProjectFormDialog>
                    <Button
                        size="icon"
                        className="size-14 rounded-full shadow-xl active:scale-95"
                        aria-label="Create project"
                    >
                        <Plus className="size-6" />
                    </Button>
                </ProjectFormDialog>
            </div>
        </>
    );
}

ProjectsIndex.layout = {
    breadcrumbs: [{ title: 'Projects', href: index() }],
};
