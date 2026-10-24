import type { PDFDocumentLoadingTask, PDFDocumentProxy } from 'pdfjs-dist';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';

function sameOriginPath(url: string): string {
    const parsed = new URL(url, window.location.href);

    return parsed.pathname + parsed.search;
}

type Status = 'loading' | 'ready' | 'error';

type PageSize = { width: number; height: number };

/**
 * Renders every page of a PDF as canvases (pdf.js), so phones can read the whole document inline.
 * Pages draw lazily as they scroll near the viewport. Falls back to an "open" link if pdf.js fails.
 */
export function PdfPages({ url, title }: { url: string; title: string }) {
    const [status, setStatus] = useState<Status>('loading');
    const [sizes, setSizes] = useState<PageSize[]>([]);
    const containerRef = useRef<HTMLDivElement>(null);
    const docRef = useRef<PDFDocumentProxy | null>(null);
    const taskRef = useRef<PDFDocumentLoadingTask | null>(null);

    useEffect(() => {
        let cancelled = false;

        (async () => {
            try {
                const pdfjs = await import('pdfjs-dist');

                // `?worker` makes Vite emit a plain .js worker, so servers that mis-type .mjs files still work.
                // With the Vite dev server the worker is cross-origin and cannot start; pdf.js then runs
                // on the main thread from `workerSrc` (slower, but it works).
                try {
                    const { default: PdfWorker } =
                        await import('pdfjs-dist/build/pdf.worker.min.mjs?worker');
                    pdfjs.GlobalWorkerOptions.workerPort = new PdfWorker();
                } catch {
                    const { default: workerSrc } =
                        await import('pdfjs-dist/build/pdf.worker.min.mjs?url');
                    pdfjs.GlobalWorkerOptions.workerSrc = workerSrc;
                }

                const task = pdfjs.getDocument({
                    // same-origin path, so a differing APP_URL host can never turn this into a blocked cross-origin request
                    url: sameOriginPath(url),
                    withCredentials: true,
                });
                taskRef.current = task;
                const doc = await task.promise;

                if (cancelled) {
                    void task.destroy();

                    return;
                }

                docRef.current = doc;

                const pageSizes: PageSize[] = [];

                for (let number = 1; number <= doc.numPages; number++) {
                    const viewport = (await doc.getPage(number)).getViewport({
                        scale: 1,
                    });
                    pageSizes.push({
                        width: viewport.width,
                        height: viewport.height,
                    });
                }

                if (!cancelled) {
                    setSizes(pageSizes);
                    setStatus('ready');
                }
            } catch (error) {
                console.error('PDF preview failed', error);

                if (!cancelled) {
                    setStatus('error');
                }
            }
        })();

        return () => {
            cancelled = true;
            void taskRef.current?.destroy();
            taskRef.current = null;
            docRef.current = null;
        };
    }, [url]);

    useEffect(() => {
        const container = containerRef.current;

        if (status !== 'ready' || !container) {
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    const canvas = entry.target as HTMLCanvasElement;

                    if (!entry.isIntersecting || canvas.dataset.drawn === '1') {
                        continue;
                    }

                    canvas.dataset.drawn = '1';
                    void draw(canvas);
                }
            },
            { rootMargin: '800px 0px' },
        );

        const draw = async (canvas: HTMLCanvasElement) => {
            const doc = docRef.current;

            if (!doc) {
                return;
            }

            const page = await doc.getPage(Number(canvas.dataset.page));
            const base = page.getViewport({ scale: 1 });
            const cssWidth = canvas.parentElement?.clientWidth ?? base.width;
            const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
            const viewport = page.getViewport({
                scale: (cssWidth / base.width) * pixelRatio,
            });

            canvas.width = viewport.width;
            canvas.height = viewport.height;
            await page.render({ canvas, viewport }).promise;
        };

        container
            .querySelectorAll('canvas')
            .forEach((canvas) => observer.observe(canvas));

        return () => observer.disconnect();
    }, [status, sizes]);

    if (status === 'error') {
        return (
            <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border/60 p-8 text-center">
                <p className="text-sm text-muted-foreground">
                    This document could not be shown here.
                </p>
                <Button asChild variant="outline" size="sm">
                    <a href={url} target="_blank" rel="noopener noreferrer">
                        Open document
                    </a>
                </Button>
            </div>
        );
    }

    if (status === 'loading') {
        return (
            <div
                className="grid gap-3"
                aria-busy="true"
                aria-label={`Loading ${title}`}
            >
                <div className="aspect-[3/4] animate-pulse rounded-xl bg-muted" />
            </div>
        );
    }

    return (
        <div ref={containerRef} className="grid gap-3">
            {sizes.map((size, index) => (
                <div
                    key={index}
                    className="overflow-hidden rounded-lg border border-border/60 bg-white shadow-sm"
                    style={{ aspectRatio: `${size.width} / ${size.height}` }}
                >
                    <canvas
                        data-page={index + 1}
                        aria-label={`${title}, page ${index + 1}`}
                        className="size-full"
                    />
                </div>
            ))}
        </div>
    );
}
