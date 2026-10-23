import { Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    CircleDashed,
    Download,
    Eye,
    FileUp,
    MessageSquare,
    Send,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/responsive-dialog';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { index, message } from '@/routes/recruitment-manager/projects';
import { send } from '@/routes/recruitment-manager/projects/candidates';
import { upload } from '@/routes/recruitment-manager/projects/requirements';
import DocumentPreviewDialog from '@app/components/document-preview-dialog';
import type { PreviewTarget } from '@app/components/document-preview-dialog';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Requirement = {
    id: number;
    title: string;
    file_name: string | null;
    download_url: string | null;
    view_url: string | null;
    review_status: 'pending' | 'approved' | 'rejected';
    rejection_note: string | null;
    sample_name: string | null;
    sample_url: string | null;
    sample_view_url: string | null;
};

type Entry = {
    student_profile_id: number;
    name: string;
    student_number: string | null;
    status: string;
    uploaded: number;
    total: number;
    can_send: boolean;
    locked: boolean;
    congratulated_at: string | null;
    requirements_sent_at: string | null;
    requirements: Requirement[];
};

type Props = {
    project: {
        id: number;
        name: string;
        recruiter: string;
        recruiter_email: string;
        country: string;
        sector: string;
        timeline: string;
        stage: string;
        updated_by_recruiter: boolean;
    };
    entries: Entry[];
    defaults: { congratulate: string; requirements: string };
};

const STATUS: Record<string, { label: string; style: string }> = {
    awaiting: {
        label: 'Awaiting documents',
        style: 'bg-muted text-muted-foreground',
    },
    ready: { label: 'Ready to send', style: 'bg-sky-600/15 text-sky-600' },
    sent: {
        label: 'Awaiting recruiter',
        style: 'bg-amber-500/15 text-amber-600',
    },
    changes_requested: {
        label: 'Changes requested',
        style: 'bg-destructive/15 text-destructive',
    },
    approved: {
        label: 'Approved by recruiter',
        style: 'bg-emerald-600/15 text-emerald-600',
    },
    draft: { label: 'Draft', style: 'bg-muted text-muted-foreground' },
};

const REVIEW: Record<Requirement['review_status'], string> = {
    pending: 'text-muted-foreground',
    approved: 'text-emerald-600',
    rejected: 'text-destructive',
};

const REVIEW_ICON = {
    pending: CircleDashed,
    approved: CheckCircle2,
    rejected: XCircle,
};

type MessageTarget = {
    type: 'congratulate' | 'requirements';
    student: Entry | null;
};

export default function ProjectShow({ project, entries, defaults }: Props) {
    const { flash, errors } = usePage().props as {
        flash?: { success?: string };
        errors: Record<string, string>;
    };
    const [target, setTarget] = useState<MessageTarget | null>(null);
    const [text, setText] = useState('');
    const [sending, setSending] = useState(false);
    const [preview, setPreview] = useState<PreviewTarget | null>(null);

    const openMessage = (next: MessageTarget) => {
        setText(defaults[next.type]);
        setTarget(next);
    };

    const submitMessage = () => {
        if (!target) {
            return;
        }

        router.post(
            message.url(project.id),
            {
                type: target.type,
                message: text,
                student_profile_id: target.student?.student_profile_id ?? null,
            },
            {
                preserveScroll: true,
                onStart: () => setSending(true),
                onFinish: () => setSending(false),
                onSuccess: () => setTarget(null),
            },
        );
    };

    return (
        <>
            <PrivateSeoHead title={project.name} />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <Link
                    href={index()}
                    className="inline-flex h-9 w-fit items-center gap-2 rounded-full bg-muted/60 pr-4 pl-3 text-sm font-medium transition-colors active:bg-muted md:hover:bg-muted"
                >
                    <ArrowLeft className="size-4" />
                    All submissions
                </Link>

                <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div className="min-w-0">
                        <h1 className="text-2xl font-semibold tracking-tight capitalize">
                            {project.name}
                        </h1>
                        <p className="mt-1 truncate text-sm text-muted-foreground">
                            {project.recruiter} · {project.recruiter_email}
                        </p>
                        <div className="mt-3 flex flex-wrap gap-1.5">
                            {[project.country, project.sector, project.timeline]
                                .filter(Boolean)
                                .map((chip) => (
                                    <span
                                        key={chip}
                                        className="rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground capitalize"
                                    >
                                        {chip}
                                    </span>
                                ))}
                        </div>
                        {project.updated_by_recruiter && (
                            <p className="mt-3 rounded-2xl bg-amber-500/10 px-3 py-2 text-xs font-medium text-amber-600">
                                The recruiter changed students or requirements
                                after sending.
                            </p>
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-2 md:flex md:shrink-0">
                        <Button
                            variant="outline"
                            className="h-10 rounded-full"
                            onClick={() =>
                                openMessage({
                                    type: 'congratulate',
                                    student: null,
                                })
                            }
                        >
                            Congratulate all
                        </Button>
                        <Button
                            variant="outline"
                            className="h-10 rounded-full"
                            onClick={() =>
                                openMessage({
                                    type: 'requirements',
                                    student: null,
                                })
                            }
                        >
                            Send requirements
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <p className="rounded-xl bg-emerald-600/10 px-4 py-2 text-sm text-emerald-600">
                        {flash.success}
                    </p>
                )}
                {(errors.send || errors.review) && (
                    <p className="rounded-xl bg-destructive/10 px-4 py-2 text-sm text-destructive">
                        {errors.send ?? errors.review}
                    </p>
                )}

                <div className="grid items-start gap-4 xl:grid-cols-2">
                    {entries.map((entry) => {
                        const status = STATUS[entry.status] ?? STATUS.awaiting;
                        const progress =
                            entry.total === 0
                                ? 0
                                : Math.round(
                                      (entry.uploaded / entry.total) * 100,
                                  );

                        return (
                            <section
                                key={entry.student_profile_id}
                                className="overflow-hidden rounded-3xl border border-border bg-card shadow-sm"
                            >
                                <div className="flex flex-col gap-3 p-4">
                                    <div className="flex items-center gap-3">
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-warm/15 text-sm font-semibold text-brand-warm">
                                            {entry.name
                                                .split(' ')
                                                .slice(0, 2)
                                                .map((word) =>
                                                    word[0]?.toUpperCase(),
                                                )
                                                .join('')}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <h2 className="truncate text-base font-semibold capitalize">
                                                {entry.name}
                                            </h2>
                                            <p className="truncate text-xs text-muted-foreground">
                                                {entry.student_number}
                                            </p>
                                        </div>
                                        <span
                                            className={cn(
                                                'shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                                status.style,
                                            )}
                                        >
                                            {status.label}
                                        </span>
                                    </div>

                                    <div className="flex items-center gap-3">
                                        <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-border">
                                            <span
                                                className="block h-full rounded-full bg-emerald-500 transition-[width]"
                                                style={{
                                                    width: `${progress}%`,
                                                }}
                                            />
                                        </div>
                                        <span className="text-xs text-muted-foreground tabular-nums">
                                            {entry.uploaded}/{entry.total} files
                                        </span>
                                    </div>

                                    <div className="grid grid-cols-2 gap-2">
                                        <Button
                                            variant="outline"
                                            className={cn(
                                                'h-10 rounded-full',
                                                !entry.can_send && 'col-span-2',
                                            )}
                                            onClick={() =>
                                                openMessage({
                                                    type: 'requirements',
                                                    student: entry,
                                                })
                                            }
                                        >
                                            <MessageSquare className="size-4" />
                                            Message
                                        </Button>
                                        {entry.can_send && (
                                            <Button
                                                className="h-10 rounded-full"
                                                onClick={() =>
                                                    router.post(
                                                        send.url({
                                                            project: project.id,
                                                            studentProfile:
                                                                entry.student_profile_id,
                                                        }),
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                <Send className="size-4" />
                                                Send to recruiter
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                <ul className="flex flex-col divide-y divide-border/60 border-t border-border/70 bg-muted/30">
                                    {entry.requirements.map((requirement) => {
                                        const Icon =
                                            REVIEW_ICON[
                                                requirement.review_status
                                            ];

                                        return (
                                            <li
                                                key={requirement.id}
                                                className="flex flex-col gap-2 px-4 py-3"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <Icon
                                                        className={cn(
                                                            'size-5 shrink-0',
                                                            REVIEW[
                                                                requirement
                                                                    .review_status
                                                            ],
                                                        )}
                                                    />
                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate text-sm font-medium">
                                                            {requirement.title}
                                                        </p>
                                                        <p
                                                            className={cn(
                                                                'truncate text-xs',
                                                                REVIEW[
                                                                    requirement
                                                                        .review_status
                                                                ],
                                                            )}
                                                        >
                                                            {requirement.file_name ??
                                                                'No file yet'}
                                                            {requirement.file_name &&
                                                                ` · ${requirement.review_status}`}
                                                        </p>
                                                    </div>
                                                    <div className="flex shrink-0 items-center gap-1">
                                                        {requirement.view_url && (
                                                            <button
                                                                type="button"
                                                                aria-label="View"
                                                                className="inline-flex size-10 items-center justify-center rounded-full transition-colors active:bg-muted md:hover:bg-muted"
                                                                onClick={() =>
                                                                    requirement.view_url &&
                                                                    setPreview({
                                                                        title: `${entry.name} · ${requirement.title}`,
                                                                        fileName:
                                                                            requirement.file_name,
                                                                        viewUrl:
                                                                            requirement.view_url,
                                                                        downloadUrl:
                                                                            requirement.download_url,
                                                                    })
                                                                }
                                                            >
                                                                <Eye className="size-4.5" />
                                                            </button>
                                                        )}
                                                        {requirement.download_url && (
                                                            <a
                                                                href={
                                                                    requirement.download_url
                                                                }
                                                                className="inline-flex size-10 items-center justify-center rounded-full transition-colors active:bg-muted md:hover:bg-muted"
                                                                aria-label="Download"
                                                            >
                                                                <Download className="size-4.5" />
                                                            </a>
                                                        )}
                                                        {!entry.locked && (
                                                            <label
                                                                className="inline-flex size-10 cursor-pointer items-center justify-center rounded-full bg-brand-warm/15 text-brand-warm transition-colors active:bg-brand-warm/25 md:hover:bg-brand-warm/25"
                                                                aria-label={
                                                                    requirement.file_name
                                                                        ? 'Replace'
                                                                        : 'Upload'
                                                                }
                                                            >
                                                                <FileUp className="size-4.5" />
                                                                <input
                                                                    type="file"
                                                                    className="sr-only"
                                                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                                    onChange={(
                                                                        e,
                                                                    ) => {
                                                                        const file =
                                                                            e
                                                                                .target
                                                                                .files?.[0];

                                                                        if (
                                                                            !file
                                                                        ) {
                                                                            return;
                                                                        }

                                                                        router.post(
                                                                            upload.url(
                                                                                {
                                                                                    project:
                                                                                        project.id,
                                                                                    requirement:
                                                                                        requirement.id,
                                                                                },
                                                                            ),
                                                                            {
                                                                                file,
                                                                            },
                                                                            {
                                                                                forceFormData: true,
                                                                                preserveScroll: true,
                                                                            },
                                                                        );
                                                                        e.target.value =
                                                                            '';
                                                                    }}
                                                                />
                                                            </label>
                                                        )}
                                                    </div>
                                                </div>
                                                {requirement.review_status ===
                                                    'rejected' && (
                                                    <p className="rounded-xl bg-destructive/10 px-3 py-2 text-xs text-destructive">
                                                        Recruiter:{' '}
                                                        {
                                                            requirement.rejection_note
                                                        }
                                                        {requirement.sample_view_url && (
                                                            <button
                                                                type="button"
                                                                className="ml-1 font-medium underline"
                                                                onClick={() =>
                                                                    requirement.sample_view_url &&
                                                                    setPreview({
                                                                        title: `${requirement.title} · sample`,
                                                                        fileName:
                                                                            requirement.sample_name,
                                                                        viewUrl:
                                                                            requirement.sample_view_url,
                                                                        downloadUrl:
                                                                            requirement.sample_url,
                                                                    })
                                                                }
                                                            >
                                                                View sample
                                                            </button>
                                                        )}
                                                    </p>
                                                )}
                                            </li>
                                        );
                                    })}
                                </ul>
                            </section>
                        );
                    })}
                </div>
            </div>

            <DocumentPreviewDialog
                target={preview}
                onClose={() => setPreview(null)}
            />

            <Dialog
                open={target !== null}
                onOpenChange={(open) => !open && setTarget(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {target?.type === 'congratulate'
                                ? 'Send congratulations'
                                : 'Send requirements'}
                            {target?.student
                                ? ` to ${target.student.name}`
                                : ' to all students'}
                        </DialogTitle>
                        <DialogDescription>
                            Sent in-app, by email and on WhatsApp when the
                            student has a number.
                            {target?.type === 'requirements' &&
                                " Each student's own document list is added below your message."}
                        </DialogDescription>
                    </DialogHeader>
                    <Textarea
                        value={text}
                        maxLength={600}
                        rows={4}
                        onChange={(e) => setText(e.target.value)}
                    />
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setTarget(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            disabled={sending || text.trim() === ''}
                            onClick={submitMessage}
                        >
                            Send
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
