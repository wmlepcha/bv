import type { ReactNode } from 'react';
import { highlightBackground } from '@app/components/assignments/grading/grading-colors';
import type { GradingAnnotation } from '@app/components/assignments/grading/grading-types';

type HighlightRange = {
    start: number;
    end: number;
    color: string;
    clientId: string;
};

function highlightRanges(
    annotations: GradingAnnotation[],
): HighlightRange[] {
    return annotations
        .filter((annotation) => annotation.type === 'highlight')
        .map((annotation) => ({
            start: annotation.payload.start_offset as number,
            end: annotation.payload.end_offset as number,
            color: (annotation.payload.color as string) ?? '#FFE066',
            clientId: annotation.clientId,
        }))
        .filter(
            (range) =>
                Number.isFinite(range.start) &&
                Number.isFinite(range.end) &&
                range.end > range.start,
        )
        .sort((a, b) => a.start - b.start);
}

export function HighlightedAnswerText({
    text,
    annotations,
    selectedClientId,
    onSelectHighlight,
    interactive = false,
}: {
    text: string;
    annotations: GradingAnnotation[];
    selectedClientId?: string | null;
    onSelectHighlight?: (clientId: string) => void;
    interactive?: boolean;
}) {
    const highlights = highlightRanges(annotations);

    if (highlights.length === 0) {
        return <>{text}</>;
    }

    const breakpoints = new Set<number>([0, text.length]);

    for (const highlight of highlights) {
        breakpoints.add(Math.max(0, highlight.start));
        breakpoints.add(Math.min(text.length, highlight.end));
    }

    const sorted = [...breakpoints].sort((a, b) => a - b);
    const segments: ReactNode[] = [];

    for (let index = 0; index < sorted.length - 1; index++) {
        const start = sorted[index];
        const end = sorted[index + 1];

        if (start >= end) {
            continue;
        }

        const slice = text.slice(start, end);
        const covering = highlights.filter(
            (highlight) => highlight.start <= start && highlight.end >= end,
        );
        const active = covering[covering.length - 1];

        if (!active) {
            segments.push(<span key={`${start}-${end}`}>{slice}</span>);
            continue;
        }

        segments.push(
            <mark
                key={`${start}-${end}-${active.clientId}`}
                className="rounded px-0.5"
                style={{
                    backgroundColor: highlightBackground(active.color),
                }}
                onClick={
                    interactive
                        ? (event) => {
                              event.stopPropagation();
                              onSelectHighlight?.(active.clientId);
                          }
                        : undefined
                }
                data-selected={
                    selectedClientId === active.clientId ? 'true' : undefined
                }
            >
                {slice}
            </mark>,
        );
    }

    return <>{segments}</>;
}
