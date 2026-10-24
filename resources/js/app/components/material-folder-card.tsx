import { Link } from '@inertiajs/react';
import { Share2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

const FOLDER_PALETTE = [
    {
        tab: 'bg-[#FFD6C8]',
        body: 'bg-[#FFE8DF]',
        text: 'text-[#5C2A1A]',
        muted: 'text-[#5C2A1A]/70',
    },
    {
        tab: 'bg-[#C8E4FF]',
        body: 'bg-[#E3F1FF]',
        text: 'text-[#0F3A5C]',
        muted: 'text-[#0F3A5C]/70',
    },
    {
        tab: 'bg-[#D8E8C8]',
        body: 'bg-[#ECF4E4]',
        text: 'text-[#2A4020]',
        muted: 'text-[#2A4020]/70',
    },
    {
        tab: 'bg-[#FFE8B8]',
        body: 'bg-[#FFF4DC]',
        text: 'text-[#5C4010]',
        muted: 'text-[#5C4010]/70',
    },
    {
        tab: 'bg-[#D4F0F5]',
        body: 'bg-[#E8F7FA]',
        text: 'text-[#0F4550]',
        muted: 'text-[#0F4550]/70',
    },
    {
        tab: 'bg-[#F5D0C8]',
        body: 'bg-[#FAE8E4]',
        text: 'text-[#5C2820]',
        muted: 'text-[#5C2820]/70',
    },
] as const;

export function folderColor(colorIndex: number) {
    const palette = FOLDER_PALETTE;
    const index = Math.abs(colorIndex) % palette.length;

    return palette[index];
}

type Assignee = { id: number; name: string };

type MaterialFolderCardProps = {
    name: string;
    href: string;
    itemLabel: string;
    colorIndex: number;
    assignees?: Assignee[];
    onShare?: () => void;
    menu?: ReactNode;
    className?: string;
};

function initials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase();
}

export function MaterialFolderCard({
    name,
    href,
    itemLabel,
    colorIndex,
    assignees = [],
    onShare,
    menu,
    className,
}: MaterialFolderCardProps) {
    const colors = folderColor(colorIndex);

    return (
        <div className={cn('group relative', className)}>
            <div
                className={cn(
                    'absolute top-0 left-3 z-10 h-3 w-12 rounded-t-lg sm:w-16',
                    colors.tab,
                )}
                aria-hidden
            />
            <Link
                href={href}
                className={cn(
                    'relative mt-2 flex min-h-[104px] flex-col justify-between rounded-2xl p-3 shadow-sm transition-transform duration-200 sm:min-h-[140px] sm:p-4',
                    'hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                    colors.body,
                    colors.text,
                )}
            >
                <div
                    className={cn('pr-2 sm:pr-8', (onShare || menu) && 'pr-16')}
                >
                    <p className="line-clamp-2 text-sm leading-snug font-semibold tracking-tight sm:text-base">
                        {name}
                    </p>
                </div>

                <div className="mt-3 flex items-end justify-between gap-2 sm:mt-6">
                    <div className="flex -space-x-1.5">
                        {assignees.slice(0, 3).map((assignee) => (
                            <span
                                key={assignee.id}
                                title={assignee.name}
                                className="flex size-7 items-center justify-center rounded-full border-2 border-white/70 bg-white/50 text-[10px] font-semibold backdrop-blur-sm"
                            >
                                {initials(assignee.name)}
                            </span>
                        ))}
                        {assignees.length > 3 && (
                            <span className="flex size-7 items-center justify-center rounded-full border-2 border-white/70 bg-white/50 text-[10px] font-semibold backdrop-blur-sm">
                                +{assignees.length - 3}
                            </span>
                        )}
                    </div>
                    <p className={cn('text-xs font-medium', colors.muted)}>
                        {itemLabel}
                    </p>
                </div>
            </Link>

            {(onShare || menu) && (
                <div
                    className={cn(
                        'absolute top-4 right-2 z-20 flex items-center gap-0.5',
                        colors.text,
                    )}
                    onClick={(event) => event.stopPropagation()}
                    onKeyDown={(event) => event.stopPropagation()}
                >
                    {onShare && (
                        <button
                            type="button"
                            onClick={onShare}
                            className={cn(
                                'inline-flex size-8 items-center justify-center rounded-full bg-black/10 transition-colors hover:bg-black/20',
                                colors.text,
                            )}
                            aria-label="Share with batches"
                            title="Share with batches"
                        >
                            <Share2 className="size-4" strokeWidth={2.25} />
                        </button>
                    )}
                    {menu}
                </div>
            )}
        </div>
    );
}
