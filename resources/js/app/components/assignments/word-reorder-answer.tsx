import {
    DndContext,
    PointerSensor,
    closestCenter,
    
    useSensor,
    useSensors
} from '@dnd-kit/core';
import type {DragEndEvent} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    horizontalListSortingStrategy,
    useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { AssignmentQuestion } from '@app/types/assignments';

function SortableWord({ id, word }: { id: string; word: string }) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } =
        useSortable({ id });

    return (
        <button
            ref={setNodeRef}
            type="button"
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
            }}
            className={cn(
                'cursor-grab rounded-md border bg-card px-3 py-1.5 text-sm shadow-sm active:cursor-grabbing',
                isDragging && 'opacity-60',
            )}
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

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
    );

    const addToken = (token: string) => {
        onChange([...value, token]);
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

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
            <p className="text-xs text-muted-foreground">
                Tap words from the bank, then drag to reorder your sentence.
            </p>

            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragEnd={handleDragEnd}
            >
                <div className="grid gap-2">
                    <p className="text-xs font-medium uppercase text-muted-foreground">
                        Your sentence
                    </p>
                    <SortableContext
                        items={sortableIds}
                        strategy={horizontalListSortingStrategy}
                    >
                        <div className="flex min-h-12 flex-wrap gap-2 rounded-lg border border-dashed border-border/70 bg-muted/20 p-3">
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
                                />
                            ))}
                        </div>
                    </SortableContext>
                </div>
            </DndContext>

            <div className="grid gap-2">
                <p className="text-xs font-medium uppercase text-muted-foreground">
                    Word bank
                </p>
                <div className="flex flex-wrap gap-2">
                    {bankTokens.map((token, index) => (
                        <button
                            key={`${token}-${index}`}
                            type="button"
                            className="rounded-md border bg-muted/40 px-3 py-1.5 text-sm hover:bg-muted"
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
