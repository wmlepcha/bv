import {
    DndContext,
    closestCenter,
    
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors
} from '@dnd-kit/core';
import type {DragEndEvent} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    ChevronLeft,
    ChevronRight,
    GripVertical,
    Plus,
    Trash2,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import {
    countBlanks,
    mediaMaxBytesForType,
    mediaSizeErrorForType,
    normalizeBlankTemplate,
    validateQuestion,
    validateSection,
} from '@app/components/assignments/assignment-validation';
import {
    GermanCharToolbar,
    insertAtCursor,
} from '@app/components/assignments/german-char-toolbar';
import type {
    AssignmentData,
    QuestionDraft,
    SectionDraft,
} from '@app/types/assignments';
import { isMediaFileType, isMediaItemType } from '@app/types/assignments';

export { countBlanks, normalizeBlankTemplate } from '@app/components/assignments/assignment-validation';

function flattenModelAnswers(raw?: string[][] | null): string[] {
    if (!raw || raw.length === 0) {
        return [];
    }

    return raw.map((group) =>
        Array.isArray(group) ? String(group[0] ?? '') : String(group),
    );
}

function syncModelAnswers(template: string, answers: string[]): string[] {
    const blankCount = Math.max(countBlanks(template), 1);

    return Array.from({ length: blankCount }, (_, index) => answers[index] ?? '');
}

export function newQuestion(
    partial: Partial<QuestionDraft> = {},
): QuestionDraft {
    return {
        clientId: crypto.randomUUID(),
        type: 'fill_in_blank',
        prompt: '',
        points: 1,
        options: ['', ''],
        correct_index: 0,
        template: '',
        hints: [''],
        tokens: [],
        model_sentence: '',
        model_answers: [''],
        mediaFile: null,
        has_media: false,
        media_original_name: '',
        existing_media_path: '',
        media_url: '',
        ...partial,
    };
}

function newSection(label = 'A'): SectionDraft {
    return {
        clientId: crypto.randomUUID(),
        label,
        title: '',
        instructions: '',
        questions: [newQuestion()],
    };
}

function isPlaceholderQuestion(question: QuestionDraft): boolean {
    if (question.type === 'fill_in_blank') {
        return !question.template.trim();
    }

    if (question.type === 'word_reorder') {
        return question.tokens.filter(Boolean).length === 0;
    }

    if (question.type === 'mcq') {
        return !question.prompt.trim();
    }

    if (question.type === 'link') {
        return !question.prompt.trim() || !question.media_url.trim();
    }

    if (isMediaFileType(question.type)) {
        return (
            !question.prompt.trim() ||
            (!question.mediaFile &&
                !question.has_media &&
                !question.existing_media_path)
        );
    }

    return !question.prompt.trim();
}

export function parseFillInBlankLine(line: string): QuestionDraft | null {
    const trimmed = line.trim().replace(/^\d+[\).\s]+/, '');

    if (!trimmed) {
        return null;
    }

    const hintMatch = trimmed.match(/\(([^)]+)\)\s*$/);
    let template = trimmed;
    let hint = '';

    if (hintMatch) {
        hint = hintMatch[1].trim();
        template = trimmed.slice(0, hintMatch.index).trim();
    }

    template = normalizeBlankTemplate(template);

    if (!template.includes('___')) {
        return null;
    }

    return newQuestion({
        type: 'fill_in_blank',
        template,
        prompt: template,
        hints: hint ? [hint] : [''],
        model_answers: syncModelAnswers(template, []),
    });
}

export function parseWordReorderLine(line: string): QuestionDraft | null {
    const trimmed = line.trim().replace(/^\d+[\).\s]+/, '');

    if (!trimmed) {
        return null;
    }

    const [tokenPart, ...modelParts] = trimmed.split('|');
    const tokens = tokenPart
        .split('/')
        .map((token) => token.trim())
        .filter(Boolean);

    if (tokens.length < 2) {
        return null;
    }

    return newQuestion({
        type: 'word_reorder',
        tokens,
        prompt: tokens.join(' / '),
        model_sentence: modelParts.join('|').trim(),
        template: '',
        hints: [''],
        model_answers: [],
    });
}

function mergeBulkQuestions(
    existing: QuestionDraft[],
    incoming: QuestionDraft[],
): QuestionDraft[] {
    if (incoming.length === 0) {
        return existing;
    }

    if (existing.length === 1 && isPlaceholderQuestion(existing[0])) {
        return incoming;
    }

    return [...existing, ...incoming];
}

function tokensFromWordsInput(value: string): string[] {
    return value
        .split('/')
        .map((token) => token.trim())
        .filter(Boolean);
}

function WordReorderWordsInput({
    tokens,
    onTokensChange,
}: {
    tokens: string[];
    onTokensChange: (tokens: string[]) => void;
}) {
    const [draft, setDraft] = useState(() => tokens.join(' / '));
    const [focused, setFocused] = useState(false);
    const tokensKey = tokens.join('\0');

    useEffect(() => {
        if (!focused) {
            setDraft(tokens.join(' / '));
        }
    }, [focused, tokensKey, tokens]);

    return (
        <Input
            value={focused ? draft : tokens.join(' / ')}
            onFocus={() => {
                setFocused(true);
                setDraft(tokens.join(' / '));
            }}
            onChange={(e) => {
                const next = e.target.value;
                setDraft(next);
                onTokensChange(tokensFromWordsInput(next));
            }}
            onBlur={() => {
                const nextTokens = tokensFromWordsInput(draft);
                onTokensChange(nextTokens);
                setDraft(nextTokens.join(' / '));
                setFocused(false);
            }}
            placeholder="gehe / ich / in / den / Supermarkt"
        />
    );
}

function SortableQuestion({
    question,
    index,
    onChange,
    onRemove,
    canRemove,
    error,
}: {
    question: QuestionDraft;
    index: number;
    onChange: (next: QuestionDraft) => void;
    onRemove: () => void;
    canRemove: boolean;
    error?: string | null;
}) {
    const fieldRef = useRef<HTMLTextAreaElement>(null);
    const { attributes, listeners, setNodeRef, transform, transition } =
        useSortable({ id: question.clientId });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    const blankCount = countBlanks(question.template);

    const insertChar = (char: string, field: 'prompt' | 'template', value: string) => {
        const el = fieldRef.current;

        if (!el) {
            return;
        }

        const { value: next, cursor } = insertAtCursor(
            value,
            char,
            el.selectionStart,
            el.selectionEnd,
        );
        const updated =
            field === 'template'
                ? {
                      ...question,
                      template: next,
                      prompt: next,
                      model_answers: syncModelAnswers(next, question.model_answers),
                  }
                : { ...question, [field]: next };

        onChange(updated);
        requestAnimationFrame(() => {
            el.focus();
            el.setSelectionRange(cursor, cursor);
        });
    };

    const applyTypeDefaults = (type: QuestionDraft['type']) => {
        const base = {
            ...question,
            type,
            mediaFile: isMediaFileType(type) ? question.mediaFile : null,
            has_media: isMediaFileType(type) ? question.has_media : false,
            media_original_name: isMediaFileType(type)
                ? question.media_original_name
                : '',
            existing_media_path: isMediaFileType(type)
                ? question.existing_media_path
                : '',
            media_url: type === 'link' ? question.media_url : '',
        };

        if (type === 'fill_in_blank' && !base.template) {
            base.template = base.prompt;
            base.model_answers = syncModelAnswers(base.template, base.model_answers);
        }

        if (type === 'word_reorder' && base.tokens.length === 0) {
            base.tokens = [''];
        }

        onChange(base);
    };

    const updateTemplate = (raw: string) => {
        const template = normalizeBlankTemplate(raw);
        onChange({
            ...question,
            template,
            prompt: template,
            model_answers: syncModelAnswers(template, question.model_answers),
        });
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            data-question-id={question.clientId}
            className={cn(
                'rounded-lg border bg-background p-3',
                error ? 'border-destructive' : 'border-border/60',
            )}
        >
            <div className="mb-3 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    className="cursor-grab text-muted-foreground"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="size-4" />
                </button>
                <span className="w-6 text-sm font-medium tabular-nums">
                    {index + 1}.
                </span>
                <select
                    className="h-9 min-w-[10rem] flex-1 rounded-md border border-input bg-background px-2 text-sm sm:flex-none"
                    value={question.type}
                    onChange={(e) =>
                        applyTypeDefaults(e.target.value as QuestionDraft['type'])
                    }
                    aria-label={`Item ${index + 1} type`}
                >
                    <option value="fill_in_blank">Fill in the blank</option>
                    <option value="word_reorder">Word reorder</option>
                    <option value="short_text">Short text</option>
                    <option value="mcq">Multiple choice</option>
                    <option value="pdf">PDF</option>
                    <option value="image">Image</option>
                    <option value="audio">Audio</option>
                    <option value="link">Link</option>
                </select>
                <div className="flex min-w-[9.5rem] flex-col gap-0.5">
                    <div className="flex items-center gap-1.5">
                        <Label
                            htmlFor={`points-${question.clientId}`}
                            className="text-sm font-medium"
                        >
                            Points
                        </Label>
                        <Input
                            id={`points-${question.clientId}`}
                            type="number"
                            min={1}
                            max={100}
                            className="h-9 w-20"
                            value={question.points}
                            onChange={(e) =>
                                onChange({
                                    ...question,
                                    points: Number(e.target.value) || 1,
                                })
                            }
                            aria-label={`Points for item ${index + 1}`}
                            title="Worth this many points when graded."
                        />
                    </div>
                    <p className="text-[11px] leading-tight text-muted-foreground">
                        Worth this many points when graded.
                    </p>
                </div>
                {canRemove && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="ml-auto"
                        onClick={onRemove}
                    >
                        <Trash2 className="size-4" />
                    </Button>
                )}
            </div>

            <div className="grid gap-3">
                {question.type === 'fill_in_blank' && (
                    <>
                        <div className="grid gap-2">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <Label>Sentence (use ___ for each blank)</Label>
                                <span className="text-xs text-muted-foreground">
                                    {blankCount > 0
                                        ? `${blankCount} blank${blankCount === 1 ? '' : 's'} detected`
                                        : 'Add ___ for blanks'}
                                </span>
                            </div>
                            <GermanCharToolbar
                                onInsert={(char) =>
                                    insertChar(char, 'template', question.template)
                                }
                            />
                            <Textarea
                                ref={fieldRef}
                                value={question.template}
                                onChange={(e) => updateTemplate(e.target.value)}
                                rows={2}
                                placeholder="Ich ___ jeden Tag Deutsch.  or  Er ___ … ___ . (einkaufen)"
                            />
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Verb hint (optional)</Label>
                                <Input
                                    value={question.hints[0] ?? ''}
                                    onChange={(e) =>
                                        onChange({
                                            ...question,
                                            hints: [e.target.value],
                                        })
                                    }
                                    placeholder="lernen"
                                />
                            </div>
                            {blankCount <= 1 ? (
                                <div className="grid gap-2">
                                    <Label>Correct answer (optional)</Label>
                                    <Input
                                        value={question.model_answers[0] ?? ''}
                                        onChange={(e) =>
                                            onChange({
                                                ...question,
                                                model_answers: [e.target.value],
                                            })
                                        }
                                        placeholder="lerne"
                                    />
                                </div>
                            ) : (
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label>
                                        Correct answers (optional, one per blank)
                                    </Label>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {Array.from({ length: blankCount }).map(
                                            (_, blankIndex) => (
                                                <Input
                                                    key={blankIndex}
                                                    value={
                                                        question.model_answers[
                                                            blankIndex
                                                        ] ?? ''
                                                    }
                                                    onChange={(e) => {
                                                        const next =
                                                            syncModelAnswers(
                                                                question.template,
                                                                question.model_answers,
                                                            );
                                                        next[blankIndex] =
                                                            e.target.value;
                                                        onChange({
                                                            ...question,
                                                            model_answers: next,
                                                        });
                                                    }}
                                                    placeholder={`Blank ${blankIndex + 1}`}
                                                />
                                            ),
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </>
                )}

                {question.type === 'word_reorder' && (
                    <>
                        <div className="grid gap-2">
                            <Label>Words (separated by /)</Label>
                            <WordReorderWordsInput
                                tokens={question.tokens}
                                onTokensChange={(tokens) =>
                                    onChange({
                                        ...question,
                                        tokens,
                                        prompt: tokens.join(' / '),
                                    })
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label>Model sentence (optional)</Label>
                            <Input
                                value={question.model_sentence}
                                onChange={(e) =>
                                    onChange({
                                        ...question,
                                        model_sentence: e.target.value,
                                    })
                                }
                                placeholder="Ich gehe gern in den Supermarkt."
                            />
                        </div>
                    </>
                )}

                {(question.type === 'short_text' || question.type === 'mcq') && (
                    <div className="grid gap-2">
                        <Label>Prompt</Label>
                        <GermanCharToolbar
                            onInsert={(char) =>
                                insertChar(char, 'prompt', question.prompt)
                            }
                        />
                        <Textarea
                            ref={fieldRef}
                            value={question.prompt}
                            onChange={(e) =>
                                onChange({ ...question, prompt: e.target.value })
                            }
                            rows={2}
                        />
                    </div>
                )}

                {isMediaItemType(question.type) && (
                    <div className="grid gap-2">
                        <Label>Instructions</Label>
                        <GermanCharToolbar
                            onInsert={(char) =>
                                insertChar(char, 'prompt', question.prompt)
                            }
                        />
                        <Textarea
                            ref={fieldRef}
                            value={question.prompt}
                            onChange={(e) =>
                                onChange({ ...question, prompt: e.target.value })
                            }
                            rows={2}
                            placeholder="What should students do with this resource?"
                        />
                    </div>
                )}

                {question.type === 'link' && (
                    <div className="grid gap-2">
                        <Label htmlFor={`url-${question.clientId}`}>Link URL</Label>
                        <Input
                            id={`url-${question.clientId}`}
                            type="url"
                            value={question.media_url}
                            onChange={(e) =>
                                onChange({
                                    ...question,
                                    media_url: e.target.value,
                                })
                            }
                            placeholder="https://"
                        />
                    </div>
                )}

                {isMediaFileType(question.type) && (
                    <div className="grid gap-2">
                        <Label htmlFor={`media-${question.clientId}`}>
                            {question.type === 'pdf'
                                ? 'PDF file'
                                : question.type === 'image'
                                  ? 'Image file'
                                  : 'Audio file'}
                        </Label>
                        {(question.has_media || question.media_original_name) &&
                            !question.mediaFile && (
                                <p className="text-sm text-muted-foreground">
                                    Current:{' '}
                                    {question.media_original_name || 'Uploaded file'}
                                </p>
                            )}
                        {question.mediaFile && (
                            <p className="text-sm text-muted-foreground">
                                Selected: {question.mediaFile.name}
                            </p>
                        )}
                        <Input
                            id={`media-${question.clientId}`}
                            type="file"
                            accept={
                                question.type === 'pdf'
                                    ? '.pdf,application/pdf'
                                    : question.type === 'image'
                                      ? 'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp'
                                      : 'audio/*,.mp3,.wav,.m4a,.ogg'
                            }
                            onChange={(e) => {
                                const file = e.target.files?.[0] ?? null;

                                if (
                                    file !== null &&
                                    file.size > mediaMaxBytesForType(question.type)
                                ) {
                                    toast.error(
                                        mediaSizeErrorForType(question.type),
                                    );
                                    e.target.value = '';

                                    return;
                                }

                                onChange({
                                    ...question,
                                    mediaFile: file,
                                    media_original_name:
                                        file?.name ??
                                        question.media_original_name,
                                });
                            }}
                        />
                    </div>
                )}

                {question.type === 'mcq' && (
                    <div className="grid gap-2">
                        <Label>Options</Label>
                        {question.options.map((option, optionIndex) => (
                            <div key={optionIndex} className="flex items-center gap-2">
                                <input
                                    type="radio"
                                    name={`correct-${question.clientId}`}
                                    checked={question.correct_index === optionIndex}
                                    onChange={() =>
                                        onChange({
                                            ...question,
                                            correct_index: optionIndex,
                                        })
                                    }
                                    aria-label={`Mark option ${optionIndex + 1} as correct`}
                                />
                                <Input
                                    value={option}
                                    onChange={(e) => {
                                        const options = [...question.options];
                                        options[optionIndex] = e.target.value;
                                        onChange({ ...question, options });
                                    }}
                                    aria-label={`Option ${optionIndex + 1}`}
                                />
                                {question.options.length > 2 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={`Remove option ${optionIndex + 1}`}
                                        onClick={() => {
                                            const options = question.options.filter(
                                                (_, i) => i !== optionIndex,
                                            );
                                            let correctIndex = question.correct_index;

                                            if (optionIndex === correctIndex) {
                                                correctIndex = 0;
                                            } else if (optionIndex < correctIndex) {
                                                correctIndex -= 1;
                                            }

                                            onChange({
                                                ...question,
                                                options,
                                                correct_index: correctIndex,
                                            });
                                        }}
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                onChange({
                                    ...question,
                                    options: [...question.options, ''],
                                })
                            }
                        >
                            Add option
                        </Button>
                    </div>
                )}
            </div>
            {error && <p className="mt-2 text-sm text-destructive">{error}</p>}
        </div>
    );
}

function BulkPastePanel({
    onAdd,
}: {
    onAdd: (questions: QuestionDraft[]) => void;
}) {
    const [open, setOpen] = useState(false);
    const [bulkType, setBulkType] = useState<'word_reorder' | 'fill_in_blank'>(
        'word_reorder',
    );
    const [text, setText] = useState('');
    const [error, setError] = useState<string | null>(null);

    const apply = () => {
        const lines = text
            .split(/\n/)
            .map((line) => line.trim())
            .filter(Boolean);

        const parsed = lines
            .map((line) =>
                bulkType === 'word_reorder'
                    ? parseWordReorderLine(line)
                    : parseFillInBlankLine(line),
            )
            .filter((item): item is QuestionDraft => item !== null);

        if (parsed.length === 0) {
            setError(
                bulkType === 'word_reorder'
                    ? 'No valid lines. Use: gehe / ich / in / den / Supermarkt'
                    : 'No valid lines. Use: Ich ___ jeden Tag Deutsch. (lernen)',
            );

            return;
        }

        setError(null);
        onAdd(parsed);
        setText('');
        setOpen(false);
    };

    if (!open) {
        return (
            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setOpen(true)}
            >
                Paste worksheet lines
            </Button>
        );
    }

    return (
        <div className="grid gap-3 rounded-lg border border-dashed border-border/80 bg-muted/30 p-3">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-sm font-medium">Paste worksheet lines</p>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => {
                        setOpen(false);
                        setError(null);
                    }}
                >
                    Cancel
                </Button>
            </div>
            <div className="grid gap-2 sm:grid-cols-2">
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="radio"
                        name="bulk-type"
                        checked={bulkType === 'word_reorder'}
                        onChange={() => setBulkType('word_reorder')}
                    />
                    Word reorder
                </label>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="radio"
                        name="bulk-type"
                        checked={bulkType === 'fill_in_blank'}
                        onChange={() => setBulkType('fill_in_blank')}
                    />
                    Fill in the blank
                </label>
            </div>
            <Textarea
                value={text}
                onChange={(e) => setText(e.target.value)}
                rows={6}
                placeholder={
                    bulkType === 'word_reorder'
                        ? 'gehe / ich / in / den / Supermarkt / gern\nkomme / aus / ich / Deutschland | Ich komme aus Deutschland.'
                        : 'Ich ____ jeden Tag Deutsch. (lernen)\nEr ____ einen Salat, das Obst und das Gemüse ____ . (einkaufen)'
                }
            />
            <p className="text-xs text-muted-foreground">
                {bulkType === 'word_reorder'
                    ? 'One scramble per line. Optional model sentence after |'
                    : 'One sentence per line. Use ___ (or ____) for blanks. Optional verb hint in (parentheses).'}
            </p>
            {error && <p className="text-sm text-destructive">{error}</p>}
            <div>
                <Button type="button" size="sm" onClick={apply}>
                    Add parsed items
                </Button>
            </div>
        </div>
    );
}

function sectionTabLabel(section: SectionDraft, index: number): string {
    const label = section.label.trim() || String.fromCharCode(65 + index);

    if (section.title.trim()) {
        return `${label}: ${section.title.trim()}`;
    }

    if (/^[A-Za-z]$/.test(label) || /^\d+$/.test(label)) {
        return `Section ${label}`;
    }

    return label;
}

function SectionTab({
    section,
    index,
    active,
    onSelect,
    onRename,
}: {
    section: SectionDraft;
    index: number;
    active: boolean;
    onSelect: () => void;
    onRename: (label: string) => void;
}) {
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState(section.label);
    const inputRef = useRef<HTMLInputElement>(null);
    const defaultLabel = String.fromCharCode(65 + index);

    useEffect(() => {
        if (editing) {
            inputRef.current?.focus();
            inputRef.current?.select();
        }
    }, [editing]);

    useEffect(() => {
        if (!editing) {
            setDraft(section.label);
        }
    }, [editing, section.label]);

    const commit = () => {
        const next = draft.trim() || defaultLabel;
        onRename(next.slice(0, 40));
        setDraft(next.slice(0, 40));
        setEditing(false);
    };

    if (editing) {
        return (
            <input
                ref={inputRef}
                value={draft}
                maxLength={40}
                aria-label="Rename section"
                onChange={(e) => setDraft(e.target.value)}
                onBlur={commit}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        commit();
                    }

                    if (e.key === 'Escape') {
                        e.preventDefault();
                        setDraft(section.label);
                        setEditing(false);
                    }
                }}
                onClick={(e) => e.stopPropagation()}
                className="min-w-[4.5rem] max-w-[12rem] rounded-md border border-input bg-background px-3 py-2 text-sm font-medium outline-none ring-1 ring-ring"
            />
        );
    }

    return (
        <button
            type="button"
            role="tab"
            aria-selected={active}
            title="Double-click to rename"
            onClick={onSelect}
            onDoubleClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                setDraft(section.label || defaultLabel);
                setEditing(true);
            }}
            className={cn(
                'max-w-full truncate rounded-md px-3 py-2 text-left text-sm transition-colors',
                active
                    ? 'bg-background font-medium text-foreground shadow-xs'
                    : 'text-muted-foreground hover:bg-background/60 hover:text-foreground',
            )}
        >
            {sectionTabLabel(section, index)}
        </button>
    );
}

function scrollToQuestion(clientId: string) {
    requestAnimationFrame(() => {
        document
            .querySelector(`[data-question-id="${clientId}"]`)
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
}

function SectionEditor({
    section,
    sectionIndex,
    onChange,
    onRemove,
    canRemove,
    canMoveLeft,
    canMoveRight,
    onMoveLeft,
    onMoveRight,
    focusQuestionIndex = null,
    focusKey = 0,
    worksheetTotalPoints = 0,
}: {
    section: SectionDraft;
    sectionIndex: number;
    onChange: (next: SectionDraft) => void;
    onRemove: () => void;
    canRemove: boolean;
    canMoveLeft: boolean;
    canMoveRight: boolean;
    onMoveLeft: () => void;
    onMoveRight: () => void;
    focusQuestionIndex?: number | null;
    focusKey?: number;
    worksheetTotalPoints?: number;
}) {
    const [itemErrorIndex, setItemErrorIndex] = useState<number | null>(null);
    const [itemErrorMessage, setItemErrorMessage] = useState<string | null>(
        null,
    );

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    useEffect(() => {
        if (focusQuestionIndex === null || focusQuestionIndex < 0) {
            return;
        }

        const question = section.questions[focusQuestionIndex];

        if (!question) {
            return;
        }

        const message = validateQuestion(question, focusQuestionIndex + 1);
        setItemErrorIndex(focusQuestionIndex);
        setItemErrorMessage(message);
        scrollToQuestion(question.clientId);
        // Only re-run when the parent asks to focus a specific item.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [focusQuestionIndex, focusKey, section.clientId]);

    const clearItemError = () => {
        setItemErrorIndex(null);
        setItemErrorMessage(null);
    };

    const showItemIssue = (index: number, message: string) => {
        setItemErrorIndex(index);
        setItemErrorMessage(message);
        toast.error(message);
        const question = section.questions[index];

        if (question) {
            scrollToQuestion(question.clientId);
        }
    };

    const handleQuestionDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (!over || active.id === over.id) {
            return;
        }

        const oldIndex = section.questions.findIndex(
            (q) => q.clientId === active.id,
        );
        const newIndex = section.questions.findIndex(
            (q) => q.clientId === over.id,
        );
        clearItemError();
        onChange({
            ...section,
            questions: arrayMove(section.questions, oldIndex, newIndex),
        });
    };

    const defaultLabel = String.fromCharCode(65 + sectionIndex);

    const tryAddItem = () => {
        const lastIndex = section.questions.length - 1;
        const last = section.questions[lastIndex];

        if (last) {
            const message = validateQuestion(last, lastIndex + 1);

            if (message) {
                showItemIssue(lastIndex, message);

                return;
            }
        }

        clearItemError();
        const type = last?.type ?? 'fill_in_blank';

        onChange({
            ...section,
            questions: [
                ...section.questions,
                newQuestion({
                    type,
                    points: last?.points ?? 1,
                    tokens: type === 'word_reorder' ? [''] : [],
                }),
            ],
        });
    };

    return (
        <div className="grid gap-5">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-sm text-muted-foreground">
                    {section.questions.length}{' '}
                    {section.questions.length === 1 ? 'item' : 'items'}
                </p>
                <div className="flex flex-wrap items-center gap-1">
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        disabled={!canMoveLeft}
                        onClick={onMoveLeft}
                        aria-label="Move section earlier"
                    >
                        <ChevronLeft className="size-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        disabled={!canMoveRight}
                        onClick={onMoveRight}
                        aria-label="Move section later"
                    >
                        <ChevronRight className="size-4" />
                    </Button>
                    {canRemove && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            onClick={onRemove}
                            aria-label="Remove section"
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    )}
                </div>
            </div>

            <div className="grid gap-3 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor={`section-label-${section.clientId}`}>
                        Section
                    </Label>
                    <Input
                        id={`section-label-${section.clientId}`}
                        value={section.label}
                        maxLength={40}
                        onChange={(e) =>
                            onChange({ ...section, label: e.target.value })
                        }
                        placeholder={defaultLabel}
                    />
                </div>
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor={`section-title-${section.clientId}`}>
                        Title
                    </Label>
                    <Input
                        id={`section-title-${section.clientId}`}
                        value={section.title}
                        onChange={(e) =>
                            onChange({ ...section, title: e.target.value })
                        }
                        placeholder="Optional title"
                    />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor={`section-instructions-${section.clientId}`}>
                    Section instructions
                </Label>
                <Textarea
                    id={`section-instructions-${section.clientId}`}
                    value={section.instructions}
                    onChange={(e) =>
                        onChange({ ...section, instructions: e.target.value })
                    }
                    rows={2}
                    placeholder="What should students do in this section?"
                />
            </div>

            <div className="grid gap-3">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <Label>Items</Label>
                        <p className="text-sm font-medium tabular-nums text-foreground">
                            Total points: {worksheetTotalPoints}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <BulkPastePanel
                            onAdd={(questions) => {
                                clearItemError();
                                onChange({
                                    ...section,
                                    questions: mergeBulkQuestions(
                                        section.questions,
                                        questions,
                                    ),
                                });
                            }}
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={tryAddItem}
                        >
                            <Plus className="mr-2 size-4" />
                            Add item
                        </Button>
                    </div>
                </div>

                <DndContext
                    sensors={sensors}
                    collisionDetection={closestCenter}
                    onDragEnd={handleQuestionDragEnd}
                >
                    <SortableContext
                        items={section.questions.map((q) => q.clientId)}
                        strategy={verticalListSortingStrategy}
                    >
                        <div className="grid gap-2">
                            {section.questions.map((question, index) => (
                                <SortableQuestion
                                    key={question.clientId}
                                    question={question}
                                    index={index}
                                    canRemove={section.questions.length > 1}
                                    error={
                                        itemErrorIndex === index
                                            ? itemErrorMessage
                                            : null
                                    }
                                    onChange={(next) => {
                                        if (itemErrorIndex === index) {
                                            clearItemError();
                                        }

                                        const questions = [
                                            ...section.questions,
                                        ];
                                        questions[index] = next;
                                        onChange({ ...section, questions });
                                    }}
                                    onRemove={() => {
                                        clearItemError();
                                        onChange({
                                            ...section,
                                            questions: section.questions.filter(
                                                (_, i) => i !== index,
                                            ),
                                        });
                                    }}
                                />
                            ))}
                        </div>
                    </SortableContext>
                </DndContext>
            </div>
        </div>
    );
}

export function WorksheetBuilder({
    value,
    onChange,
    focusSectionIndex = null,
    focusQuestionIndex = null,
    focusKey = 0,
}: {
    value: SectionDraft[];
    onChange: (sections: SectionDraft[]) => void;
    focusSectionIndex?: number | null;
    focusQuestionIndex?: number | null;
    focusKey?: number;
}) {
    const [activeId, setActiveId] = useState(
        () => value[0]?.clientId ?? '',
    );
    const [localFocusQuestion, setLocalFocusQuestion] = useState<number | null>(
        null,
    );

    useEffect(() => {
        if (value.length === 0) {
            return;
        }

        if (!value.some((section) => section.clientId === activeId)) {
            setActiveId(value[0].clientId);
        }
    }, [activeId, value]);

    useEffect(() => {
        if (
            focusSectionIndex === null ||
            focusSectionIndex < 0 ||
            focusSectionIndex >= value.length
        ) {
            return;
        }

        setActiveId(value[focusSectionIndex].clientId);
        setLocalFocusQuestion(focusQuestionIndex);
    }, [focusKey, focusSectionIndex, focusQuestionIndex, value]);

    const activeIndex = Math.max(
        0,
        value.findIndex((section) => section.clientId === activeId),
    );
    const activeSection = value[activeIndex] ?? value[0];

    const ensureActiveSectionComplete = (): boolean => {
        if (!activeSection) {
            return true;
        }

        const issue = validateSection(activeSection);

        if (!issue) {
            return true;
        }

        toast.error(issue.message);
        setLocalFocusQuestion(issue.questionIndex);
        const question = activeSection.questions[issue.questionIndex];

        if (question) {
            scrollToQuestion(question.clientId);
        }

        return false;
    };

    const updateActive = (next: SectionDraft) => {
        const copy = [...value];
        copy[activeIndex] = next;
        onChange(copy);
    };

    const selectSection = (clientId: string) => {
        if (clientId === activeId) {
            return;
        }

        if (!ensureActiveSectionComplete()) {
            return;
        }

        setLocalFocusQuestion(null);
        setActiveId(clientId);
    };

    const addSection = () => {
        if (!ensureActiveSectionComplete()) {
            return;
        }

        const next = newSection(String.fromCharCode(65 + value.length));
        onChange([...value, next]);
        setLocalFocusQuestion(null);
        setActiveId(next.clientId);
    };

    const removeActive = () => {
        if (value.length <= 1 || !activeSection) {
            return;
        }

        const nextSections = value.filter(
            (section) => section.clientId !== activeSection.clientId,
        );
        const fallbackIndex = Math.min(activeIndex, nextSections.length - 1);
        onChange(nextSections);
        setLocalFocusQuestion(null);
        setActiveId(nextSections[fallbackIndex]?.clientId ?? '');
    };

    const moveActive = (direction: -1 | 1) => {
        const target = activeIndex + direction;

        if (target < 0 || target >= value.length) {
            return;
        }

        onChange(arrayMove(value, activeIndex, target));
    };

    if (!activeSection) {
        return (
            <div className="grid gap-3">
                <p className="text-sm text-muted-foreground">
                    No sections yet. Add one to start building the worksheet.
                </p>
                <Button type="button" variant="outline" onClick={addSection}>
                    <Plus className="mr-2 size-4" />
                    Add section
                </Button>
            </div>
        );
    }

    return (
        <div className="grid gap-4">
            <div className="flex flex-wrap items-center gap-2">
                <div
                    className="flex min-w-0 flex-1 flex-wrap gap-1 rounded-lg bg-muted p-1"
                    role="tablist"
                    aria-label="Worksheet sections"
                >
                    {value.map((section, index) => (
                        <SectionTab
                            key={section.clientId}
                            section={section}
                            index={index}
                            active={section.clientId === activeSection.clientId}
                            onSelect={() => selectSection(section.clientId)}
                            onRename={(label) => {
                                const copy = [...value];
                                copy[index] = { ...section, label };
                                onChange(copy);
                                setActiveId(section.clientId);
                            }}
                        />
                    ))}
                </div>
                <Button type="button" variant="outline" size="sm" onClick={addSection}>
                    <Plus className="mr-2 size-4" />
                    Add section
                </Button>
            </div>

            <div
                role="tabpanel"
                className="rounded-xl border border-border/70 bg-card p-4"
            >
                <SectionEditor
                    section={activeSection}
                    sectionIndex={activeIndex}
                    canRemove={value.length > 1}
                    canMoveLeft={activeIndex > 0}
                    canMoveRight={activeIndex < value.length - 1}
                    onChange={updateActive}
                    onRemove={removeActive}
                    onMoveLeft={() => moveActive(-1)}
                    onMoveRight={() => moveActive(1)}
                    focusQuestionIndex={localFocusQuestion}
                    focusKey={focusKey}
                    worksheetTotalPoints={value.reduce(
                        (total, section) =>
                            total +
                            section.questions.reduce(
                                (sectionTotal, question) =>
                                    sectionTotal + (Number(question.points) || 0),
                                0,
                            ),
                        0,
                    )}
                />
            </div>
        </div>
    );
}

function questionFromData(q: NonNullable<AssignmentData['questions']>[number]): QuestionDraft {
    const template = normalizeBlankTemplate(q.template ?? q.prompt);

    return newQuestion({
        type: q.type,
        prompt: q.prompt,
        points: q.points,
        options: q.options ?? ['', ''],
        correct_index: q.correct_index ?? 0,
        template,
        hints: q.hints?.length ? q.hints : [''],
        tokens: q.tokens ?? [],
        model_sentence: q.model_sentence ?? '',
        model_answers: syncModelAnswers(
            template,
            flattenModelAnswers(q.model_answers),
        ),
        mediaFile: null,
        has_media: Boolean(q.has_media),
        media_original_name: q.media_original_name ?? '',
        existing_media_path: q.existing_media_path ?? '',
        media_url: q.media_url ?? '',
    });
}

export function sectionsFromAssignment(assignment?: AssignmentData): SectionDraft[] {
    if (assignment?.sections && assignment.sections.length > 0) {
        return assignment.sections.map((section) => ({
            clientId: crypto.randomUUID(),
            label: section.label,
            title: section.title ?? '',
            instructions: section.instructions ?? '',
            questions: (section.questions ?? []).map((q) => questionFromData(q)),
        }));
    }

    if (assignment?.questions && assignment.questions.length > 0) {
        return [
            {
                clientId: crypto.randomUUID(),
                label: 'General',
                title: '',
                instructions: '',
                questions: assignment.questions.map((q) => questionFromData(q)),
            },
        ];
    }

    return [newSection('A')];
}

export function serializeSections(sections: SectionDraft[]) {
    return sections.map((section) => ({
        label: section.label,
        title: section.title || null,
        instructions: section.instructions || null,
        questions: section.questions.map((q) => {
            const template =
                q.type === 'fill_in_blank'
                    ? normalizeBlankTemplate(q.template || q.prompt)
                    : undefined;

            return {
                type: q.type,
                prompt:
                    q.type === 'fill_in_blank'
                        ? template || q.prompt
                        : q.type === 'word_reorder'
                          ? q.tokens.join(' / ') || q.prompt
                          : q.prompt,
                points: q.points,
                options: q.type === 'mcq' ? q.options : undefined,
                correct_index: q.type === 'mcq' ? q.correct_index : undefined,
                template,
                hints:
                    q.type === 'fill_in_blank'
                        ? q.hints.filter(Boolean)
                        : undefined,
                model_answers:
                    q.type === 'fill_in_blank' &&
                    syncModelAnswers(template ?? '', q.model_answers).some(
                        (answer) => answer.trim() !== '',
                    )
                        ? syncModelAnswers(template ?? '', q.model_answers).map(
                              (answer) => [answer],
                          )
                        : undefined,
                tokens:
                    q.type === 'word_reorder'
                        ? q.tokens.filter(Boolean)
                        : undefined,
                model_sentence:
                    q.type === 'word_reorder' && q.model_sentence
                        ? q.model_sentence
                        : undefined,
                url: q.type === 'link' ? q.media_url.trim() : undefined,
                existing_media_path:
                    isMediaFileType(q.type) &&
                    !q.mediaFile &&
                    q.existing_media_path
                        ? q.existing_media_path
                        : undefined,
                media_original_name:
                    isMediaFileType(q.type) && q.media_original_name
                        ? q.media_original_name
                        : undefined,
                media: q.mediaFile ?? undefined,
            };
        }),
    }));
}

/** Worksheet payload without File objects — safe for JSON / sections_json. */
export function serializeSectionsForJson(sections: SectionDraft[]) {
    return serializeSections(sections).map((section) => ({
        ...section,
        questions: section.questions.map((question) => {
            const { media: _media, ...rest } = question;

            return rest;
        }),
    }));
}

// Backward-compatible exports
export const QuestionBuilder = WorksheetBuilder;

export function questionsFromAssignment(
    questions: AssignmentData['questions'] = [],
): QuestionDraft[] {
    return sectionsFromAssignment({ questions } as AssignmentData)[0]?.questions ?? [
        newQuestion(),
    ];
}

export function serializeQuestions(questions: QuestionDraft[]) {
    return serializeSections([
        {
            clientId: crypto.randomUUID(),
            label: 'General',
            title: '',
            instructions: '',
            questions,
        },
    ])[0].questions;
}

export { newSection };
