import { useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { AssignmentTargetPicker } from '@app/components/assignments/assignment-target-picker';
import {
    firstServerErrorMessage,
    sectionIndexFromServerErrors,
    validateSections,
    ASSIGNMENT_AUDIO_MAX_BYTES,
} from '@app/components/assignments/assignment-validation';
import {
    WorksheetBuilder,
    sectionsFromAssignment,
    serializeSections,
    serializeSectionsForJson,
} from '@app/components/assignments/question-builder';
import type {
    AssignmentData,
    BatchOption,
    SectionDraft,
    StudentOption,
} from '@app/types/assignments';
import InputError from '@shared/components/input-error';

type Props = {
    action: string;
    method: 'post' | 'put';
    batches: BatchOption[];
    batchStudents: Record<string, StudentOption[]>;
    assignment?: AssignmentData;
    submitLabel: string;
    draftLabel?: string;
    allowSaveDraft?: boolean;
    isDuplicate?: boolean;
};

type StepId = 'details' | 'assign' | 'worksheet';

const STEPS: { id: StepId; label: string }[] = [
    { id: 'details', label: 'Details' },
    { id: 'assign', label: 'Assign to' },
    { id: 'worksheet', label: 'Worksheet' },
];

const STEP_ORDER: StepId[] = ['details', 'assign', 'worksheet'];

const DETAILS_ERROR_KEYS = [
    'title',
    'batch_id',
    'due_at',
    'audio',
    'instructions',
] as const;
const ASSIGN_ERROR_KEYS = ['target_type', 'student_ids'] as const;
const WORKSHEET_ERROR_KEYS = ['sections', 'questions'] as const;

function firstStepWithErrors(errors: Record<string, string>): StepId | null {
    const keys = Object.keys(errors);

    if (
        keys.some(
            (key) =>
                DETAILS_ERROR_KEYS.includes(
                    key as (typeof DETAILS_ERROR_KEYS)[number],
                ) || key.startsWith('audio'),
        )
    ) {
        return 'details';
    }

    if (
        keys.some(
            (key) =>
                ASSIGN_ERROR_KEYS.includes(
                    key as (typeof ASSIGN_ERROR_KEYS)[number],
                ) || key.startsWith('student_ids'),
        )
    ) {
        return 'assign';
    }

    if (
        keys.some(
            (key) =>
                WORKSHEET_ERROR_KEYS.includes(
                    key as (typeof WORKSHEET_ERROR_KEYS)[number],
                ) ||
                key.startsWith('sections') ||
                key.startsWith('questions'),
        )
    ) {
        return 'worksheet';
    }

    return null;
}

function scrollFormToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function nestedWorksheetError(errors: Record<string, string>): string | null {
    for (const [key, message] of Object.entries(errors)) {
        if (key.startsWith('sections') || key.startsWith('questions')) {
            return message;
        }
    }

    return errors.sections ?? errors.questions ?? null;
}

export function AssignmentForm({
    action,
    method,
    batches,
    batchStudents,
    assignment,
    submitLabel,
    draftLabel = 'Save draft',
    allowSaveDraft = false,
    isDuplicate = false,
}: Props) {
    const initialSections = useMemo(
        () => sectionsFromAssignment(assignment),
        [assignment],
    );

    const [step, setStep] = useState<StepId>('details');
    const [titleError, setTitleError] = useState<string | null>(null);
    const [assignError, setAssignError] = useState<string | null>(null);
    const [worksheetError, setWorksheetError] = useState<string | null>(null);
    const [focusSectionIndex, setFocusSectionIndex] = useState<number | null>(
        null,
    );
    const [focusQuestionIndex, setFocusQuestionIndex] = useState<number | null>(
        null,
    );
    const [focusKey, setFocusKey] = useState(0);
    const [sections, setSections] = useState<SectionDraft[]>(initialSections);
    const [targetType, setTargetType] = useState<'batch' | 'student'>(
        assignment?.target_type ?? 'batch',
    );
    const [studentIds, setStudentIds] = useState<number[]>(
        assignment?.student_ids ?? [],
    );
    const [batchId, setBatchId] = useState<number>(
        isDuplicate ? 0 : (assignment?.batch_id ?? batches[0]?.id ?? 0),
    );
    const [removeAudio, setRemoveAudio] = useState(false);
    const handledErrorSignature = useRef<string>('');

    const form = useForm({
        batch_id: batchId,
        title: assignment?.title ?? '',
        instructions: assignment?.instructions ?? '',
        due_at: assignment?.due_at ? assignment.due_at.slice(0, 16) : '',
        target_type: targetType,
        student_ids: studentIds,
        sections: serializeSections(initialSections),
        audio: null as File | null,
        remove_audio: false,
    });

    const students = batchStudents[String(batchId)] ?? [];
    const stepIndex = STEPS.findIndex((item) => item.id === step);
    const isFirstStep = stepIndex === 0;
    const isLastStep = stepIndex === STEPS.length - 1;

    const jumpToStep = (next: StepId) => {
        setStep(next);
        scrollFormToTop();
    };

    const validateDetails = (): boolean => {
        if ((isDuplicate || !assignment) && (!batchId || batchId <= 0)) {
            const message = 'Select a batch.';
            setTitleError(null);
            toast.error(message);
            jumpToStep('details');

            return false;
        }

        if (!form.data.title.trim()) {
            const message = 'Title is required.';
            setTitleError(message);
            toast.error(message);
            jumpToStep('details');

            return false;
        }

        setTitleError(null);

        return true;
    };

    const validateAssign = (): boolean => {
        if (targetType === 'student' && studentIds.length === 0) {
            const message = 'Select at least one student.';
            setAssignError(message);
            toast.error(message);
            jumpToStep('assign');

            return false;
        }

        setAssignError(null);

        return true;
    };

    const validateWorksheet = (): boolean => {
        const issue = validateSections(sections);

        if (issue) {
            setWorksheetError(issue.message);
            setFocusSectionIndex(issue.sectionIndex);
            setFocusQuestionIndex(issue.questionIndex);
            setFocusKey((key) => key + 1);
            toast.error(issue.message);
            jumpToStep('worksheet');

            return false;
        }

        setWorksheetError(null);
        setFocusSectionIndex(null);
        setFocusQuestionIndex(null);

        return true;
    };

    useEffect(() => {
        const keys = Object.keys(form.errors);

        if (keys.length === 0) {
            handledErrorSignature.current = '';

            return;
        }

        const signature = keys
            .map((key) => `${key}:${form.errors[key]}`)
            .join('|');

        if (signature === handledErrorSignature.current) {
            return;
        }

        handledErrorSignature.current = signature;

        const next = firstStepWithErrors(form.errors);

        if (next) {
            jumpToStep(next);
        }

        const message =
            firstServerErrorMessage(form.errors) ??
            'Please fix the highlighted errors.';
        toast.error(message);

        if (next === 'details' && form.errors.title) {
            setTitleError(form.errors.title);
        }

        if (next === 'assign') {
            setAssignError(
                form.errors.student_ids ?? form.errors.target_type ?? message,
            );
        }

        if (next === 'worksheet') {
            setWorksheetError(nestedWorksheetError(form.errors) ?? message);
            setFocusSectionIndex(sectionIndexFromServerErrors(form.errors));
            setFocusQuestionIndex(0);
            setFocusKey((key) => key + 1);
        }
    }, [form.errors]);

    const goToStep = (next: StepId) => {
        const currentIndex = STEP_ORDER.indexOf(step);
        const nextIndex = STEP_ORDER.indexOf(next);

        if (nextIndex <= currentIndex) {
            setStep(next);

            return;
        }

        if (nextIndex >= 1 && !validateDetails()) {
            return;
        }

        if (nextIndex >= 2 && !validateAssign()) {
            return;
        }

        setStep(next);
    };

    const goNext = () => {
        if (step === 'details') {
            if (!validateDetails()) {
                return;
            }

            setStep('assign');

            return;
        }

        if (step === 'assign') {
            if (!validateAssign()) {
                return;
            }

            setStep('worksheet');
        }
    };

    const goBack = () => {
        if (step === 'assign') {
            setStep('details');
        } else if (step === 'worksheet') {
            setStep('assign');
        }
    };

    const submitAssignment = (publish: boolean) => {
        if (!validateDetails() || !validateAssign() || !validateWorksheet()) {
            return;
        }

        if (
            form.data.audio !== null &&
            form.data.audio.size > ASSIGNMENT_AUDIO_MAX_BYTES
        ) {
            toast.error('Audio must be 20MB or smaller.');
            jumpToStep('details');

            return;
        }

        const hasQuestionMedia = sections.some((section) =>
            section.questions.some((question) => question.mediaFile !== null),
        );
        const useMultipart =
            form.data.audio !== null || hasQuestionMedia || removeAudio;

        form.transform((data) => {
            const payload: Record<string, unknown> = {
                ...data,
                batch_id: batchId,
                target_type: targetType,
                student_ids: studentIds,
                remove_audio: removeAudio,
                publish: publish ? 1 : 0,
                _method: method === 'put' ? 'put' : undefined,
            };

            if (useMultipart) {
                payload.sections_json = JSON.stringify(
                    serializeSectionsForJson(sections),
                );
                payload.sections = sections.map((section) => ({
                    questions: section.questions.map((question) =>
                        question.mediaFile
                            ? { media: question.mediaFile }
                            : {},
                    ),
                }));
            } else {
                payload.sections = serializeSections(sections);
            }

            return payload;
        });

        form.post(action, {
            forceFormData: useMultipart,
            preserveScroll: true,
            onError: (errors) => {
                const message =
                    firstServerErrorMessage(errors) ??
                    'This upload is too large or invalid. Please use a smaller file and try again.';
                toast.error(message);
            },
            onFinish: () => {
                form.transform((data) => data);
            },
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!isLastStep) {
            goNext();

            return;
        }

        submitAssignment(true);
    };

    return (
        <form onSubmit={submit} noValidate className="grid gap-6">
            <div
                className="flex flex-wrap gap-1 rounded-lg bg-muted p-1"
                role="tablist"
                aria-label="Assignment steps"
            >
                {STEPS.map((item, index) => {
                    const active = item.id === step;

                    return (
                        <button
                            key={item.id}
                            type="button"
                            role="tab"
                            aria-selected={active}
                            onClick={() => goToStep(item.id)}
                            className={cn(
                                'flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm transition-colors',
                                active
                                    ? 'bg-background text-foreground shadow-xs'
                                    : 'text-muted-foreground hover:bg-background/60 hover:text-foreground',
                            )}
                        >
                            <span
                                className={cn(
                                    'flex size-5 items-center justify-center rounded-full text-xs font-medium',
                                    active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted-foreground/20',
                                )}
                            >
                                {index + 1}
                            </span>
                            <span className="font-medium">{item.label}</span>
                        </button>
                    );
                })}
            </div>

            {step === 'details' && (
                <div className="grid gap-6" role="tabpanel">
                    {(!assignment || isDuplicate) && (
                        <div className="grid gap-2">
                            <Label htmlFor="batch_id">Batch</Label>
                            <select
                                id="batch_id"
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                value={batchId || ''}
                                onChange={(e) =>
                                    setBatchId(Number(e.target.value))
                                }
                            >
                                {isDuplicate && (
                                    <option value="">Select a batch</option>
                                )}
                                {batches.map((batch) => (
                                    <option key={batch.id} value={batch.id}>
                                        {batch.name} ({batch.language}{' '}
                                        {batch.level})
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.batch_id} />
                        </div>
                    )}

                    <div className="grid gap-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(e) => {
                                setTitleError(null);
                                form.setData('title', e.target.value);
                            }}
                        />
                        <InputError
                            message={titleError ?? form.errors.title}
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="instructions">Instructions</Label>
                        <Textarea
                            id="instructions"
                            value={form.data.instructions}
                            onChange={(e) =>
                                form.setData('instructions', e.target.value)
                            }
                            rows={3}
                        />
                        <InputError message={form.errors.instructions} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="due_at">Due date</Label>
                        <Input
                            id="due_at"
                            type="datetime-local"
                            value={form.data.due_at}
                            onChange={(e) =>
                                form.setData('due_at', e.target.value)
                            }
                        />
                        <InputError message={form.errors.due_at} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="audio">Audio (optional)</Label>
                        {assignment?.has_audio && !removeAudio && (
                            <p className="text-sm text-muted-foreground">
                                Current: {assignment.audio_original_name}
                                <Button
                                    type="button"
                                    variant="link"
                                    className="ml-2 h-auto p-0"
                                    onClick={() => setRemoveAudio(true)}
                                >
                                    Remove
                                </Button>
                            </p>
                        )}
                        <Input
                            id="audio"
                            type="file"
                            accept="audio/*"
                            onChange={(e) => {
                                const file = e.target.files?.[0] ?? null;

                                if (
                                    file !== null &&
                                    file.size > ASSIGNMENT_AUDIO_MAX_BYTES
                                ) {
                                    toast.error('Audio must be 20MB or smaller.');
                                    e.target.value = '';
                                    form.setData('audio', null);

                                    return;
                                }

                                form.setData('audio', file);
                            }}
                        />
                        <InputError message={form.errors.audio} />
                    </div>
                </div>
            )}

            {step === 'assign' && (
                <div role="tabpanel" className="grid gap-3">
                    <AssignmentTargetPicker
                        targetType={targetType}
                        studentIds={studentIds}
                        students={students}
                        onTargetTypeChange={(type) => {
                            setAssignError(null);
                            setTargetType(type);
                        }}
                        onStudentIdsChange={(ids) => {
                            setAssignError(null);
                            setStudentIds(ids);
                        }}
                    />
                    <InputError
                        message={
                            assignError ??
                            form.errors.target_type ??
                            form.errors.student_ids
                        }
                    />
                </div>
            )}

            {step === 'worksheet' && (
                <div className="grid gap-3" role="tabpanel">
                    <p className="text-sm text-muted-foreground">
                        Complete each item before adding another. Double-click a
                        tab to rename it.
                    </p>
                    <WorksheetBuilder
                        value={sections}
                        onChange={(next) => {
                            setWorksheetError(null);
                            setSections(next);
                        }}
                        focusSectionIndex={focusSectionIndex}
                        focusQuestionIndex={focusQuestionIndex}
                        focusKey={focusKey}
                    />
                    <InputError
                        message={
                            worksheetError ??
                            nestedWorksheetError(form.errors) ??
                            form.errors.sections
                        }
                    />
                </div>
            )}

            <div className="flex flex-wrap items-center gap-3 border-t border-border/70 pt-4">
                {!isFirstStep && (
                    <Button type="button" variant="outline" onClick={goBack}>
                        Back
                    </Button>
                )}

                <div className="ml-auto flex flex-wrap gap-3">
                    {!isLastStep && (
                        <Button type="button" onClick={goNext}>
                            Continue
                        </Button>
                    )}

                    {isLastStep && (
                        <>
                            {allowSaveDraft && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={form.processing}
                                    onClick={() => submitAssignment(false)}
                                >
                                    {form.processing && (
                                        <Spinner className="mr-2" />
                                    )}
                                    {draftLabel}
                                </Button>
                            )}
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && (
                                    <Spinner className="mr-2" />
                                )}
                                {submitLabel}
                            </Button>
                        </>
                    )}
                </div>
            </div>
        </form>
    );
}
