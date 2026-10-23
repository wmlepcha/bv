import { useCallback, useEffect, useState } from 'react';
import {
    DEFAULT_HIGHLIGHT_COLOR,
    DEFAULT_MEMO_COLOR,
    DEFAULT_PEN_COLOR,
    STROKE_WIDTHS,
} from '@app/components/assignments/grading/grading-colors';
import {
    cloneAnnotations,
    ensureClientIds,
    stripClientIds
    
} from '@app/components/assignments/grading/grading-types';
import type {GradingAnnotation} from '@app/components/assignments/grading/grading-types';
import type { GradingTool } from '@app/components/assignments/grading/grading-types';
import type { SubmissionAnnotation } from '@app/types/assignments';

const MAX_UNDO = 50;

export function useGradingAnnotations(initial: SubmissionAnnotation[]) {
    const [annotations, setAnnotations] = useState<GradingAnnotation[]>(() =>
        ensureClientIds(initial),
    );
    const [undoStack, setUndoStack] = useState<GradingAnnotation[][]>([]);
    const [redoStack, setRedoStack] = useState<GradingAnnotation[][]>([]);
    const [tool, setTool] = useState<GradingTool>('select');
    const [penColor, setPenColor] = useState<string>(DEFAULT_PEN_COLOR);
    const [highlightColor, setHighlightColor] = useState<string>(
        DEFAULT_HIGHLIGHT_COLOR,
    );
    const [memoColor, setMemoColor] = useState<string>(DEFAULT_MEMO_COLOR);
    const [strokeWidth, setStrokeWidth] = useState<number>(STROKE_WIDTHS[1]);
    const [selectedClientId, setSelectedClientId] = useState<string | null>(
        null,
    );
    const [autoFocusClientId, setAutoFocusClientId] = useState<string | null>(
        null,
    );

    const focusMemo = useCallback((clientId: string) => {
        setAutoFocusClientId(clientId);
        setSelectedClientId(clientId);
    }, []);

    const clearMemoFocus = useCallback(() => {
        setAutoFocusClientId(null);
    }, []);

    const mutate = useCallback(
        (updater: (current: GradingAnnotation[]) => GradingAnnotation[]) => {
            setAnnotations((current) => {
                setUndoStack((stack) => [
                    ...stack.slice(-(MAX_UNDO - 1)),
                    cloneAnnotations(current),
                ]);
                setRedoStack([]);

                return ensureClientIds(updater(current));
            });
        },
        [],
    );

    const replaceAnnotations = useCallback(
        (next: GradingAnnotation[]) => {
            mutate(() => next);
        },
        [mutate],
    );

    const undo = useCallback(() => {
        setUndoStack((stack) => {
            if (stack.length === 0) {
                return stack;
            }

            const previous = stack[stack.length - 1];
            setAnnotations((current) => {
                setRedoStack((redo) => [
                    ...redo,
                    cloneAnnotations(current),
                ]);

                return cloneAnnotations(previous);
            });

            return stack.slice(0, -1);
        });
        setSelectedClientId(null);
    }, []);

    const redo = useCallback(() => {
        setRedoStack((stack) => {
            if (stack.length === 0) {
                return stack;
            }

            const next = stack[stack.length - 1];
            setAnnotations((current) => {
                setUndoStack((undo) => [
                    ...undo,
                    cloneAnnotations(current),
                ]);

                return cloneAnnotations(next);
            });

            return stack.slice(0, -1);
        });
        setSelectedClientId(null);
    }, []);

    const deleteSelected = useCallback(() => {
        if (!selectedClientId) {
            return;
        }

        mutate((current) =>
            current.filter(
                (annotation) => annotation.clientId !== selectedClientId,
            ),
        );
        setSelectedClientId(null);
    }, [mutate, selectedClientId]);

    const recolorSelected = useCallback(
        (color: string, memoColorId?: string) => {
            if (!selectedClientId) {
                return;
            }

            mutate((current) =>
                current.map((annotation) => {
                    if (annotation.clientId !== selectedClientId) {
                        return annotation;
                    }

                    if (annotation.type === 'memo' && memoColorId) {
                        return {
                            ...annotation,
                            payload: {
                                ...annotation.payload,
                                color: memoColorId,
                            },
                        };
                    }

                    return {
                        ...annotation,
                        payload: {
                            ...annotation.payload,
                            color,
                        },
                    };
                }),
            );
        },
        [mutate, selectedClientId],
    );

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            const mod = event.metaKey || event.ctrlKey;

            if (mod && event.key === 'z' && !event.shiftKey) {
                event.preventDefault();
                undo();

                return;
            }

            if (mod && event.key === 'z' && event.shiftKey) {
                event.preventDefault();
                redo();

                return;
            }

            if (
                (event.key === 'Delete' || event.key === 'Backspace') &&
                selectedClientId &&
                !(event.target instanceof HTMLInputElement) &&
                !(event.target instanceof HTMLTextAreaElement) &&
                !(event.target as HTMLElement).isContentEditable
            ) {
                event.preventDefault();
                deleteSelected();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [deleteSelected, redo, selectedClientId, undo]);

    return {
        annotations,
        mutate,
        replaceAnnotations,
        undo,
        redo,
        canUndo: undoStack.length > 0,
        canRedo: redoStack.length > 0,
        stripForSave: () => stripClientIds(annotations),
        tool,
        setTool,
        penColor,
        setPenColor,
        highlightColor,
        setHighlightColor,
        memoColor,
        setMemoColor,
        strokeWidth,
        setStrokeWidth,
        selectedClientId,
        setSelectedClientId,
        autoFocusClientId,
        focusMemo,
        clearMemoFocus,
    };
}
