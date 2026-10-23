import { Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    ChevronDown,
    FileText,
    FolderInput,
    Pencil,
    Send,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { destroy, index } from '@/routes/recruiter/projects';
import {
    destroy as removeCandidate,
    move as moveCandidate,
} from '@/routes/recruiter/projects/candidates';
import CandidateCard from '@app/components/candidate-card';
import ConfirmDialog from '@app/components/confirm-dialog';
import ProjectDocumentsSheet from '@app/components/project-documents-sheet';
import ProjectFormDialog from '@app/components/project-form-dialog';
import ProjectRequirementsSheet from '@app/components/project-requirements-sheet';
import type {
    CandidateCard as CandidateCardData,
    ProjectSummary,
} from '@app/types/recruiter';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    project: ProjectSummary;
    candidates: CandidateCardData[];
    otherProjects: { id: number; name: string }[];
};

const STAGE_LABELS = {
    draft: 'Draft',
    in_progress: 'In progress',
    approved: 'Approved',
} as const;

const STAGE_STYLES = {
    draft: 'bg-muted text-muted-foreground',
    in_progress: 'bg-sky-600/15 text-sky-600',
    approved: 'bg-emerald-600/15 text-emerald-600',
} as const;

function EntryStatus({
    candidate,
    onOpenDocuments,
}: {
    candidate: CandidateCardData;
    onOpenDocuments: () => void;
}) {
    const entry = candidate.entry;

    if (!entry || entry.status === 'draft') {
        return null;
    }

    if (['approved', 'sent', 'changes_requested'].includes(entry.status)) {
        const approved = entry.status === 'approved';

        return (
            <button
                type="button"
                onClick={onOpenDocuments}
                className={cn(
                    'flex h-9 items-center justify-center gap-1.5 rounded-full text-xs font-semibold transition-transform active:scale-95',
                    approved
                        ? 'bg-emerald-600/15 text-emerald-600'
                        : 'bg-amber-500/15 text-amber-600',
                )}
            >
                {approved ? (
                    <CheckCircle2 className="size-4" />
                ) : (
                    <FileText className="size-4" />
                )}
                {approved
                    ? 'Approved · Documents'
                    : entry.status === 'sent'
                      ? 'Review documents'
                      : 'Waiting for BV changes'}
            </button>
        );
    }

    return (
        <div className="space-y-1.5 rounded-2xl border border-border bg-card/60 px-3 py-2">
            <div className="flex items-center justify-between gap-2 text-xs">
                <span className="font-medium">
                    {entry.status === 'ready'
                        ? 'Ready for approval'
                        : 'Awaiting documents'}
                </span>
                <span className="text-muted-foreground tabular-nums">
                    {entry.uploaded}/{entry.total}
                </span>
            </div>
            <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                <div
                    className={cn(
                        'h-full rounded-full',
                        entry.status === 'ready'
                            ? 'bg-emerald-500'
                            : 'bg-brand-cool',
                    )}
                    style={{
                        width: `${entry.total > 0 ? (entry.uploaded / entry.total) * 100 : 0}%`,
                    }}
                />
            </div>
        </div>
    );
}

export default function ProjectShow({
    project,
    candidates,
    otherProjects,
}: Props) {
    const [requirementsOpen, setRequirementsOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [documentsFor, setDocumentsFor] = useState<CandidateCardData | null>(
        null,
    );
    const canFinalize = candidates.length > 0;

    return (
        <>
            <PrivateSeoHead title={project.name} />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-col gap-4">
                    <Link
                        href={index()}
                        className="inline-flex w-fit items-center gap-2 text-sm text-muted-foreground hover:underline"
                    >
                        <ArrowLeft className="size-4" />
                        All projects
                    </Link>

                    <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between md:gap-3">
                        <div className="min-w-0">
                            <div className="flex items-center gap-3">
                                <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                                    {project.name}
                                </h1>
                            </div>
                            <p className="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                <span
                                    className={cn(
                                        'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        STAGE_STYLES[project.stage],
                                    )}
                                >
                                    {STAGE_LABELS[project.stage]}
                                </span>
                                {[
                                    project.country,
                                    project.sector,
                                    project.timeline,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            {project.stage === 'draft' ? (
                                <Button
                                    size="sm"
                                    className="h-10 flex-1 rounded-full md:h-8 md:flex-none"
                                    disabled={!canFinalize}
                                    onClick={() => setRequirementsOpen(true)}
                                >
                                    <Send className="size-4" />
                                    Finalize list
                                </Button>
                            ) : (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="h-10 flex-1 rounded-full md:h-8 md:flex-none"
                                    onClick={() => setRequirementsOpen(true)}
                                >
                                    <Pencil className="size-4" />
                                    Edit requirements
                                </Button>
                            )}
                            <ProjectFormDialog project={project}>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="rounded-full max-sm:size-10 max-sm:p-0"
                                >
                                    <Pencil className="size-4" />
                                    <span className="max-sm:sr-only">Edit</span>
                                </Button>
                            </ProjectFormDialog>
                            <Button
                                variant="outline"
                                size="sm"
                                className="rounded-full max-sm:size-10 max-sm:p-0"
                                disabled={project.approved_count > 0}
                                title={
                                    project.approved_count > 0
                                        ? 'Projects with approved candidates cannot be deleted'
                                        : undefined
                                }
                                onClick={() => setDeleteOpen(true)}
                            >
                                <Trash2 className="size-4" />
                                <span className="max-sm:sr-only">Delete</span>
                            </Button>
                        </div>
                    </div>

                    {project.stage === 'draft' && !canFinalize && (
                        <p className="-mt-2 text-xs text-muted-foreground">
                            Add at least one candidate to finalize.
                        </p>
                    )}
                </div>

                <section className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Shortlist · {candidates.length}
                        {project.finalized &&
                            ` · ${project.approved_count} approved`}
                    </h2>

                    {candidates.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                            No candidates shortlisted yet. Open a student's
                            profile and use "Add to project".
                        </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                            {candidates.map((candidate) => (
                                <div
                                    key={candidate.id}
                                    className="flex flex-col gap-2"
                                >
                                    <CandidateCard candidate={candidate} />
                                    <EntryStatus
                                        candidate={candidate}
                                        onOpenDocuments={() =>
                                            setDocumentsFor(candidate)
                                        }
                                    />
                                    {candidate.entry?.status !== 'approved' && (
                                        <div className="flex gap-2">
                                            {otherProjects.length > 0 && (
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger
                                                        asChild
                                                    >
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            className="h-8 flex-1 justify-between gap-1 rounded-full text-xs"
                                                        >
                                                            <span className="inline-flex items-center gap-1.5">
                                                                <FolderInput className="size-3.5" />
                                                                Move to
                                                            </span>
                                                            <ChevronDown className="size-3.5 opacity-60" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent
                                                        align="start"
                                                        className="min-w-52 rounded-2xl p-1.5 shadow-xl"
                                                    >
                                                        {otherProjects.map(
                                                            (other) => (
                                                                <DropdownMenuItem
                                                                    key={
                                                                        other.id
                                                                    }
                                                                    className="rounded-xl py-2"
                                                                    onSelect={() =>
                                                                        router.post(
                                                                            moveCandidate.url(
                                                                                {
                                                                                    project:
                                                                                        project.id,
                                                                                    studentProfile:
                                                                                        candidate.id,
                                                                                },
                                                                            ),
                                                                            {
                                                                                target_project_id:
                                                                                    other.id,
                                                                            },
                                                                            {
                                                                                preserveScroll: true,
                                                                            },
                                                                        )
                                                                    }
                                                                >
                                                                    {other.name}
                                                                </DropdownMenuItem>
                                                            ),
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            )}
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="h-8 rounded-full text-xs"
                                                onClick={() =>
                                                    router.delete(
                                                        removeCandidate.url({
                                                            project: project.id,
                                                            studentProfile:
                                                                candidate.id,
                                                        }),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Remove
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>

            {requirementsOpen && (
                <ProjectRequirementsSheet
                    key={`${project.id}-${project.requirement_template.join('|')}-${candidates.length}`}
                    project={project}
                    candidates={candidates}
                    open
                    onOpenChange={setRequirementsOpen}
                />
            )}
            <ConfirmDialog
                open={deleteOpen}
                onOpenChange={setDeleteOpen}
                title="Delete this project?"
                description={
                    <>
                        <strong>{project.name}</strong> and its shortlist will
                        be removed
                        {project.finalized &&
                            ', including the requirements BV has been given and any documents uploaded so far'}
                        . The candidates themselves are not affected.
                    </>
                }
                confirmLabel="Yes, delete"
                cancelLabel="No, keep it"
                destructive
                onConfirm={() => router.delete(destroy.url(project.id))}
            />
            <ProjectDocumentsSheet
                candidate={documentsFor}
                projectId={project.id}
                onClose={() => setDocumentsFor(null)}
            />
        </>
    );
}

ProjectShow.layout = {
    breadcrumbs: [{ title: 'Projects', href: index() }],
};
