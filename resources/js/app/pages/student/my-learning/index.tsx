import { router } from '@inertiajs/react';
import {
    Download,
    FileSpreadsheet,
    FileText,
    Link2,
    NotebookPen,
    X,
    Youtube,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogClose,
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
import { insertAtCursor } from '@app/components/assignments/german-char-toolbar';
import {
    extractPlaylistId,
    playlistVideoEmbedUrl,
} from '@app/components/material-file-card';
import type { PlaylistVideoItem } from '@app/components/material-file-card';
import { folderColor } from '@app/components/material-folder-card';
import { PdfPages } from '@app/components/pdf-pages';
import { PlaylistVideoSelect } from '@app/components/playlist-video-select';
import type { StudyBankFile } from '@app/components/study-bank-contents';
import {
    YoutubeDescription,
    YoutubeEmbed,
} from '@app/components/youtube-embed';
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

const AUDIO_EXTENSIONS = ['mp3', 'wav', 'm4a', 'ogg'];
const VIDEO_FILE_EXTENSIONS = ['mp4', 'webm', 'mov'];

function isAudioMaterial(material: StudyBankFile): boolean {
    return (
        (material.kind ?? 'file') === 'file' &&
        AUDIO_EXTENSIONS.includes(material.extension.toLowerCase())
    );
}

function isImageMaterial(material: StudyBankFile): boolean {
    return (
        (material.kind ?? 'file') === 'file' &&
        ['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(
            material.extension.toLowerCase(),
        )
    );
}

function isVideoFileMaterial(material: StudyBankFile): boolean {
    return (
        (material.kind ?? 'file') === 'file' &&
        VIDEO_FILE_EXTENSIONS.includes(material.extension.toLowerCase())
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
    const [pane, setPane] = useState(0);
    const scrollerRef = useRef<HTMLDivElement>(null);
    const noteRef = useRef<HTMLTextAreaElement>(null);
    const isDirty = note !== (item.note ?? '');
    const material = item.material;
    const isPdf = isPdfMaterial(material);
    const isVideo = isVideoMaterial(material);
    const isAudio = isAudioMaterial(material);
    const isVideoFile = isVideoFileMaterial(material);
    const isImage = isImageMaterial(material);
    const isPlayable = isAudio || isVideoFile;
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

    const goToPane = (index: number) => {
        const el = scrollerRef.current;

        el?.scrollTo({ left: index * el.clientWidth, behavior: 'smooth' });
    };

    const insertChar = (char: string) => {
        const el = noteRef.current;
        const start = el?.selectionStart ?? note.length;
        const end = el?.selectionEnd ?? note.length;
        const { value, cursor } = insertAtCursor(note, char, start, end);

        setNote(value.slice(0, MAX_NOTE_LENGTH));
        requestAnimationFrame(() => {
            el?.focus();
            el?.setSelectionRange(cursor, cursor);
        });
    };

    const downloadNotes = () => {
        const text = `${material.name}\n${materialKindLabel(material)}\n\n${note}\n`;
        const link = document.createElement('a');
        link.href = URL.createObjectURL(
            new Blob([text], { type: 'text/plain;charset=utf-8' }),
        );
        link.download = `${material.name.replace(/[^\p{L}\p{N}]+/gu, '-')}-notes.txt`;
        link.click();
        URL.revokeObjectURL(link.href);
    };

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
                Your private notes
            </Label>
            <p className="text-sm text-muted-foreground">
                Only you can see these. Write new words, grammar rules or
                questions to ask your trainer.
            </p>
            <div className="flex items-center justify-between gap-2">
                <p className="text-xs text-muted-foreground">
                    Need a German letter? Tap to add it.
                </p>
            </div>
            <div className="flex flex-wrap gap-1.5">
                {['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'].map((char) => (
                    <button
                        key={char}
                        type="button"
                        onMouseDown={(event) => event.preventDefault()}
                        onClick={() => insertChar(char)}
                        className="h-10 min-w-10 rounded-xl border border-border bg-card px-2 font-serif text-lg active:scale-95 active:bg-primary/15"
                    >
                        {char}
                    </button>
                ))}
            </div>
            <Textarea
                ref={noteRef}
                id={`learning-note-${item.id}`}
                value={note}
                onChange={(event) =>
                    setNote(event.target.value.slice(0, MAX_NOTE_LENGTH))
                }
                maxLength={MAX_NOTE_LENGTH}
                placeholder="For example: der Tisch = table. Nebensatz: the verb goes to the end."
                className="min-h-[40dvh] flex-1 resize-none text-base lg:min-h-0"
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
                <div className="flex items-center gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        disabled={note.trim() === ''}
                        onClick={downloadNotes}
                    >
                        <Download className="mr-1.5 size-4" />
                        Download
                    </Button>
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
        </div>
    );

    return (
        <DialogContent
            phone="fullscreen"
            className="top-0 left-0 flex h-[100dvh] max-h-[100dvh] w-screen max-w-none translate-x-0 translate-y-0 flex-col gap-0 overflow-hidden rounded-none border-0 p-0 sm:max-w-none"
        >
            <DialogHeader className="relative border-b border-border/60 px-4 py-3 pr-14 text-left sm:px-6 sm:py-4">
                {/* Phones get a visible close button; desktop dialogs already have one. */}
                <DialogClose asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="absolute top-2.5 right-3 size-10 rounded-full bg-foreground/10 sm:hidden"
                        aria-label="Close"
                    >
                        <X className="size-5" />
                    </Button>
                </DialogClose>
                <DialogTitle className="truncate text-base sm:text-lg">
                    {material.name}
                </DialogTitle>
                <DialogDescription>
                    {materialKindLabel(material)}
                    {material.folder_name ? ` · ${material.folder_name}` : ''}
                </DialogDescription>
            </DialogHeader>

            {/* Phones: swipe between the material and your notes. */}
            <div
                role="tablist"
                aria-label="Material or notes"
                className="relative mx-4 mt-3 grid shrink-0 grid-cols-2 rounded-xl bg-muted p-1 lg:hidden"
            >
                <span
                    aria-hidden
                    className="absolute inset-y-1 left-1 w-[calc((100%-0.5rem)/2)] rounded-lg bg-card shadow-sm transition-transform duration-300 ease-out"
                    style={{ transform: `translateX(${pane * 100}%)` }}
                />
                {['Material', 'My notes'].map((label, index) => (
                    <button
                        key={label}
                        type="button"
                        role="tab"
                        aria-selected={pane === index}
                        onClick={() => goToPane(index)}
                        className={cn(
                            'relative z-10 h-10 rounded-lg text-sm font-semibold transition-colors',
                            pane === index
                                ? 'text-foreground'
                                : 'text-muted-foreground',
                        )}
                    >
                        {label}
                        {index === 1 && note.trim() !== '' && (
                            <span className="ml-1.5 inline-block size-1.5 rounded-full bg-primary align-middle" />
                        )}
                    </button>
                ))}
            </div>

            <div
                ref={scrollerRef}
                onScroll={(event) => {
                    const el = event.currentTarget;
                    setPane(
                        Math.round(el.scrollLeft / Math.max(el.clientWidth, 1)),
                    );
                }}
                className="flex min-h-0 flex-1 snap-x snap-mandatory scrollbar-none overflow-x-auto overscroll-x-contain lg:grid lg:grid-cols-2 lg:gap-6 lg:overflow-hidden lg:p-6"
            >
                <div className="flex w-full shrink-0 snap-center flex-col gap-4 overflow-y-auto p-4 sm:p-6 lg:h-full lg:overflow-y-auto lg:p-0">
                    {isPdf && (
                        <div className="flex flex-col gap-4 lg:h-full lg:overflow-hidden">
                            <div className="lg:hidden">
                                {material.preview_url ? (
                                    <PdfPages
                                        url={material.preview_url}
                                        title={material.name}
                                    />
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        Preview unavailable.
                                    </p>
                                )}
                                <p className="mt-3 text-center text-xs text-muted-foreground">
                                    Swipe left or tap “My notes” to write your
                                    notes.
                                </p>
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
                                    <Label
                                        htmlFor={`playlist-video-${item.id}`}
                                    >
                                        Video
                                    </Label>
                                    <PlaylistVideoSelect
                                        items={playlistItems}
                                        value={selectedVideoId}
                                        onChange={setSelectedVideoId}
                                        loading={playlistLoading}
                                    />
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

                            {activeEmbedUrl ? (
                                <>
                                    <YoutubeEmbed
                                        src={activeEmbedUrl}
                                        title={material.name}
                                        className="mx-auto max-w-xl overflow-hidden rounded-xl lg:max-w-none"
                                    />
                                    <div className="-mx-4">
                                        <YoutubeDescription
                                            title={material.name}
                                            url={material.url}
                                        />
                                    </div>
                                </>
                            ) : (
                                <div className="flex aspect-video items-center justify-center rounded-xl bg-black text-sm text-white/70">
                                    Video unavailable.
                                </div>
                            )}
                        </div>
                    )}

                    {isPlayable && material.preview_url && (
                        <div className="overflow-hidden rounded-xl border border-border/60 bg-black/5 p-3 dark:bg-white/5">
                            {isAudio ? (
                                <audio
                                    key={material.preview_url}
                                    controls
                                    preload="metadata"
                                    className="w-full"
                                    src={material.preview_url}
                                >
                                    Your browser does not support audio
                                    playback.
                                </audio>
                            ) : (
                                <video
                                    key={material.preview_url}
                                    controls
                                    playsInline
                                    preload="metadata"
                                    className="mx-auto max-h-[60vh] w-full rounded-lg bg-black"
                                    src={material.preview_url}
                                >
                                    Your browser does not support video
                                    playback.
                                </video>
                            )}
                        </div>
                    )}

                    {isImage && material.preview_url && (
                        <div className="overflow-hidden rounded-xl border border-border/60 p-2">
                            <img
                                src={material.preview_url}
                                alt={material.name}
                                className="mx-auto max-h-[70vh] w-auto max-w-full object-contain"
                            />
                        </div>
                    )}

                    {!isPdf && !isVideo && !isPlayable && !isImage && (
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
                                    <a
                                        href={studyBankDownload.url(
                                            material.id,
                                        )}
                                    >
                                        Download to view
                                    </a>
                                </Button>
                            )}
                        </div>
                    )}
                </div>

                <div className="flex w-full shrink-0 snap-center flex-col overflow-y-auto p-4 sm:p-6 lg:h-full lg:p-0">
                    {notesPanel}
                </div>
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
