import { useState } from 'react';
import {
    Download,
    ExternalLink,
    FileText,
    FolderInput,
    Link2,
    Play,
    Share2,
    Trash2,
    Youtube,
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

export type MaterialKind = 'file' | 'youtube' | 'link';

const EXTENSION_STYLES: Record<
    string,
    { label: string; iconClass: string }
> = {
    pdf: {
        label: 'PDF',
        iconClass: 'bg-[#FFD6C8] text-[#8B3A22]',
    },
    doc: {
        label: 'Word',
        iconClass: 'bg-[#C8E4FF] text-[#0F3A5C]',
    },
    docx: {
        label: 'Word',
        iconClass: 'bg-[#C8E4FF] text-[#0F3A5C]',
    },
    xls: {
        label: 'Excel',
        iconClass: 'bg-[#D8E8C8] text-[#2A4020]',
    },
    xlsx: {
        label: 'Excel',
        iconClass: 'bg-[#D8E8C8] text-[#2A4020]',
    },
    youtube: {
        label: 'YouTube',
        iconClass: 'bg-[#FFD6C8] text-[#8B3A22]',
    },
    link: {
        label: 'Link',
        iconClass: 'bg-[#C8E4FF] text-[#0F3A5C]',
    },
};

function formatUploadedAt(value?: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();

    return `${day}.${month}.${year}`;
}

type MaterialFileCardProps = {
    name: string;
    extension: string;
    kind?: MaterialKind;
    url?: string | null;
    embedUrl?: string | null;
    uploadedAt?: string | null;
    categoryLabel?: string;
    downloadHref?: string | null;
    onShare?: () => void;
    onDelete?: () => void;
    onRename?: () => void;
    onMove?: () => void;
    className?: string;
};

export function MaterialFileCard({
    name,
    extension,
    kind = 'file',
    url,
    embedUrl,
    uploadedAt,
    categoryLabel,
    downloadHref,
    onShare,
    onDelete,
    onRename,
    onMove,
    className,
}: MaterialFileCardProps) {
    const [youtubeOpen, setYoutubeOpen] = useState(false);
    const normalized = extension.toLowerCase();
    const style = EXTENSION_STYLES[normalized] ?? {
        label: extension.toUpperCase(),
        iconClass: 'bg-muted text-muted-foreground',
    };

    const Icon =
        kind === 'youtube' ? Youtube : kind === 'link' ? Link2 : FileText;

    const badgeLabel =
        kind === 'youtube'
            ? 'YouTube'
            : kind === 'link'
              ? 'Link'
              : (categoryLabel ?? style.label);

    const extensionLabel =
        kind === 'youtube'
            ? 'video'
            : kind === 'link'
              ? 'web'
              : `.${normalized}`;

    return (
        <>
            <div
                className={cn(
                    'group grid grid-cols-[auto_minmax(0,1.4fr)_minmax(0,0.8fr)_auto_auto_auto] items-center gap-3 rounded-2xl px-3 py-3 transition-all duration-200',
                    'hover:bg-card hover:shadow-sm sm:gap-4 sm:px-4',
                    className,
                )}
            >
                <span
                    className={cn(
                        'flex size-10 shrink-0 items-center justify-center rounded-full',
                        style.iconClass,
                    )}
                >
                    <Icon className="size-4" />
                </span>

                {onRename ? (
                    <button
                        type="button"
                        className="min-w-0 truncate text-left text-sm font-medium hover:underline sm:text-base"
                        onClick={onRename}
                        title="Rename"
                    >
                        {name}
                    </button>
                ) : (
                    <p className="min-w-0 truncate text-sm font-medium sm:text-base">
                        {name}
                    </p>
                )}

                <p className="hidden truncate text-sm text-muted-foreground sm:block">
                    {badgeLabel}
                </p>

                <p className="hidden text-sm tabular-nums text-muted-foreground md:block">
                    {formatUploadedAt(uploadedAt)}
                </p>

                <p className="text-sm lowercase text-muted-foreground">
                    {extensionLabel}
                </p>

                <div className="flex items-center justify-end gap-1">
                    {onDelete && (
                        <button
                            type="button"
                            onClick={onDelete}
                            className="inline-flex size-8 items-center justify-center rounded-full text-destructive transition-colors hover:bg-destructive/10 hover:text-destructive"
                            aria-label="Delete"
                            title="Delete"
                        >
                            <Trash2 className="size-4" />
                        </button>
                    )}
                    {onMove && (
                        <button
                            type="button"
                            onClick={onMove}
                            className="inline-flex size-8 items-center justify-center rounded-full text-foreground/60 transition-colors hover:bg-muted hover:text-foreground"
                            aria-label="Move or copy"
                            title="Move or copy"
                        >
                            <FolderInput className="size-4" />
                        </button>
                    )}
                    {kind === 'file' && downloadHref && (
                        <a
                            href={downloadHref}
                            className="inline-flex size-8 items-center justify-center rounded-full text-foreground/60 transition-colors hover:bg-muted hover:text-foreground"
                            aria-label="Download file"
                            title="Download"
                        >
                            <Download className="size-4" />
                        </a>
                    )}
                    {kind === 'youtube' && embedUrl && (
                        <button
                            type="button"
                            onClick={() => setYoutubeOpen(true)}
                            className="inline-flex size-8 items-center justify-center rounded-full text-foreground/60 transition-colors hover:bg-muted hover:text-foreground"
                            aria-label="Play video"
                            title="Play"
                        >
                            <Play className="size-4" />
                        </button>
                    )}
                    {kind === 'link' && url && (
                        <a
                            href={url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex size-8 items-center justify-center rounded-full text-foreground/60 transition-colors hover:bg-muted hover:text-foreground"
                            aria-label="Open link"
                            title="Open"
                        >
                            <ExternalLink className="size-4" />
                        </a>
                    )}
                    {onShare && (
                        <button
                            type="button"
                            onClick={onShare}
                            className="inline-flex size-8 items-center justify-center rounded-full text-foreground/60 transition-colors hover:bg-muted hover:text-foreground"
                            aria-label="Share"
                            title="Share"
                        >
                            <Share2 className="size-4" />
                        </button>
                    )}
                </div>
            </div>

            {kind === 'youtube' && embedUrl && (
                <Dialog open={youtubeOpen} onOpenChange={setYoutubeOpen}>
                    <DialogContent className="max-w-3xl gap-0 overflow-hidden p-0 sm:max-w-3xl">
                        <DialogHeader className="px-4 pt-4 pb-2">
                            <DialogTitle className="truncate pr-8">
                                {name}
                            </DialogTitle>
                        </DialogHeader>
                        <div className="aspect-video w-full bg-black">
                            {youtubeOpen && (
                                <iframe
                                    title={name}
                                    src={embedUrl}
                                    className="size-full border-0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowFullScreen
                                    referrerPolicy="strict-origin-when-cross-origin"
                                />
                            )}
                        </div>
                    </DialogContent>
                </Dialog>
            )}
        </>
    );
}
