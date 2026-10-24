import {
    DndContext,
    KeyboardSensor,
    MouseSensor,
    TouchSensor,
    closestCenter,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import type { DragEndEvent } from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    horizontalListSortingStrategy,
    sortableKeyboardCoordinates,
    useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { useMemo, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { AssignmentQuestion } from '@app/types/assignments';

function SortableWord({
    id,
    word,
    onRemove,
}: {
    id: string;
    word: string;
    onRemove: () => void;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id });

    return (
        <button
            ref={setNodeRef}
            type="button"
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
            }}
            className={cn(
                // touch-none: the browser must not scroll the page while a word is being dragged
                'min-h-11 cursor-grab touch-none rounded-xl border-2 border-primary/40 bg-card px-4 py-2 text-base font-medium shadow-sm select-none active:cursor-grabbing',
                isDragging && 'z-10 scale-105 opacity-80 shadow-lg',
            )}
            onClick={onRemove}
            {...attributes}
            {...listeners}
        >
            {word}
        </button>
    );
}

type Props = {
    question: AssignmentQuestion;
    value: string[];
    onChange: (tokens: string[]) => void;
    readOnly?: boolean;
};

export function WordReorderAnswer({
    question,
    value,
    onChange,
    readOnly = false,
}: Props) {
    const allTokens = question.tokens ?? [];

    const bankTokens = useMemo(() => {
        const remaining = [...allTokens];

        for (const token of value) {
            const index = remaining.indexOf(token);

            if (index >= 0) {
                remaining.splice(index, 1);
            }
        }

        return remaining;
    }, [allTokens, value]);

    const sortableIds = useMemo(
        () => value.map((token, index) => `${index}-${token}`),
        [value],
    );

    // Touch needs a short press-and-hold to start a drag, so a plain tap still works (and scrolling is not hijacked).
    const sensors = useSensors(
        useSensor(MouseSensor, { activationConstraint: { distance: 5 } }),
        useSensor(TouchSensor, {
            activationConstraint: { delay: 150, tolerance: 8 },
        }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );
    const justDragged = useRef(false);

    const removeToken = (index: number) => {
        if (justDragged.current) {
            return;
        }

        onChange(value.filter((_, i) => i !== index));
    };

    const addToken = (token: string) => {
        onChange([...value, token]);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        // the tap that ends a drag must not also remove the word
        justDragged.current = true;
        setTimeout(() => {
            justDragged.current = false;
        }, 100);

        if (!over || active.id === over.id) {
            return;
        }

        const oldIndex = sortableIds.indexOf(String(active.id));
        const newIndex = sortableIds.indexOf(String(over.id));

        if (oldIndex >= 0 && newIndex >= 0) {
            onChange(arrayMove(value, oldIndex, newIndex));
        }
    };

    if (readOnly) {
        return <p className="text-sm">{value.join(' ')}</p>;
    }

    return (
        <div className="grid gap-3">
            <ol className="space-y-1 text-sm text-muted-foreground">
                <li>1. Tap the words below to build your sentence.</li>
                <li>
                    2. Press and hold a word, then drag it to change its place.
                </li>
                <li>3. Tap a word in your sentence to take it back.</li>
            </ol>

            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragEnd={handleDragEnd}
            >
                <div className="grid gap-2">
                    <p className="text-xs font-medium text-muted-foreground uppercase">
                        Your sentence
                    </p>
                    <SortableContext
                        items={sortableIds}
                        strategy={horizontalListSortingStrategy}
                    >
                        <div className="flex min-h-16 flex-wrap gap-2 rounded-2xl border-2 border-dashed border-border/70 bg-muted/20 p-3">
                            {value.length === 0 && (
                                <span className="text-sm text-muted-foreground">
                                    Add words from the bank below
                                </span>
                            )}
                            {value.map((token, index) => (
                                <SortableWord
                                    key={sortableIds[index]}
                                    id={sortableIds[index]}
                                    word={token}
                                    onRemove={() => removeToken(index)}
                                />
                            ))}
                        </div>
                    </SortableContext>
                </div>
            </DndContext>

            <div className="grid gap-2">
                <p className="text-xs font-medium text-muted-foreground uppercase">
                    Word bank
                </p>
                <div className="flex min-h-12 flex-wrap gap-2">
                    {bankTokens.length === 0 && (
                        <span className="text-sm text-muted-foreground">
                            All words used. Check the order, then continue.
                        </span>
                    )}
                    {bankTokens.map((token, index) => (
                        <button
                            key={`${token}-${index}`}
                            type="button"
                            className="min-h-11 rounded-xl border bg-muted/40 px-4 py-2 text-base font-medium transition-transform hover:bg-muted active:scale-95"
                            onClick={() => addToken(token)}
                        >
                            {token}
                        </button>
                    ))}
                </div>
            </div>

            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => onChange([])}
            >
                Reset
            </Button>
        </div>
    );
}
