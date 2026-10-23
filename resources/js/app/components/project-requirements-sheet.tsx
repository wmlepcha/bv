import { router } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/responsive-sheet';
import { useMediaQuery } from '@/hooks/use-media-query';
import { cn } from '@/lib/utils';
import { finalize } from '@/routes/recruiter/projects';
import { update as updateRequirements } from '@/routes/recruiter/projects/requirements';
import type {
    CandidateCard as CandidateCardData,
    ProjectSummary,
} from '@app/types/recruiter';

const SUGGESTIONS = [
    'Passport copy',
    'Degree certificate',
    'Nursing licence',
    'Experience letter',
    'German language certificate',
    'Police clearance',
    'Medical fitness certificate',
];

function ChipInput({
    onAdd,
    suggestions,
    placeholder,
}: {
    onAdd: (title: string) => void;
    suggestions: string[];
    placeholder: string;
}) {
    const [value, setValue] = useState('');

    const submit = () => {
        if (value.trim()) {
            onAdd(value.trim());
            setValue('');
        }
    };

    return (
        <div className="flex flex-col gap-2">
            <div className="flex gap-2">
                <input
                    value={value}
                    onChange={(event) => setValue(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            submit();
                        }
                    }}
                    placeholder={placeholder}
                    maxLength={255}
                    className="h-11 min-w-0 flex-1 rounded-full border border-border bg-card px-4 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <Button
                    type="button"
                    size="icon"
                    className="size-11 rounded-full"
                    onClick={submit}
                    aria-label="Add requirement"
                >
                    <Plus className="size-5" />
                </Button>
            </div>
            {suggestions.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {suggestions.map((suggestion) => (
                        <button
                            key={suggestion}
                            type="button"
                            onClick={() => onAdd(suggestion)}
                            className="rounded-full border border-dashed border-border px-3 py-1.5 text-xs text-muted-foreground transition-transform active:scale-95"
                        >
                            + {suggestion}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function ProjectRequirementsSheet({
    project,
    candidates,
    open,
    onOpenChange,
}: {
    project: ProjectSummary;
    candidates: CandidateCardData[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const isDesktop = useMediaQuery('(min-width: 768px)');
    const editable = candidates.filter(
        (candidate) => candidate.entry?.status !== 'approved',
    );
    const initialTemplate = project.requirement_template;
    const [template, setTemplate] = useState<string[]>(initialTemplate);
    const [perStudent, setPerStudent] = useState<Record<number, string[]>>(() =>
        Object.fromEntries(
            editable.map((candidate) => [
                candidate.id,
                candidate.entry && candidate.entry.requirements.length > 0
                    ? candidate.entry.requirements.map((r) => r.title)
                    : initialTemplate,
            ]),
        ),
    );
    const [processing, setProcessing] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);

    const has = (list: string[], title: string) =>
        list.some((item) => item.toLowerCase() === title.toLowerCase());

    const addToTemplate = (title: string) => {
        if (has(template, title)) {
            return;
        }

        setTemplate((current) => [...current, title]);
        setPerStudent((current) =>
            Object.fromEntries(
                Object.entries(current).map(([id, list]) => [
                    id,
                    has(list, title) ? list : [...list, title],
                ]),
            ),
        );
    };

    const removeFromTemplate = (title: string) => {
        setTemplate((current) => current.filter((item) => item !== title));
        setPerStudent((current) =>
            Object.fromEntries(
                Object.entries(current).map(([id, list]) => [
                    id,
                    list.filter((item) => item !== title),
                ]),
            ),
        );
    };

    const toggleForStudent = (id: number, title: string) =>
        setPerStudent((current) => {
            const list = current[id] ?? [];

            return {
                ...current,
                [id]: has(list, title)
                    ? list.filter(
                          (item) => item.toLowerCase() !== title.toLowerCase(),
                      )
                    : [...list, title],
            };
        });

    const submit = () => {
        const payload = { requirements: template, students: perStudent };
        const options = {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onSuccess: () => onOpenChange(false),
        };

        if (project.finalized) {
            router.put(updateRequirements.url(project.id), payload, options);
        } else {
            router.post(finalize.url(project.id), payload, options);
        }
    };

    const canSubmit = template.length > 0 && candidates.length > 0;

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side={isDesktop ? 'right' : 'bottom'}
                className={cn(
                    'gap-0 p-0',
                    isDesktop
                        ? 'sm:max-w-lg'
                        : 'h-[92svh] rounded-t-3xl pb-[env(safe-area-inset-bottom)]',
                )}
            >
                <SheetHeader className="border-b border-border/60 p-5 pr-12">
                    <SheetTitle>
                        {project.finalized
                            ? 'Edit requirements'
                            : 'Finalize list'}
                    </SheetTitle>
                    <SheetDescription>
                        {project.finalized
                            ? 'BV is notified when you change the list.'
                            : 'Tell BV what each student needs. The list is sent to BV, who prepare the documents.'}
                    </SheetDescription>
                </SheetHeader>

                <div className="flex flex-1 flex-col gap-7 overflow-y-auto p-5">
                    <section className="flex flex-col gap-3">
                        <h3 className="text-sm font-semibold">
                            Requirements for everyone
                        </h3>
                        <div className="flex flex-wrap gap-2">
                            {template.map((title) => (
                                <span
                                    key={title}
                                    className="inline-flex items-center gap-1.5 rounded-full bg-primary/10 py-1.5 pr-1.5 pl-3 text-sm font-medium text-primary"
                                >
                                    {title}
                                    <button
                                        type="button"
                                        onClick={() =>
                                            removeFromTemplate(title)
                                        }
                                        aria-label={`Remove ${title}`}
                                        className="flex size-5 items-center justify-center rounded-full active:bg-primary/20"
                                    >
                                        <X className="size-3.5" />
                                    </button>
                                </span>
                            ))}
                            {template.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    Nothing added yet.
                                </p>
                            )}
                        </div>
                        <ChipInput
                            onAdd={addToTemplate}
                            suggestions={SUGGESTIONS.filter(
                                (suggestion) => !has(template, suggestion),
                            )}
                            placeholder="e.g. Passport copy"
                        />
                    </section>

                    {editable.length > 0 && (
                        <section className="flex flex-col gap-3">
                            <h3 className="text-sm font-semibold">
                                Per student
                            </h3>
                            <p className="-mt-2 text-xs text-muted-foreground">
                                Untick what a student doesn't need, or add
                                something extra just for them.
                            </p>
                            {editable.map((candidate) => {
                                const list = perStudent[candidate.id] ?? [];
                                const options = Array.from(
                                    new Set([...template, ...list]),
                                );

                                return (
                                    <details
                                        key={candidate.id}
                                        className="group rounded-2xl border border-border bg-card/60"
                                    >
                                        <summary className="flex cursor-pointer list-none items-center justify-between gap-3 p-4 text-sm font-medium">
                                            <span className="truncate">
                                                {candidate.name}
                                            </span>
                                            <span className="shrink-0 rounded-full bg-muted px-2.5 py-0.5 text-xs text-muted-foreground">
                                                {list.length} required
                                            </span>
                                        </summary>
                                        <div className="flex flex-col gap-3 border-t border-border/60 p-4">
                                            {options.map((title) => (
                                                <label
                                                    key={title}
                                                    className="flex items-center gap-3 text-sm"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={has(
                                                            list,
                                                            title,
                                                        )}
                                                        onChange={() =>
                                                            toggleForStudent(
                                                                candidate.id,
                                                                title,
                                                            )
                                                        }
                                                        className="size-5 rounded accent-primary"
                                                    />
                                                    {title}
                                                </label>
                                            ))}
                                            <ChipInput
                                                onAdd={(title) =>
                                                    setPerStudent(
                                                        (current) => ({
                                                            ...current,
                                                            [candidate.id]: has(
                                                                current[
                                                                    candidate.id
                                                                ] ?? [],
                                                                title,
                                                            )
                                                                ? current[
                                                                      candidate
                                                                          .id
                                                                  ]
                                                                : [
                                                                      ...(current[
                                                                          candidate
                                                                              .id
                                                                      ] ?? []),
                                                                      title,
                                                                  ],
                                                        }),
                                                    )
                                                }
                                                suggestions={[]}
                                                placeholder="Extra requirement for this student"
                                            />
                                        </div>
                                    </details>
                                );
                            })}
                        </section>
                    )}
                </div>

                <SheetFooter className="border-t border-border/60 p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]">
                    <Button
                        type="button"
                        className="h-12 w-full rounded-full text-base"
                        disabled={!canSubmit || processing}
                        onClick={() =>
                            project.finalized ? submit() : setConfirmOpen(true)
                        }
                    >
                        {project.finalized ? 'Save changes' : 'Send to BV'}
                    </Button>
                </SheetFooter>
            </SheetContent>

            <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <DialogContent className="max-w-[calc(100%-2rem)] rounded-3xl sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Finalize this list?</DialogTitle>
                        <DialogDescription>
                            You are about to send{' '}
                            <strong>
                                {candidates.length}{' '}
                                {candidates.length === 1
                                    ? 'candidate'
                                    : 'candidates'}
                            </strong>{' '}
                            to BV with {template.length}{' '}
                            {template.length === 1
                                ? 'requirement'
                                : 'requirements'}
                            . BV will start preparing the documents. You can
                            still make changes until a candidate is approved.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="flex-row gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            className="h-11 flex-1 rounded-full"
                            onClick={() => setConfirmOpen(false)}
                        >
                            No
                        </Button>
                        <Button
                            type="button"
                            className="h-11 flex-1 rounded-full"
                            disabled={processing}
                            onClick={() => {
                                setConfirmOpen(false);
                                submit();
                            }}
                        >
                            Yes, finalize
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </Sheet>
    );
}
