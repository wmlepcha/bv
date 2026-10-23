import type { DraggableAttributes, SyntheticListenerMap } from '@dnd-kit/core';
import { Link } from '@inertiajs/react';
import { GripVertical, MoreVertical } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { AssignmentStatusBadge } from '@app/components/assignments/assignment-status-badge';
import type { AssignmentData } from '@app/types/assignments';

type Props = {
    assignment: AssignmentData;
    basePath: string;
    draggable?: boolean;
    dragHandleProps?: {
        attributes: DraggableAttributes;
        listeners: SyntheticListenerMap | undefined;
    };
    isDragging?: boolean;
};

export function AssignmentCard({
    assignment,
    basePath,
    draggable = false,
    dragHandleProps,
    isDragging = false,
}: Props) {
    const showHref = `${basePath}/${assignment.id}`;
    const duplicateHref = `${basePath}/${assignment.id}/duplicate`;

    return (
        <div
            className={cn(
                'group relative flex flex-col rounded-xl border border-border/70 bg-card transition hover:border-primary/30',
                isDragging && 'opacity-50 ring-2 ring-primary/40',
            )}
        >
            <div className="flex items-start gap-1 p-3 pb-0">
                {draggable && dragHandleProps && (
                    <button
                        type="button"
                        className="mt-0.5 shrink-0 cursor-grab touch-none rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground active:cursor-grabbing"
                        aria-label="Drag to move"
                        {...dragHandleProps.attributes}
                        {...dragHandleProps.listeners}
                    >
                        <GripVertical className="size-4" />
                    </button>
                )}
                <Link
                    href={showHref}
                    className="min-w-0 flex-1 pr-8"
                >
                    <div className="flex items-start justify-between gap-2">
                        <p className="line-clamp-2 text-sm font-medium leading-snug">
                            {assignment.title}
                        </p>
                        <AssignmentStatusBadge
                            status={assignment.status}
                            label={assignment.status_label}
                            className="shrink-0"
                        />
                    </div>
                    <p className="mt-1.5 line-clamp-1 text-xs text-muted-foreground">
                        {assignment.batch.name}
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {(assignment.turned_in_count ??
                            assignment.submissions_count ??
                            0) +
                            '/' +
                            (assignment.assigned_count ?? '—')}{' '}
                        turned in · {assignment.max_score} pts
                    </p>
                </Link>
            </div>

            <div className="absolute top-2 right-2">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-8 opacity-70 group-hover:opacity-100"
                            aria-label="Assignment actions"
                            onClick={(event) => event.stopPropagation()}
                        >
                            <MoreVertical className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem asChild>
                            <Link href={showHref}>Open</Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={duplicateHref}>Duplicate to batch</Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    );
}
