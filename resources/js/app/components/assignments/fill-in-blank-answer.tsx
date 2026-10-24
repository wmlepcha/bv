import { Lightbulb } from 'lucide-react';
import { Fragment, useRef, useState } from 'react';
import { cn } from '@/lib/utils';
import { insertAtCursor } from '@app/components/assignments/german-char-toolbar';
import { clipboardGuards } from '@app/components/assignments/german-text-input';
import type { AssignmentQuestion } from '@app/types/assignments';

type Props = {
    question: AssignmentQuestion;
    value: string[];
    onChange: (blanks: string[]) => void;
    readOnly?: boolean;
};

export function FillInBlankAnswer({
    question,
    value,
    onChange,
    readOnly = false,
}: Props) {
    const template = (question.template ?? question.prompt).replace(
        /_{3,}/g,
        '___',
    );
    const hints = question.hints ?? [];
    const parts = template.split('___');
    const blankCount = Math.max(parts.length - 1, question.blank_count ?? 0);

    const updateBlank = (index: number, text: string) => {
        const next = [...value];

        while (next.length < blankCount) {
            next.push('');
        }

        next[index] = text;
        onChange(next);
    };

    const hint = hints[0] ? (
        <span className="ml-1 text-muted-foreground">({hints[0]})</span>
    ) : null;

    if (readOnly) {
        let blankIndex = 0;

        return (
            <p className="text-sm leading-relaxed">
                {parts.map((part, index) => (
                    <span key={index}>
                        {part}
                        {index < parts.length - 1 && (
                            <strong className="mx-1 underline decoration-primary">
                                {value[blankIndex++] ?? '___'}
                            </strong>
                        )}
                    </span>
                ))}
                {hint}
            </p>
        );
    }

    return (
        <InteractiveBlanks
            parts={parts}
            blankCount={blankCount}
            hints={hints}
            value={value}
            onChange={updateBlank}
        />
    );
}

const GERMAN_CHARS = ['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'];

/** The sentence reads as one line, with a tappable gap for each blank and one umlaut row below. */
function InteractiveBlanks({
    parts,
    blankCount,
    hints,
    value,
    onChange,
}: {
    parts: string[];
    blankCount: number;
    hints: string[];
    value: string[];
    onChange: (index: number, text: string) => void;
}) {
    const inputs = useRef<(HTMLInputElement | null)[]>([]);
    const [active, setActive] = useState(0);
    const filled = Array.from({ length: blankCount }).filter(
        (_, i) => (value[i] ?? '').trim() !== '',
    ).length;
    const many = blankCount > 1;

    const insertChar = (char: string) => {
        const el = inputs.current[active];

        if (!el) {
            return;
        }

        const { value: next, cursor } = insertAtCursor(
            value[active] ?? '',
            char,
            el.selectionStart ?? el.value.length,
            el.selectionEnd ?? el.value.length,
        );
        onChange(active, next);
        requestAnimationFrame(() => el.setSelectionRange(cursor, cursor));
    };

    return (
        <div className="grid gap-4">
            <p className="text-sm text-muted-foreground">
                {many
                    ? `Type the missing words. Tap each gap to fill it in. (${filled} of ${blankCount} done)`
                    : 'Type the missing word in the gap.'}
            </p>

            <p className="text-xl leading-[2.75rem] font-medium">
                {parts.map((part, index) => (
                    <Fragment key={index}>
                        {part}
                        {index < blankCount && (
                            <span className="relative mx-1 inline-block align-middle">
                                {many && (
                                    <span className="absolute -top-2 -left-1 z-10 flex size-5 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-primary-foreground">
                                        {index + 1}
                                    </span>
                                )}
                                <input
                                    ref={(el) => {
                                        inputs.current[index] = el;
                                    }}
                                    value={value[index] ?? ''}
                                    onChange={(e) =>
                                        onChange(index, e.target.value)
                                    }
                                    onFocus={() => setActive(index)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            inputs.current[index + 1]?.focus();
                                        }
                                    }}
                                    aria-label={`Blank ${index + 1}`}
                                    autoCapitalize="none"
                                    autoComplete="off"
                                    autoCorrect="off"
                                    spellCheck={false}
                                    size={Math.max(
                                        6,
                                        (value[index] ?? '').length + 1,
                                    )}
                                    className={cn(
                                        'h-11 min-w-24 rounded-xl border-2 border-dashed bg-primary/10 px-2 text-center text-xl font-semibold transition-colors outline-none focus:border-solid focus:border-primary focus:bg-background',
                                        (value[index] ?? '').trim() !== ''
                                            ? 'border-solid border-primary/60'
                                            : 'border-primary/50',
                                    )}
                                    {...clipboardGuards}
                                />
                            </span>
                        )}
                    </Fragment>
                ))}
            </p>

            {hints.some((hint) => hint?.trim()) && (
                <div className="flex flex-wrap gap-2">
                    {hints.slice(0, blankCount).map(
                        (hint, index) =>
                            hint?.trim() && (
                                <span
                                    key={index}
                                    className="inline-flex items-center gap-1.5 rounded-full bg-amber-500/15 px-3 py-1.5 text-sm font-medium text-amber-700 dark:text-amber-300"
                                >
                                    <Lightbulb className="size-4" />
                                    {many
                                        ? `Gap ${index + 1} hint: `
                                        : 'Hint: '}
                                    {hint}
                                </span>
                            ),
                    )}
                </div>
            )}

            <div className="space-y-1.5">
                <p className="text-xs text-muted-foreground">
                    Need a German letter? Tap to add it to the
                    {many
                        ? ` gap you are typing in (gap ${active + 1}).`
                        : ' gap.'}
                </p>
                <div className="flex flex-wrap gap-1.5">
                    {GERMAN_CHARS.map((char) => (
                        <button
                            key={char}
                            type="button"
                            // keep focus (and the phone keyboard) on the gap
                            onMouseDown={(e) => e.preventDefault()}
                            onClick={() => insertChar(char)}
                            className="h-10 min-w-10 rounded-xl border border-border bg-card px-2 font-serif text-lg active:scale-95 active:bg-primary/15"
                        >
                            {char}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}
