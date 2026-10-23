import {
    ChevronRight,
    ExternalLink,
    FileText,
    Image as ImageIcon,
} from 'lucide-react';
import { useState } from 'react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/responsive-sheet';
import { useMediaQuery } from '@/hooks/use-media-query';
import { cn } from '@/lib/utils';
import { collectDocuments, previewModeFor } from '@app/lib/cv-documents';
import type { DocumentItem } from '@app/lib/cv-documents';
import type { CvProfile } from '@app/types/cv';

function iconFor(doc: DocumentItem) {
    if (doc.kind === 'link') {
        return ExternalLink;
    }

    return previewModeFor(doc) === 'image' ? ImageIcon : FileText;
}

function typeLabel(doc: DocumentItem): string {
    if (doc.kind === 'link') {
        return 'Link';
    }

    return previewModeFor(doc) === 'image' ? 'Image' : 'PDF';
}

function Preview({ doc, className }: { doc: DocumentItem; className: string }) {
    const mode = previewModeFor(doc);

    if (mode === 'image') {
        return (
            <div
                className={cn(
                    'flex items-center justify-center bg-muted/30 p-2',
                    className,
                )}
            >
                <img
                    src={doc.href}
                    alt={doc.label}
                    className="max-h-full w-full object-contain"
                />
            </div>
        );
    }

    if (mode === 'pdf') {
        return (
            <iframe
                src={`${doc.href.split('#')[0]}#navpanes=0&view=FitH`}
                title={doc.label}
                className={cn('w-full bg-white', className)}
            />
        );
    }

    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-2 p-8 text-center text-sm text-muted-foreground',
                className,
            )}
        >
            <p>External links can't be previewed here.</p>
            <a
                href={doc.href}
                target="_blank"
                rel="noreferrer"
                className="break-all text-foreground underline"
            >
                {doc.href}
            </a>
        </div>
    );
}

export default function CandidateDocumentsViewer({ cv }: { cv: CvProfile }) {
    const documents = collectDocuments(cv);
    const isDesktop = useMediaQuery('(min-width: 1024px)');
    const [selectedHref, setSelectedHref] = useState<string | null>(null);

    if (documents.length === 0) {
        return (
            <div className="rounded-3xl border border-dashed border-border px-6 py-10 text-center text-sm text-muted-foreground">
                No documents or links uploaded yet.
            </div>
        );
    }

    const selected =
        documents.find((doc) => doc.href === selectedHref) ??
        (isDesktop
            ? (documents.find((doc) => previewModeFor(doc)) ?? documents[0])
            : null);

    if (!isDesktop) {
        return (
            <>
                <p className="mb-3 px-1 text-sm text-muted-foreground">
                    {documents.length}{' '}
                    {documents.length === 1 ? 'file' : 'files'} and links
                </p>
                <ul className="overflow-hidden rounded-3xl border border-border bg-card/40">
                    {documents.map((doc) => {
                        const Icon = iconFor(doc);
                        const content = (
                            <>
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-cool/15 text-brand-cool">
                                    <Icon className="size-5" />
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-medium">
                                        {doc.label}
                                    </span>
                                    <span className="block text-xs text-muted-foreground">
                                        {typeLabel(doc)}
                                    </span>
                                </span>
                                <ChevronRight className="size-5 shrink-0 text-muted-foreground/60" />
                            </>
                        );
                        const rowClass =
                            'flex w-full items-center gap-3 border-b border-border/60 px-4 py-3 text-left transition-colors last:border-b-0 active:bg-muted';

                        return (
                            <li key={`${doc.label}-${doc.href}`}>
                                {doc.kind === 'link' ? (
                                    <a
                                        href={doc.href}
                                        target="_blank"
                                        rel="noreferrer"
                                        className={rowClass}
                                    >
                                        {content}
                                    </a>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setSelectedHref(doc.href)
                                        }
                                        className={rowClass}
                                    >
                                        {content}
                                    </button>
                                )}
                            </li>
                        );
                    })}
                </ul>

                <Sheet
                    open={selected !== null}
                    onOpenChange={(open) => !open && setSelectedHref(null)}
                >
                    <SheetContent
                        side="bottom"
                        className="h-[92svh] gap-0 rounded-t-3xl p-0 pb-[env(safe-area-inset-bottom)]"
                    >
                        <SheetHeader className="border-b border-border/60 p-4 pr-12">
                            <SheetTitle className="truncate text-base">
                                {selected?.label}
                            </SheetTitle>
                            <SheetDescription className="sr-only">
                                Document preview
                            </SheetDescription>
                            {selected && (
                                <a
                                    href={selected.href}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex w-fit items-center gap-1.5 text-sm font-medium text-primary"
                                >
                                    <ExternalLink className="size-4" />
                                    Open full screen
                                </a>
                            )}
                        </SheetHeader>
                        {selected && (
                            <Preview
                                doc={selected}
                                className="min-h-0 flex-1"
                            />
                        )}
                    </SheetContent>
                </Sheet>
            </>
        );
    }

    return (
        <div className="grid gap-4 lg:grid-cols-[300px_1fr]">
            <ul className="flex max-h-[75vh] flex-col gap-2 overflow-y-auto rounded-3xl border border-border bg-card/40 p-3">
                {documents.map((doc) => {
                    const Icon = iconFor(doc);

                    return (
                        <li key={`${doc.label}-${doc.href}`}>
                            <button
                                type="button"
                                onClick={() => setSelectedHref(doc.href)}
                                className={cn(
                                    'flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left text-sm transition-colors',
                                    doc.href === selected?.href
                                        ? 'bg-primary text-primary-foreground'
                                        : 'hover:bg-muted',
                                )}
                            >
                                <Icon className="size-4 shrink-0" />
                                <span className="min-w-0 truncate font-medium">
                                    {doc.label}
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ul>

            {selected && (
                <div className="flex min-h-[60vh] flex-col overflow-hidden rounded-3xl border border-border bg-card/40">
                    <div className="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
                        <p className="min-w-0 truncate text-sm font-semibold">
                            {selected.label}
                        </p>
                        <a
                            href={selected.href}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex shrink-0 items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ExternalLink className="size-4" />
                            Open in new tab
                        </a>
                    </div>
                    <Preview doc={selected} className="h-[75vh]" />
                </div>
            )}
        </div>
    );
}
