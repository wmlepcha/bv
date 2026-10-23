import { GermanTextInput } from '@app/components/assignments/german-text-input';
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
    const template = (question.template ?? question.prompt).replace(/_{3,}/g, '___');
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
        <div className="grid gap-2">
            <div className="flex flex-wrap items-center gap-x-2 gap-y-3 text-sm leading-relaxed">
                {parts.map((part, index) => (
                    <span key={index} className="inline-flex flex-wrap items-center gap-2">
                        {part && <span>{part}</span>}
                        {index < blankCount && (
                            <GermanTextInput
                                value={value[index] ?? ''}
                                onChange={(text) => updateBlank(index, text)}
                                placeholder="..."
                            />
                        )}
                    </span>
                ))}
                {hint}
            </div>
        </div>
    );
}
