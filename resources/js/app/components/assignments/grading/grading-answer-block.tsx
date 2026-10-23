import { useMemo, useRef, useState } from 'react';
import { cn } from '@/lib/utils';
import {
    AnnotationLayer,
    PenPreviewPath,
} from '@app/components/assignments/grading/annotation-layer';
import { newAnnotation   } from '@app/components/assignments/grading/grading-types';
import type {GradingAnnotation, GradingTool} from '@app/components/assignments/grading/grading-types';
import { HighlightedAnswerText } from '@app/components/assignments/grading/highlight-text';
import { pointsToPenPath } from '@app/components/assignments/grading/pen-stroke';

type Props = {
    questionId: number;
    prompt: string;
    answerText: string;
    tool: GradingTool;
    penColor: string;
    highlightColor: string;
    memoColor: string;
    strokeWidth: number;
    annotations: GradingAnnotation[];
    selectedClientId: string | null;
    onSelect: (clientId: string | null) => void;
    onMutate: (updater: (current: GradingAnnotation[]) => GradingAnnotation[]) => void;
    onMemoPlaced?: (clientId: string) => void;
    autoFocusClientId?: string | null;
    onAutoFocusComplete?: () => void;
    readOnly?: boolean;
};

export function GradingAnswerBlock({
    questionId,
    prompt,
    answerText,
    tool,
    penColor,
    highlightColor,
    memoColor,
    strokeWidth,
    annotations,
    selectedClientId,
    onSelect,
    onMutate,
    onMemoPlaced,
    autoFocusClientId = null,
    onAutoFocusComplete,
    readOnly = false,
}: Props) {
    const containerRef = useRef<HTMLDivElement>(null);
    const [drawing, setDrawing] = useState(false);
    const [currentPoints, setCurrentPoints] = useState<number[][]>([]);

    const questionAnnotations = useMemo(
        () => annotations.filter((annotation) => annotation.question_id === questionId),
        [annotations, questionId],
    );

    const previewPath = useMemo(() => {
        if (!drawing || currentPoints.length < 2) {
            return '';
        }

        return pointsToPenPath(currentPoints, strokeWidth);
    }, [currentPoints, drawing, strokeWidth]);

    const updateAnnotation = (
        clientId: string,
        payloadPatch: Record<string, unknown>,
    ) => {
        onMutate((current) =>
            current.map((annotation) =>
                annotation.clientId === clientId
                    ? {
                          ...annotation,
                          payload: {
                              ...annotation.payload,
                              ...payloadPatch,
                          },
                      }
                    : annotation,
            ),
        );
    };

    const handleMouseUp = () => {
        if (readOnly || tool !== 'highlight') {
            return;
        }

        const selection = window.getSelection();

        if (!selection || selection.isCollapsed || !containerRef.current) {
            return;
        }

        const selected = selection.toString();

        if (!selected || !answerText.includes(selected)) {
            return;
        }

        const start = answerText.indexOf(selected);
        const end = start + selected.length;

        onMutate((current) => [
            ...current,
            newAnnotation({
                type: 'highlight',
                question_id: questionId,
                payload: {
                    start_offset: start,
                    end_offset: end,
                    color: highlightColor,
                },
            }),
        ]);
        selection.removeAllRanges();
    };

    const handlePointerDown = (event: React.PointerEvent) => {
        if (readOnly || !containerRef.current) {
            return;
        }

        const rect = containerRef.current.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        if (tool === 'select') {
            onSelect(null);

            return;
        }

        if (tool === 'memo') {
            const memo = newAnnotation({
                type: 'memo',
                question_id: questionId,
                payload: {
                    x: Math.max(0, x - 8),
                    y: Math.max(0, y - 8),
                    width: 180,
                    text: '',
                    color: memoColor,
                },
            });

            onMutate((current) => [...current, memo]);
            onMemoPlaced?.(memo.clientId);

            return;
        }

        if (tool !== 'pen') {
            return;
        }

        event.currentTarget.setPointerCapture(event.pointerId);
        setDrawing(true);
        setCurrentPoints([[x, y]]);
    };

    const handlePointerMove = (event: React.PointerEvent) => {
        if (!drawing || !containerRef.current) {
            return;
        }

        const rect = containerRef.current.getBoundingClientRect();
        setCurrentPoints((points) => [
            ...points,
            [event.clientX - rect.left, event.clientY - rect.top],
        ]);
    };

    const finishPen = (event: React.PointerEvent) => {
        if (!drawing) {
            return;
        }

        if (event.currentTarget.hasPointerCapture(event.pointerId)) {
            event.currentTarget.releasePointerCapture(event.pointerId);
        }

        if (currentPoints.length >= 2) {
            const path = pointsToPenPath(currentPoints, strokeWidth);

            onMutate((current) => [
                ...current,
                newAnnotation({
                    type: 'pen',
                    question_id: questionId,
                    payload: {
                        path,
                        color: penColor,
                        strokeWidth,
                    },
                }),
            ]);
        }

        setDrawing(false);
        setCurrentPoints([]);
    };

    return (
        <div className="grid gap-2 rounded-xl border border-border/70 p-4">
            <p className="text-sm font-medium">{prompt}</p>
            <div
                ref={containerRef}
                className={cn(
                    'relative min-h-[80px] rounded-lg bg-muted/40 p-3 text-sm',
                    tool === 'pen' && !readOnly && 'cursor-crosshair',
                    tool === 'highlight' && !readOnly && 'cursor-text',
                    tool === 'memo' && !readOnly && 'cursor-cell',
                )}
                onMouseUp={handleMouseUp}
                onPointerDown={handlePointerDown}
                onPointerMove={handlePointerMove}
                onPointerUp={finishPen}
                onPointerLeave={finishPen}
            >
                <div className="relative z-0 whitespace-pre-wrap select-text">
                    <HighlightedAnswerText
                        text={answerText}
                        annotations={questionAnnotations}
                        selectedClientId={selectedClientId}
                        interactive={!readOnly && tool === 'select'}
                        onSelectHighlight={onSelect}
                    />
                </div>
                <AnnotationLayer
                    annotations={questionAnnotations}
                    tool={tool}
                    selectedClientId={selectedClientId}
                    autoFocusClientId={autoFocusClientId}
                    readOnly={readOnly}
                    onSelect={onSelect}
                    onUpdate={updateAnnotation}
                    onAutoFocusComplete={onAutoFocusComplete}
                />
                {previewPath && (
                    <PenPreviewPath path={previewPath} color={penColor} />
                )}
            </div>
        </div>
    );
}
