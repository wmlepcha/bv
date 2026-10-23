import { cn } from '@/lib/utils';
import type { AssignmentStatus } from '@app/types/assignments';

const STYLES: Record<AssignmentStatus, string> = {
    draft: 'bg-muted text-muted-foreground',
    pending_approval: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-100',
    rejected: 'bg-red-100 text-red-900 dark:bg-red-950 dark:text-red-100',
    published: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100',
    archived: 'bg-muted text-muted-foreground',
};

export function AssignmentStatusBadge({
    status,
    label,
    className,
}: {
    status: AssignmentStatus;
    label: string;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                STYLES[status],
                className,
            )}
        >
            {label}
        </span>
    );
}
