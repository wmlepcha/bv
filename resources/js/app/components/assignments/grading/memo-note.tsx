import { GripVertical } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';
import { memoColorById } from '@app/components/assignments/grading/grading-colors';
import type { GradingAnnotation } from '@app/components/assignments/grading/grading-types';

type Props = {
    annotation: GradingAnnotation;
    selected: boolean;
    readOnly: boolean;
    autoFocus?: boolean;
    onSelect: () => void;
    onUpdate: (payload: Record<string, unknown>) => void;
    onAutoFocusComplete?: () => void;
};

export function MemoNote({
    annotation,
    selected,
    readOnly,
    autoFocus = false,
    onSelect,
    onUpdate,
    onAutoFocusComplete,
}: Props) {
    const scheme = memoColorById(annotation.payload.color as string | undefined);
    const x = (annotation.payload.x as number) ?? 0;
    const y = (annotation.payload.y as number) ?? 0;
    const width = (annotation.payload.width as number) ?? 180;
    const text = (annotation.payload.text as string) ?? '';
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    const [dragOffset, setDragOffset] = useState<{ x: number; y: number } | null>(
        null,
    );
    const [resizeWidth, setResizeWidth] = useState<number | null>(null);

    const displayX = x + (dragOffset?.x ?? 0);
    const displayY = y + (dragOffset?.y ?? 0);
    const displayWidth = resizeWidth ?? width;

    useEffect(() => {
        if (!autoFocus || readOnly || !textareaRef.current) {
            return;
        }

        textareaRef.current.focus();
        onAutoFocusComplete?.();
    }, [autoFocus, onAutoFocusComplete, readOnly]);

    const startDrag = (event: React.PointerEvent) => {
        if (readOnly) {
            return;
        }

        event.stopPropagation();
        onSelect();

        const startX = event.clientX;
        const startY = event.clientY;

        const onMove = (moveEvent: PointerEvent) => {
            setDragOffset({
                x: moveEvent.clientX - startX,
                y: moveEvent.clientY - startY,
            });
        };

        const onUp = (upEvent: PointerEvent) => {
            const nextX = Math.max(0, x + (upEvent.clientX - startX));
            const nextY = Math.max(0, y + (upEvent.clientY - startY));
            setDragOffset(null);
            onUpdate({ x: nextX, y: nextY });
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
        };

        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
    };

    const startResize = (event: React.PointerEvent) => {
        if (readOnly) {
            return;
        }

        event.stopPropagation();
        onSelect();

        const startX = event.clientX;

        const onMove = (moveEvent: PointerEvent) => {
            setResizeWidth(
                Math.max(120, width + (moveEvent.clientX - startX)),
            );
        };

        const onUp = (upEvent: PointerEvent) => {
            const nextWidth = Math.max(
                120,
                width + (upEvent.clientX - startX),
            );
            setResizeWidth(null);
            onUpdate({ width: nextWidth });
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
        };

        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
    };

    return (
        <div
            className={cn(
                'absolute flex rounded-md border shadow',
                !readOnly && 'pointer-events-auto',
                selected && 'ring-2 ring-primary ring-offset-1',
            )}
            style={{
                left: displayX,
                top: displayY,
                width: displayWidth,
                backgroundColor: scheme.bg,
                borderColor: scheme.border,
                color: scheme.text,
            }}
            onClick={(event) => {
                event.stopPropagation();
                onSelect();
            }}
        >
            {!readOnly && (
                <button
                    type="button"
                    className="flex shrink-0 cursor-grab touch-none items-start px-0.5 pt-1.5 text-current/50 hover:text-current active:cursor-grabbing"
                    aria-label="Drag memo"
                    onPointerDown={startDrag}
                >
                    <GripVertical className="size-3.5" />
                </button>
            )}
            <div className="min-w-0 flex-1 py-1.5 pr-2">
                {readOnly ? (
                    <p className="text-xs whitespace-pre-wrap break-words">
                        {text}
                    </p>
                ) : (
                    <textarea
                        ref={textareaRef}
                        value={text}
                        rows={2}
                        placeholder="Write a note…"
                        className="w-full min-h-[3rem] resize-none border-0 bg-transparent p-0 text-xs outline-none placeholder:text-current/40"
                        style={{ color: scheme.text }}
                        onPointerDown={(event) => event.stopPropagation()}
                        onClick={(event) => event.stopPropagation()}
                        onChange={(event) =>
                            onUpdate({ text: event.target.value })
                        }
                    />
                )}
            </div>
            {!readOnly && (
                <div
                    className="absolute right-0 bottom-0 size-3 cursor-se-resize rounded-tl bg-black/20"
                    onPointerDown={startResize}
                />
            )}
        </div>
    );
}
