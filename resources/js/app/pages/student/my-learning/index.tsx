import { router } from '@inertiajs/react';
import {
    FileSpreadsheet,
    FileText,
    Link2,
    NotebookPen,
    Youtube,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/responsive-dialog';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import {
    destroy as destroyLearningItem,
    index as myLearningIndex,
} from '@/routes/student/my-learning';
import { update as updateLearningItemNote } from '@/routes/student/my-learning/note';
import { download as studyBankDownload } from '@/routes/student/study-bank/files';
import {
    extractPlaylistId,
    playlistVideoEmbedUrl,
} from '@app/components/material-file-card';
import type { PlaylistVideoItem } from '@app/components/material-file-card';
import { folderColor } from '@app/components/material-folder-card';
import type { StudyBankFile } from '@app/components/study-bank-contents';
import { PrivateSeoHead } from '@shared/components/seo-head';

type LearningItem = {
    id: number;
    note: string | null;
    added_at: string | null;
    material: StudyBankFile;
};

type Props = {
    items: LearningItem[];
    openItemId: number | null;
};

/** Matches the `note` validation rule in LearningController::updateNote. */
const MAX_NOTE_LENGTH = 5000;

function isPdfMaterial(material: StudyBankFile): boolean {
    return (
        (material.kind ?? 'file') === 'file' &&
        material.extension.toLowerCase() === 'pdf'
    );
}

function isVideoMaterial(material: StudyBankFile): boolean {
    return material.kind === 'youtube' || material.kind === 'youtube_playlist';
}

function MaterialGlyph({
    material,
    className,
}: {
    material: StudyBankFile;
    className?: string;
}) {
    if (isVideoMaterial(material)) {
        return <Youtube className={className} />;
    }

    if (material.kind === 'link') {
        return <Link2 className={className} />;
    }

    if (['xls', 'xlsx', 'csv'].includes(material.extension.toLowerCase())) {
        return <FileSpreadsheet className={className} />;
    }

    return <FileText className={className} />;
}

function materialKindLabel(material: StudyBankFile): string {
    if (material.kind === 'youtube') {
        return 'YouTube video';
    }

    if (material.kind === 'youtube_playlist') {
        return 'YouTube playlist';
    }

    if (material.kind === 'link') {
        return 'Web link';
    }

    return material.extension.toUpperCase();
}

type LearningItemGroup = {
    key: string;
    label: string;
    items: LearningItem[];
};

function dateLabel(dateKey: string): string {
    const date = new Date(`${dateKey}T00:00:00`);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    const isSameDay = (a: Date, b: Date) =>
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate();

    if (isSameDay(date, today)) {
        return 'Today';
    }

    if (isSameDay(date, yesterday)) {
        return 'Yesterday';
    }

    return date.toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year:
            date.getFullYear() === today.getFullYear() ? undefined : 'numeric',
    });
}

/** Groups items by the calendar day they were saved, newest day first. */
function groupItemsByDate(items: LearningItem[]): LearningItemGroup[] {
    const groups = new Map<string, LearningItem[]>();

    for (const item of items) {
        const key = item.added_at ? item.added_at.slice(0, 10) : 'unknown';
        const existing = groups.get(key);

        if (existing) {
            existing.push(item);
        } else {
            groups.set(key, [item]);
        }
    }

    return Array.from(groups.entries()).map(([key, groupItems]) => ({
        key,
        label: key === 'unknown' ? 'Earlier' : dateLabel(key),
        items: groupItems,
    }));
}

function LearningFolderCard({
    item,
    onOpen,
    compact = false,
}: {
    item: LearningItem;
    onOpen: () => void;
    compact?: boolean;
}) {
    const colors = folderColor(item.id);

    return (
        <div className="group relative">
            <div
                className={cn(
                    'absolute top-0 left-3 z-10 rounded-t-lg',
                    compact ? 'h-2.5 w-12' : 'h-3 w-16',
                    colors.tab,
                )}
                aria-hidden
            />
            <button
                type="button"
                onClick={onOpen}
                className={cn(
                    'relative mt-2 flex w-full flex-col justify-between rounded-2xl text-left shadow-sm transition-transform duration-200',
                    'hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                    compact ? 'min-h-[112px] p-3' : 'min-h-[150px] p-4',
                    colors.body,
                    colors.text,
                )}
            >
                <div className="flex items-start justify-between gap-2">
                    <span
                        className={cn(
                            'flex items-center justify-center rounded-xl bg-white/60',
                            compact ? 'size-8' : 'size-11',
                            colors.text,
                        )}
                    >
                        <MaterialGlyph
                            material={item.material}
                            className={compact ? 'size-4' : 'size-5'}
                        />
                    </span>
                    {item.note && (
                        <span
                            title="Has notes"
                            className={cn(
                                'flex items-center justify-center rounded-full bg-white/50',
                                compact ? 'size-5' : 'size-6',
                                colors.text,
                            )}
                        >
                            <NotebookPen
                                className={compact ? 'size-3' : 'size-3.5'}
                            />
                        </span>
                    )}
                </div>

                <div>
                    <p
                        className={cn(
                            'line-clamp-2 leading-snug font-semibold tracking-tight',
                            compact ? 'text-xs' : 'text-sm',
                        )}
                    >
                        {item.material.name}
                    </p>
                    <p
                        className={cn(
                            'mt-1 font-medium',
                            compact ? 'text-[11px]' : 'text-xs',
                            colors.muted,
                        )}
                    >
                        {materialKindLabel(item.material)}
                    </p>
                </div>
            </button>
        </div>
    );
}

function LearningItemModalContent({
    item,
    onClose,
}: {
    item: LearningItem;
    onClose: () => void;
}) {
    const [note, setNote] = useState(item.note ?? '');
    const [saving, setSaving] = useState(false);
    const isDirty = note !== (item.note ?? '');
    const material = item.material;
    const isPdf = isPdfMaterial(material);
    const isVideo = isVideoMaterial(material);
    const isPlaylist = material.kind === 'youtube_playlist';
    const playlistItemsUrl = isPlaylist
        ? `/student/study-bank/files/${material.id}/playlist-items`
        : null;
    const playlistId = isPlaylist ? extractPlaylistId(material.url) : null;

    const [playlistItems, setPlaylistItems] = useState<PlaylistVideoItem[]>(
        material.playlist_items ?? [],
    );
    const [selectedVideoId, setSelectedVideoId] = useState<string | null>(
        material.playlist_items?.[0]?.video_id ?? null,
    );
    const [playlistLoading, setPlaylistLoading] = useState(
        () => isPlaylist && (material.playlist_items?.length ?? 0) === 0,
    );
    const [playlistApiConfigured, setPlaylistApiConfigured] = useState(true);

    useEffect(() => {
        if (!isPlaylist || playlistItems.length > 0 || !playlistItemsUrl) {
            return;
        }

        let cancelled = false;

        fetch(playlistItemsUrl, {
            headers: { Accept: 'application/json' },
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
    }, [isPlaylist, playlistItemsUrl, playlistItems.length]);

    const activeEmbedUrl =
        isPlaylist && playlistId
            ? playlistVideoEmbedUrl(playlistId, selectedVideoId)
            : material.embed_url;

    const saveNote = () => {
        setSaving(true);

        router.patch(
            updateLearningItemNote.url(item.id),
            { note },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => router.flushAll(),
                onFinish: () => setSaving(false),
            },
        );
    };

    const remove = () => {
        router.delete(destroyLearningItem.url(item.id), {
            preserveScroll: true,
            onSuccess: () => {
                router.flushAll();
                onClose();
            },
        });
    };

    const notesPanel = (
        <div className="flex h-full min-h-[16rem] flex-col gap-2">
            <Label
                htmlFor={`learning-note-${item.id}`}
                className="text-xs font-medium tracking-wide text-muted-foreground uppercase"
            >
                Your notes
            </Label>
            <Textarea
                id={`learning-note-${item.id}`}
                value={note}
                onChange={(event) =>
                    setNote(event.target.value.slice(0, MAX_NOTE_LENGTH))
                }
                maxLength={MAX_NOTE_LENGTH}
                placeholder="Jot down what you want to remember about this…"
                className="flex-1 resize-none"
            />
            <div className="flex items-center justify-between">
                <p
                    className={cn(
                        'text-xs text-muted-foreground',
                        note.length >= MAX_NOTE_LENGTH && 'text-destructive',
                    )}
                >
                    {note.length.toLocaleString()} /{' '}
                    {MAX_NOTE_LENGTH.toLocaleString()} characters
                </p>
                <Button
                    type="button"
                    size="sm"
                    disabled={!isDirty || saving}
                    onClick={saveNote}
                >
                    {saving ? 'Saving…' : 'Save note'}
                </Button>
            </div>
        </div>
    );

    return (
        <DialogContent
            phone="fullscreen"
            className="top-0 left-0 flex h-[100dvh] max-h-[100dvh] w-screen max-w-none translate-x-0 translate-y-0 flex-col gap-0 overflow-hidden rounded-none border-0 p-0 sm:max-w-none"
        >
            <DialogHeader className="border-b border-border/60 px-4 py-3 pr-14 text-left sm:px-6 sm:py-4">
                <DialogTitle className="truncate text-base sm:text-lg">
                    {material.name}
                </DialogTitle>
                <DialogDescription>
                    {materialKindLabel(material)}
                    {material.folder_name ? ` · ${material.folder_name}` : ''}
                </DialogDescription>
            </DialogHeader>

            <div
                className={cn(
                    'flex-1 overflow-y-auto p-4 sm:p-6',
                    isPdf || isVideo
                        ? 'flex flex-col gap-4 lg:grid lg:grid-cols-2 lg:gap-6 lg:overflow-hidden'
                        : 'flex flex-col gap-4 sm:gap-6',
                )}
            >
                {isPdf && (
                    <div className="flex flex-col gap-4 lg:h-full lg:overflow-hidden">
                        {/* Inline PDF viewers are unreliable on mobile browsers, so phones get an "open" prompt instead. */}
                        <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border/60 p-8 text-center lg:hidden">
                            <FileText className="size-10 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">
                                PDFs preview best on a larger screen. Open it in
                                a new tab to read comfortably.
                            </p>
                            {material.preview_url && (
                                <Button asChild variant="outline" size="sm">
                                    <a
                                        href={material.preview_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Open document
                                    </a>
                                </Button>
                            )}
                        </div>
                        <div className="hidden overflow-hidden rounded-xl border border-border/60 lg:block lg:h-full">
                            {material.preview_url ? (
                                <iframe
                                    src={material.preview_url}
                                    title={material.name}
                                    className="size-full"
                                />
                            ) : (
                                <div className="flex h-full items-center justify-center text-sm text-muted-foreground">
                                    Preview unavailable.
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {isVideo && (
                    <div className="flex flex-col justify-center gap-3 lg:h-full lg:overflow-y-auto">
                        {isPlaylist && (
                            <div className="space-y-1.5">
                                <Label htmlFor={`playlist-video-${item.id}`}>
                                    Video
                                </Label>
                                <select
                                    id={`playlist-video-${item.id}`}
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
                                        playlistItems.map(
                                            (playlistItem, index) => (
                                                <option
                                                    key={playlistItem.video_id}
                                                    value={
                                                        playlistItem.video_id
                                                    }
                                                >
                                                    {index + 1}.{' '}
                                                    {playlistItem.title}
                                                </option>
                                            ),
                                        )
                                    )}
                                </select>
                                {!playlistLoading &&
                                    playlistItems.length === 0 && (
                                        <p className="text-xs text-muted-foreground">
                                            {playlistApiConfigured
                                                ? 'Could not load playlist videos. You can still play the playlist below.'
                                                : 'The playlist video list is not configured. You can still play the playlist below.'}
                                        </p>
                                    )}
                            </div>
                        )}

                        <div className="mx-auto aspect-video w-full max-w-xl overflow-hidden rounded-xl bg-black lg:max-w-none">
                            {activeEmbedUrl ? (
                                <iframe
                                    key={activeEmbedUrl}
                                    src={activeEmbedUrl}
                                    title={material.name}
                                    className="size-full border-0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowFullScreen
                                    referrerPolicy="strict-origin-when-cross-origin"
                                />
                            ) : (
                                <div className="flex h-full items-center justify-center text-sm text-white/70">
                                    Video unavailable.
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {!isPdf && !isVideo && (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border/60 p-10 text-center">
                        <MaterialGlyph
                            material={material}
                            className="size-10 text-muted-foreground"
                        />
                        <p className="text-sm text-muted-foreground">
                            {material.kind === 'link'
                                ? 'This is a web link.'
                                : `This ${material.extension.toUpperCase()} file can’t be previewed here.`}
                        </p>
                        {material.kind === 'link' && material.url ? (
                            <Button asChild variant="outline" size="sm">
                                <a
                                    href={material.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Open link
                                </a>
                            </Button>
                        ) : (
                            <Button asChild variant="outline" size="sm">
                                <a href={studyBankDownload.url(material.id)}>
                                    Download to view
                                </a>
                            </Button>
                        )}
                    </div>
                )}

                {isPdf || isVideo ? (
                    <div className="flex flex-1 flex-col lg:h-full">
                        {notesPanel}
                    </div>
                ) : (
                    notesPanel
                )}
            </div>

            <DialogFooter className="border-t border-border/60 px-4 py-3 sm:px-6 sm:py-4">
                <Button
                    type="button"
                    variant="ghost"
                    className="text-destructive hover:text-destructive"
                    onClick={remove}
                >
                    Remove from My Learning
                </Button>
            </DialogFooter>
        </DialogContent>
    );
}

function LearningItemGroupSection({
    group,
    onOpen,
}: {
    group: LearningItemGroup;
    onOpen: (itemId: number) => void;
}) {
    return (
        <section>
            <h2 className="mb-3 text-sm font-semibold text-muted-foreground">
                {group.label}{' '}
                <span className="font-normal">({group.items.length})</span>
            </h2>

            {/* Mobile: a compact, horizontally scrollable row per date. */}
            <div className="-mx-2 flex gap-3 overflow-x-auto px-2 pb-1 sm:hidden">
                {group.items.map((item) => (
                    <div key={item.id} className="w-44 shrink-0">
                        <LearningFolderCard
                            item={item}
                            onOpen={() => onOpen(item.id)}
                            compact
                        />
                    </div>
                ))}
            </div>

            {/* Larger screens: a regular grid. */}
            <div className="hidden gap-4 sm:grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                {group.items.map((item) => (
                    <LearningFolderCard
                        key={item.id}
                        item={item}
                        onOpen={() => onOpen(item.id)}
                    />
                ))}
            </div>
        </section>
    );
}

export default function StudentMyLearningIndex({ items, openItemId }: Props) {
    const [activeItemId, setActiveItemId] = useState<number | null>(openItemId);
    const isEmpty = items.length === 0;
    const activeItem = items.find((item) => item.id === activeItemId) ?? null;
    const groups = groupItemsByDate(items);

    return (
        <>
            <PrivateSeoHead title="My Learning" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                        My Learning
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Materials you&apos;ve saved from the Study Bank, with
                        your own notes.
                    </p>
                </div>

                {isEmpty ? (
                    <div className="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        Nothing saved yet. Open the Study Bank and tap the
                        bookmark icon on any material to add it here.
                    </div>
                ) : (
                    <div className="flex flex-col gap-8">
                        {groups.map((group) => (
                            <LearningItemGroupSection
                                key={group.key}
                                group={group}
                                onOpen={setActiveItemId}
                            />
                        ))}
                    </div>
                )}
            </div>

            <Dialog
                open={activeItem !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setActiveItemId(null);
                    }
                }}
            >
                {activeItem && (
                    <LearningItemModalContent
                        key={activeItem.id}
                        item={activeItem}
                        onClose={() => setActiveItemId(null)}
                    />
                )}
            </Dialog>
        </>
    );
}

StudentMyLearningIndex.layout = {
    breadcrumbs: [
        {
            title: 'My Learning',
            href: myLearningIndex(),
        },
    ],
};
