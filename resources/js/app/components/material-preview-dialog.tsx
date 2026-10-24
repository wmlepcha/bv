import {
    Bookmark,
    BookmarkCheck,
    Download,
    ExternalLink,
    FileText,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/responsive-dialog';
import { PdfPages } from '@app/components/pdf-pages';

export type PreviewMaterial = {
    name: string;
    extension: string;
    kind: 'file' | 'link';
    url?: string | null;
    previewUrl?: string | null;
    downloadHref?: string | null;
    saved: boolean;
};

const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
const AUDIO_EXTENSIONS = ['mp3', 'wav', 'm4a', 'ogg'];
const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov'];

export type MaterialType =
    'pdf' | 'image' | 'audio' | 'video' | 'link' | 'word' | 'excel' | 'file';

export function materialType(kind: string, extension: string): MaterialType {
    const ext = extension.toLowerCase();

    if (kind === 'link') {
        return 'link';
    }

    if (ext === 'pdf') {
        return 'pdf';
    }

    if (IMAGE_EXTENSIONS.includes(ext)) {
        return 'image';
    }

    if (AUDIO_EXTENSIONS.includes(ext)) {
        return 'audio';
    }

    if (VIDEO_EXTENSIONS.includes(ext)) {
        return 'video';
    }

    if (ext === 'doc' || ext === 'docx') {
        return 'word';
    }

    if (ext === 'xls' || ext === 'xlsx') {
        return 'excel';
    }

    return 'file';
}

/** One line telling the student what the material is and what tapping it does. */
export const TYPE_HINTS: Record<MaterialType, string> = {
    pdf: 'PDF · tap to read',
    image: 'Image · tap to view',
    audio: 'Audio · tap to listen',
    video: 'Video · tap to watch',
    link: 'Web link · opens a website',
    word: 'Word document · download to open',
    excel: 'Excel sheet · download to open',
    file: 'File · download to open',
};

function hostnameOf(url: string): string {
    try {
        return new URL(url).hostname.replace(/^www\./, '');
    } catch {
        return url;
    }
}

export function MaterialPreviewDialog({
    material,
    onClose,
    onToggleSaved,
}: {
    material: PreviewMaterial | null;
    onClose: () => void;
    onToggleSaved: () => void;
}) {
    const type = material
        ? materialType(material.kind, material.extension)
        : 'file';
    const src = material?.previewUrl ?? null;

    return (
        <Dialog
            open={material !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent
                phone="fullscreen"
                className="top-0 left-0 flex h-[100dvh] max-h-[100dvh] w-screen max-w-none translate-x-0 translate-y-0 flex-col gap-0 overflow-hidden rounded-none border-0 p-0 sm:top-[50%] sm:left-[50%] sm:h-[90vh] sm:max-w-3xl sm:translate-x-[-50%] sm:translate-y-[-50%] sm:rounded-2xl sm:border"
            >
                {material && (
                    <>
                        <DialogHeader className="relative border-b border-border/60 px-4 py-3 pr-14 text-left">
                            <DialogClose asChild>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="absolute top-2.5 right-3 size-10 rounded-full bg-foreground/10 sm:hidden"
                                    aria-label="Close"
                                >
                                    ✕
                                </Button>
                            </DialogClose>
                            <DialogTitle className="truncate text-base">
                                {material.name}
                            </DialogTitle>
                            <DialogDescription>
                                {TYPE_HINTS[type]}
                            </DialogDescription>
                        </DialogHeader>

                        <div className="min-h-0 flex-1 space-y-4 overflow-y-auto p-4">
                            {type === 'pdf' && src && (
                                <>
                                    <div className="lg:hidden">
                                        <PdfPages
                                            url={src}
                                            title={material.name}
                                        />
                                    </div>
                                    <iframe
                                        src={src}
                                        title={material.name}
                                        className="hidden h-[70vh] w-full rounded-xl border border-border/60 lg:block"
                                    />
                                </>
                            )}

                            {type === 'image' && src && (
                                <img
                                    src={src}
                                    alt={material.name}
                                    className="mx-auto max-h-[75vh] w-auto max-w-full rounded-xl object-contain"
                                />
                            )}

                            {type === 'audio' && src && (
                                <audio
                                    controls
                                    preload="metadata"
                                    className="w-full"
                                    src={src}
                                >
                                    Your browser does not support audio
                                    playback.
                                </audio>
                            )}

                            {type === 'video' && src && (
                                <video
                                    controls
                                    playsInline
                                    preload="metadata"
                                    className="mx-auto max-h-[70vh] w-full rounded-xl bg-black"
                                    src={src}
                                >
                                    Your browser does not support video
                                    playback.
                                </video>
                            )}

                            {type === 'link' && material.url && (
                                <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-border/60 p-8 text-center">
                                    <ExternalLink className="size-9 text-muted-foreground" />
                                    <p className="font-medium">
                                        {hostnameOf(material.url)}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Your trainer shared this website. It
                                        opens in a new tab, so you can come back
                                        here afterwards.
                                    </p>
                                    <Button
                                        asChild
                                        className="h-11 rounded-full px-6"
                                    >
                                        <a
                                            href={material.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            Open website
                                        </a>
                                    </Button>
                                </div>
                            )}

                            {(type === 'word' ||
                                type === 'excel' ||
                                type === 'file') && (
                                <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-border/60 p-8 text-center">
                                    <FileText className="size-9 text-muted-foreground" />
                                    <p className="text-sm text-muted-foreground">
                                        This kind of file can&apos;t be shown
                                        here. Download it and open it in{' '}
                                        {type === 'excel' ? 'Excel' : 'Word'} or
                                        your files app.
                                    </p>
                                    {material.downloadHref && (
                                        <Button
                                            asChild
                                            className="h-11 rounded-full px-6"
                                        >
                                            <a href={material.downloadHref}>
                                                Download
                                            </a>
                                        </Button>
                                    )}
                                </div>
                            )}
                        </div>

                        <DialogFooter className="flex-row gap-3 border-t border-border/60 px-4 py-3">
                            {material.downloadHref &&
                                type !== 'word' &&
                                type !== 'excel' &&
                                type !== 'file' && (
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="h-11 flex-1 rounded-full"
                                    >
                                        <a href={material.downloadHref}>
                                            <Download className="mr-1.5 size-4" />
                                            Download
                                        </a>
                                    </Button>
                                )}
                            <Button
                                type="button"
                                variant={
                                    material.saved ? 'secondary' : 'default'
                                }
                                className="h-11 flex-1 rounded-full"
                                onClick={onToggleSaved}
                            >
                                {material.saved ? (
                                    <BookmarkCheck className="mr-1.5 size-4" />
                                ) : (
                                    <Bookmark className="mr-1.5 size-4" />
                                )}
                                {material.saved
                                    ? 'Saved to My Learning'
                                    : 'Save to take notes'}
                            </Button>
                        </DialogFooter>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
