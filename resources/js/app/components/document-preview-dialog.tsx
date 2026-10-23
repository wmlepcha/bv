import { Download, ExternalLink, FileQuestion, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/responsive-dialog';

export type PreviewTarget = {
    title: string;
    fileName: string | null;
    viewUrl: string;
    downloadUrl: string | null;
};

type Kind = 'pdf' | 'image' | 'other';

function kindOf(fileName: string | null): Kind {
    const extension = fileName?.split('.').pop()?.toLowerCase();

    if (extension === 'pdf') {
        return 'pdf';
    }

    if (extension === 'jpg' || extension === 'jpeg' || extension === 'png') {
        return 'image';
    }

    return 'other';
}

/** Phone browsers cannot draw a PDF inside a page, so they get a button to open it natively. */
function canEmbedPdf(): boolean {
    return (
        typeof navigator === 'undefined' ||
        (navigator as Navigator & { pdfViewerEnabled?: boolean })
            .pdfViewerEnabled !== false
    );
}

/** A centred modal on desktop and a full-screen sheet on phones, for looking at a document without leaving the page. */
export default function DocumentPreviewDialog({
    target,
    onClose,
}: {
    target: PreviewTarget | null;
    onClose: () => void;
}) {
    const kind = kindOf(target?.fileName ?? null);
    const showPdf = kind === 'pdf' && canEmbedPdf();

    return (
        <Dialog
            open={target !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent
                phone="fullscreen"
                className="flex h-[88vh] flex-col gap-0 overflow-hidden p-0 sm:max-w-4xl"
            >
                <div className="flex items-start justify-between gap-3 border-b border-border/60 px-5 py-4 pr-12">
                    <div className="min-w-0">
                        <DialogTitle className="truncate">
                            {target?.title}
                        </DialogTitle>
                        <DialogDescription className="truncate">
                            {target?.fileName ?? 'Document'}
                        </DialogDescription>
                    </div>
                    <div className="flex shrink-0 gap-2">
                        {target && kind !== 'other' && (
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="rounded-full"
                            >
                                <a
                                    href={target.viewUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <ExternalLink className="size-4" />
                                    <span className="max-sm:sr-only">Open</span>
                                </a>
                            </Button>
                        )}
                        {target?.downloadUrl && (
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="rounded-full"
                            >
                                <a href={target.downloadUrl}>
                                    <Download className="size-4" />
                                    <span className="max-sm:sr-only">
                                        Download
                                    </span>
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="flex min-h-0 flex-1 items-center justify-center bg-muted/40">
                    {target && kind === 'image' && (
                        <img
                            src={target.viewUrl}
                            alt={target.title}
                            className="max-h-full max-w-full object-contain"
                        />
                    )}
                    {target && showPdf && (
                        <iframe
                            src={target.viewUrl}
                            title={target.title}
                            className="size-full border-0"
                        />
                    )}
                    {target && !showPdf && kind !== 'image' && (
                        <div className="flex max-w-xs flex-col items-center gap-3 p-6 text-center">
                            <FileQuestion className="size-10 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">
                                {kind === 'pdf'
                                    ? 'Your phone cannot show PDFs inside the page. Open it to read it in your browser.'
                                    : 'This file type cannot be previewed here. Download it to open it.'}
                            </p>
                            {kind === 'pdf' && (
                                <Button asChild className="rounded-full">
                                    <a
                                        href={target.viewUrl}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Open PDF
                                    </a>
                                </Button>
                            )}
                        </div>
                    )}
                </div>

                <div className="border-t border-border/60 bg-background p-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] sm:hidden">
                    <DialogClose asChild>
                        <Button
                            variant="outline"
                            className="h-11 w-full rounded-full"
                        >
                            <X className="size-4" />
                            Close
                        </Button>
                    </DialogClose>
                </div>
            </DialogContent>
        </Dialog>
    );
}
