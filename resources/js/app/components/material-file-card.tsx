import {
    Bookmark,
    BookmarkCheck,
    ChevronDown,
    ChevronUp,
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
import { useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/responsive-dialog';
import { cn } from '@/lib/utils';

export type MaterialKind = 'file' | 'youtube' | 'youtube_playlist' | 'link';

export type PlaylistVideoItem = {
    video_id: string;
    title: string;
};

const EXTENSION_STYLES: Record<string, { label: string; iconClass: string }> = {
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
    youtube_playlist: {
        label: 'YouTube playlist',
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

export function extractPlaylistId(url?: string | null): string | null {
    if (!url) {
        return null;
    }

    try {
        const parsed = new URL(url);
        const list = parsed.searchParams.get('list');

        return list && list.length > 0 ? list : null;
    } catch {
        return null;
    }
}

export function playlistVideoEmbedUrl(
    playlistId: string,
    videoId: string | null,
): string {
    if (videoId) {
        return `https://www.youtube-nocookie.com/embed/${videoId}?list=${playlistId}`;
    }

    return `https://www.youtube-nocookie.com/embed/videoseries?list=${playlistId}`;
}

type MaterialFileCardProps = {
    name: string;
    extension: string;
    kind?: MaterialKind;
    url?: string | null;
    embedUrl?: string | null;
    playlistItems?: PlaylistVideoItem[] | null;
    playlistItemsUrl?: string | null;
    uploadedAt?: string | null;
    categoryLabel?: string;
    downloadHref?: string | null;
    onShare?: () => void;
    onDelete?: () => void;
    onRename?: () => void;
    onMove?: () => void;
    isSavedToLearning?: boolean;
    onToggleLearning?: () => void;
    className?: string;
};

export function MaterialFileCard({
    name,
    extension,
    kind = 'file',
    url,
    embedUrl,
    playlistItems: initialPlaylistItems = null,
    playlistItemsUrl = null,
    uploadedAt,
    categoryLabel,
    downloadHref,
    onShare,
    onDelete,
    onRename,
    onMove,
    isSavedToLearning,
    onToggleLearning,
    className,
}: MaterialFileCardProps) {
    const [youtubeOpen, setYoutubeOpen] = useState(false);
    const [expanded, setExpanded] = useState(false);
    const [playlistItems, setPlaylistItems] = useState<PlaylistVideoItem[]>(
        initialPlaylistItems ?? [],
    );
    const [selectedVideoId, setSelectedVideoId] = useState<string | null>(
        initialPlaylistItems?.[0]?.video_id ?? null,
    );
    const [playlistLoading, setPlaylistLoading] = useState(false);
    const [playlistApiConfigured, setPlaylistApiConfigured] = useState(true);
    const isYoutubeMaterial = kind === 'youtube' || kind === 'youtube_playlist';
    const playlistId = extractPlaylistId(url);
    const normalized = extension.toLowerCase();
    const style = EXTENSION_STYLES[normalized] ?? {
        label: extension.toUpperCase(),
        iconClass: 'bg-muted text-muted-foreground',
    };

    const Icon = isYoutubeMaterial
        ? Youtube
        : kind === 'link'
          ? Link2
          : FileText;

    const badgeLabel =
        kind === 'youtube'
            ? 'YouTube'
            : kind === 'youtube_playlist'
              ? 'YouTube playlist'
              : kind === 'link'
                ? 'Link'
                : (categoryLabel ?? style.label);

    const extensionLabel =
        kind === 'youtube'
            ? 'video'
            : kind === 'youtube_playlist'
              ? playlistItems.length > 0
                  ? `playlist · ${playlistItems.length} video${playlistItems.length === 1 ? '' : 's'}`
                  : 'playlist'
              : kind === 'link'
                ? 'web'
                : `.${normalized}`;

    useEffect(() => {
        setPlaylistItems(initialPlaylistItems ?? []);
        setSelectedVideoId(initialPlaylistItems?.[0]?.video_id ?? null);
    }, [initialPlaylistItems]);

    useEffect(() => {
        if (
            !(youtubeOpen || expanded) ||
            kind !== 'youtube_playlist' ||
            playlistItems.length > 0 ||
            !playlistItemsUrl
        ) {
            return;
        }

        let cancelled = false;

        setPlaylistLoading(true);

        fetch(playlistItemsUrl, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error('Failed to load playlist items');
                }

                return response.json() as Promise<{
                    items?: PlaylistVideoItem[];
                    api_configured?: boolean;
                }>;
            })
            .then((payload) => {
                if (cancelled) {
                    return;
                }

                const items = Array.isArray(payload.items) ? payload.items : [];
                setPlaylistItems(items);
                setSelectedVideoId(items[0]?.video_id ?? null);
                setPlaylistApiConfigured(payload.api_configured !== false);
            })
            .catch(() => {
                if (!cancelled) {
                    setPlaylistApiConfigured(false);
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setPlaylistLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [youtubeOpen, expanded, kind, playlistItems.length, playlistItemsUrl]);

    const activeEmbedUrl =
        kind === 'youtube_playlist' && playlistId
            ? playlistVideoEmbedUrl(playlistId, selectedVideoId)
            : embedUrl;

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

                <p className="hidden text-sm text-muted-foreground tabular-nums md:block">
                    {formatUploadedAt(uploadedAt)}
                </p>

                <p className="text-sm text-muted-foreground lowercase">
                    {extensionLabel}
                </p>

                <div className="flex items-center justify-end gap-1">
                    {onToggleLearning && (
                        <button
                            type="button"
                            onClick={onToggleLearning}
                            className={cn(
                                'inline-flex size-8 items-center justify-center rounded-full transition-colors hover:bg-muted',
                                isSavedToLearning
                                    ? 'text-primary hover:text-primary'
                                    : 'text-foreground/60 hover:text-foreground',
                            )}
                            aria-label={
                                isSavedToLearning
                                    ? 'Remove from My Learning'
                                    : 'Add to My Learning'
                            }
                            title={
                                isSavedToLearning
                                    ? 'Remove from My Learning'
                                    : 'Add to My Learning'
                            }
                        >
                            {isSavedToLearning ? (
                                <BookmarkCheck className="size-4" />
                            ) : (
                                <Bookmark className="size-4" />
                            )}
                        </button>
                    )}
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
                    {isYoutubeMaterial && embedUrl && (
                        <button
                            type="button"
                            onClick={() => setYoutubeOpen(true)}
                            className="inline-flex size-8 items-center justify-center rounded-full text-foreground/60 transition-colors hover:bg-muted hover:text-foreground"
                            aria-label={
                                kind === 'youtube_playlist'
                                    ? 'Play playlist'
                                    : 'Play video'
                            }
                            title="Play"
                        >
                            <Play className="size-4" />
                        </button>
                    )}
                    {kind === 'youtube_playlist' && playlistItemsUrl && (
                        <button
                            type="button"
                            onClick={() => setExpanded((previous) => !previous)}
                            className="inline-flex size-8 items-center justify-center rounded-full text-foreground/60 transition-colors hover:bg-muted hover:text-foreground"
                            aria-label={
                                expanded ? 'Hide videos' : 'Show videos'
                            }
                            title={expanded ? 'Hide videos' : 'Show videos'}
                        >
                            {expanded ? (
                                <ChevronUp className="size-4" />
                            ) : (
                                <ChevronDown className="size-4" />
                            )}
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

            {kind === 'youtube_playlist' && expanded && (
                <div className="mt-1 space-y-0.5 rounded-xl border border-border/50 bg-background/60 px-2 py-2">
                    {playlistLoading && playlistItems.length === 0 && (
                        <p className="px-2 py-1 text-xs text-muted-foreground">
                            Loading videos…
                        </p>
                    )}
                    {!playlistLoading && playlistItems.length === 0 && (
                        <p className="px-2 py-1 text-xs text-muted-foreground">
                            {playlistApiConfigured
                                ? 'Could not load playlist videos.'
                                : 'Add YOUTUBE_DATA_API_KEY to load the video list.'}
                        </p>
                    )}
                    {playlistItems.map((item, index) => (
                        <button
                            key={item.video_id}
                            type="button"
                            onClick={() => {
                                setSelectedVideoId(item.video_id);
                                setYoutubeOpen(true);
                            }}
                            className="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-left text-sm transition-colors hover:bg-muted"
                        >
                            <span className="w-6 shrink-0 text-xs text-muted-foreground tabular-nums">
                                {index + 1}.
                            </span>
                            <span className="min-w-0 flex-1 truncate">
                                {item.title}
                            </span>
                        </button>
                    ))}
                </div>
            )}

            {isYoutubeMaterial && activeEmbedUrl && (
                <Dialog open={youtubeOpen} onOpenChange={setYoutubeOpen}>
                    <DialogContent
                        phone="fullscreen"
                        className="max-w-3xl gap-0 overflow-hidden p-0 sm:max-w-3xl"
                    >
                        <DialogHeader className="px-4 pt-4 pb-2">
                            <DialogTitle className="truncate pr-8">
                                {name}
                            </DialogTitle>
                        </DialogHeader>
                        {kind === 'youtube_playlist' && (
                            <div className="space-y-1.5 border-b border-border/60 px-4 pb-3">
                                <Label htmlFor={`playlist-video-${name}`}>
                                    Video
                                </Label>
                                <select
                                    id={`playlist-video-${name}`}
                                    className="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm"
                                    value={selectedVideoId ?? ''}
                                    disabled={
                                        playlistLoading ||
                                        playlistItems.length === 0
                                    }
                                    onChange={(event) =>
                                        setSelectedVideoId(
                                            event.target.value || null,
                                        )
                                    }
                                >
                                    {playlistItems.length === 0 ? (
                                        <option value="">
                                            {playlistLoading
                                                ? 'Loading videos…'
                                                : 'No videos available'}
                                        </option>
                                    ) : (
                                        playlistItems.map((item) => (
                                            <option
                                                key={item.video_id}
                                                value={item.video_id}
                                            >
                                                {item.title}
                                            </option>
                                        ))
                                    )}
                                </select>
                                {!playlistLoading &&
                                    playlistItems.length === 0 && (
                                        <p className="text-xs text-muted-foreground">
                                            {playlistApiConfigured
                                                ? 'Could not load playlist videos. You can still play the playlist below.'
                                                : 'Add YOUTUBE_DATA_API_KEY to load the video list.'}
                                        </p>
                                    )}
                            </div>
                        )}
                        <div className="aspect-video w-full bg-black">
                            {youtubeOpen && (
                                <iframe
                                    key={activeEmbedUrl}
                                    title={name}
                                    src={activeEmbedUrl}
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
