import { Link } from '@inertiajs/react';
import { UserRound } from 'lucide-react';
import { cn } from '@/lib/utils';

export type AssignmentCreatorCardData = {
    id: number;
    name: string;
    role: string;
    role_label: string;
    photo_url: string | null;
    published_count: number;
    draft_count: number;
    assignments_count: number;
    is_mine?: boolean;
};

type Props = {
    creator: AssignmentCreatorCardData;
    href: string;
};

export function AssignmentCreatorCard({ creator, href }: Props) {
    const isMine = creator.is_mine === true;

    return (
        <Link
            href={href}
            className={cn(
                'group flex flex-col rounded-xl border p-4 transition',
                isMine
                    ? 'border-primary/40 bg-primary/8 hover:border-primary/60 hover:bg-primary/12'
                    : 'border-border/70 bg-card hover:border-primary/30',
            )}
        >
            <div className="flex items-start gap-3">
                {creator.photo_url ? (
                    <img
                        src={creator.photo_url}
                        alt=""
                        className={cn(
                            'size-11 shrink-0 rounded-full object-cover',
                            isMine && 'ring-2 ring-primary/40',
                        )}
                    />
                ) : (
                    <div
                        className={cn(
                            'flex size-11 shrink-0 items-center justify-center rounded-full',
                            isMine
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground',
                        )}
                    >
                        <UserRound className="size-5" />
                    </div>
                )}
                <div className="min-w-0 flex-1">
                    <p
                        className={cn(
                            'truncate text-sm font-medium leading-snug',
                            isMine && 'text-primary',
                        )}
                    >
                        {creator.name}
                    </p>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        {creator.role_label}
                    </p>
                </div>
            </div>
            <p className="mt-3 text-xs text-muted-foreground">
                {creator.assignments_count} assignment
                {creator.assignments_count === 1 ? '' : 's'}
                {' · '}
                {creator.published_count} published
                {' · '}
                {creator.draft_count} draft
                {creator.draft_count === 1 ? '' : 's'}
            </p>
        </Link>
    );
}
