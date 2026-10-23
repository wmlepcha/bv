import { router } from '@inertiajs/react';
import { Check, Download, Eye, FileText, Paperclip, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/responsive-sheet';
import { Textarea } from '@/components/ui/textarea';
import { useMediaQuery } from '@/hooks/use-media-query';
import { cn } from '@/lib/utils';
import { approveAll, review } from '@/routes/recruiter/projects/candidates';
import DocumentPreviewDialog from '@app/components/document-preview-dialog';
import type { PreviewTarget } from '@app/components/document-preview-dialog';
import type {
    CandidateCard as CandidateCardData,
    ProjectRequirement,
} from '@app/types/recruiter';

type Decision = { approve: boolean; note: string; sample: File | null };

const EMPTY_DECISION: Decision = { approve: true, note: '', sample: null };

const STATUS_STYLES = {
    pending: 'bg-muted text-muted-foreground',
    approved: 'bg-emerald-600/15 text-emerald-600',
    rejected: 'bg-destructive/15 text-destructive',
} as const;

const STATUS_LABELS = {
    pending: 'Pending review',
    approved: 'Approved',
    rejected: 'Rejected',
} as const;

function FileLink({
    requirement,
    onView,
}: {
    requirement: ProjectRequirement;
    onView: () => void;
}) {
    return (
        <div className="flex items-center gap-3 rounded-2xl bg-muted/40 px-3 py-2.5">
            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-cool/15 text-brand-cool">
                <FileText className="size-4" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block truncate text-sm font-medium">
                    {requirement.title}
                </span>
                <span className="block truncate text-xs text-muted-foreground">
                    {requirement.file_name ?? 'No file'}
                </span>
            </span>
            {requirement.view_url && (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-8 shrink-0 rounded-full px-3 text-xs"
                    onClick={onView}
                >
                    <Eye className="size-4" />
                    View
                </Button>
            )}
            {requirement.download_url && (
                <a
                    href={requirement.download_url}
                    aria-label={`Download ${requirement.title}`}
                    className="flex size-8 shrink-0 items-center justify-center rounded-full text-muted-foreground hover:bg-muted"
                >
                    <Download className="size-4" />
                </a>
            )}
        </div>
    );
}

export default function ProjectDocumentsSheet({
    candidate,
    projectId,
    onClose,
}: {
    candidate: CandidateCardData | null;
    projectId: number;
    onClose: () => void;
}) {
    const isDesktop = useMediaQuery('(min-width: 768px)');
    const [decisions, setDecisions] = useState<Record<number, Decision>>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);
    const [preview, setPreview] = useState<PreviewTarget | null>(null);
    const justClosedPreview = useRef(false);

    // The preview opens on top of this sheet. Closing it must not count as a dismissal of the sheet underneath,
    // so ignore the sheet's own close request while the preview is open and for a moment after it closes.
    const closePreview = () => {
        justClosedPreview.current = true;
        setPreview(null);
        window.setTimeout(() => {
            justClosedPreview.current = false;
        }, 500);
    };
    const handleSheetOpenChange = (open: boolean) => {
        if (open || preview !== null || justClosedPreview.current) {
            return;
        }

        close();
    };

    const entry = candidate?.entry;
    const reviewable =
        entry !== undefined &&
        (entry.status === 'sent' || entry.status === 'changes_requested');
    const target = candidate
        ? { project: projectId, studentProfile: candidate.id }
        : null;

    const decide = (id: number, patch: Partial<Decision>) =>
        setDecisions((current) => ({
            ...current,
            [id]: { ...(current[id] ?? EMPTY_DECISION), ...patch },
        }));

    const close = () => {
        setDecisions({});
        setErrors({});
        onClose();
    };

    const options = {
        preserveScroll: true,
        forceFormData: true,
        onStart: () => setBusy(true),
        onFinish: () => setBusy(false),
        onError: (e: Record<string, string>) => setErrors(e),
        onSuccess: () => {
            setDecisions({});
            setErrors({});
        },
    };

    const submit = () => {
        if (!target) {
            return;
        }

        const payload = Object.fromEntries(
            Object.entries(decisions).map(([id, d]) => [
                id,
                {
                    approve: d.approve ? 1 : 0,
                    note: d.note,
                    ...(d.sample ? { sample: d.sample } : {}),
                },
            ]),
        );

        router.post(review.url(target), { decisions: payload }, options);
    };

    const rejectAll = () =>
        entry?.requirements.forEach((r) => decide(r.id, { approve: false }));

    const pending = Object.keys(decisions).length;

    return (
        <Sheet open={candidate !== null} onOpenChange={handleSheetOpenChange}>
            <SheetContent
                side={isDesktop ? 'right' : 'bottom'}
                className={cn(
                    'gap-0 p-0',
                    isDesktop
                        ? 'sm:max-w-md'
                        : 'max-h-[88svh] rounded-t-3xl pb-[env(safe-area-inset-bottom)]',
                )}
            >
                <SheetHeader className="border-b border-border/60 p-5 pr-12">
                    <SheetTitle>{candidate?.name}</SheetTitle>
                    <SheetDescription>
                        {entry?.status === 'approved'
                            ? `Approved${entry.approved_at ? ` on ${entry.approved_at}` : ''}. Documents prepared by BV.`
                            : reviewable
                              ? 'Approved by BV. Review each document, then approve or reject it.'
                              : 'Documents prepared by BV.'}
                    </SheetDescription>
                </SheetHeader>

                <ul className="flex flex-col gap-3 overflow-y-auto p-5">
                    {entry?.requirements.map((requirement) => {
                        const decision = decisions[requirement.id];
                        const noteError =
                            errors[`decisions.${requirement.id}.note`];

                        return (
                            <li
                                key={requirement.id}
                                className="flex flex-col gap-2 rounded-2xl border border-border bg-card/60 p-2"
                            >
                                <FileLink
                                    requirement={requirement}
                                    onView={() =>
                                        requirement.view_url &&
                                        setPreview({
                                            title: requirement.title,
                                            fileName: requirement.file_name,
                                            viewUrl: requirement.view_url,
                                            downloadUrl:
                                                requirement.download_url,
                                        })
                                    }
                                />
                                <div className="flex items-center justify-between gap-2 px-1">
                                    <span
                                        className={cn(
                                            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                            STATUS_STYLES[
                                                requirement.review_status
                                            ],
                                        )}
                                    >
                                        {
                                            STATUS_LABELS[
                                                requirement.review_status
                                            ]
                                        }
                                    </span>
                                    {reviewable && (
                                        <div className="flex gap-1.5">
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant={
                                                    decision?.approve === true
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                className="h-8 rounded-full text-xs"
                                                onClick={() =>
                                                    decide(requirement.id, {
                                                        approve: true,
                                                    })
                                                }
                                            >
                                                <Check className="size-3.5" />
                                                Approve
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant={
                                                    decision?.approve === false
                                                        ? 'destructive'
                                                        : 'outline'
                                                }
                                                className="h-8 rounded-full text-xs"
                                                onClick={() =>
                                                    decide(requirement.id, {
                                                        approve: false,
                                                    })
                                                }
                                            >
                                                <X className="size-3.5" />
                                                Reject
                                            </Button>
                                        </div>
                                    )}
                                </div>

                                {requirement.review_status === 'rejected' &&
                                    decision === undefined && (
                                        <p className="px-1 text-xs text-destructive">
                                            {requirement.rejection_note}
                                            {requirement.sample_view_url && (
                                                <button
                                                    type="button"
                                                    className="ml-1 underline"
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

                                {decision?.approve === false && (
                                    <div className="flex flex-col gap-2 px-1 pb-1">
                                        <Textarea
                                            value={decision.note}
                                            onChange={(e) =>
                                                decide(requirement.id, {
                                                    note: e.target.value,
                                                })
                                            }
                                            placeholder="What is wrong with this file? BV will fix it."
                                            aria-invalid={Boolean(noteError)}
                                            rows={3}
                                        />
                                        {noteError && (
                                            <p className="text-xs text-destructive">
                                                {noteError}
                                            </p>
                                        )}
                                        <label className="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground">
                                            <Paperclip className="size-3.5" />
                                            <span className="truncate">
                                                {decision.sample?.name ??
                                                    'Attach a sample (optional)'}
                                            </span>
                                            <input
                                                type="file"
                                                className="sr-only"
                                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                                onChange={(e) =>
                                                    decide(requirement.id, {
                                                        sample:
                                                            e.target
                                                                .files?.[0] ??
                                                            null,
                                                    })
                                                }
                                            />
                                        </label>
                                    </div>
                                )}
                            </li>
                        );
                    })}
                </ul>

                {reviewable && target && (
                    <div className="flex flex-col gap-2 border-t border-border/60 p-4">
                        {errors.review && (
                            <p className="text-xs text-destructive">
                                {errors.review}
                            </p>
                        )}
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                className="h-10 flex-1 rounded-full"
                                disabled={busy || pending === 0}
                                onClick={submit}
                            >
                                Submit {pending > 0 ? `(${pending})` : ''}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-10 rounded-full"
                                disabled={busy}
                                onClick={() =>
                                    router.post(
                                        approveAll.url(target),
                                        {},
                                        options,
                                    )
                                }
                            >
                                Approve all
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-10 rounded-full"
                                disabled={busy}
                                onClick={rejectAll}
                            >
                                Reject all
                            </Button>
                        </div>
                    </div>
                )}
            </SheetContent>
            <DocumentPreviewDialog target={preview} onClose={closePreview} />
        </Sheet>
    );
}
