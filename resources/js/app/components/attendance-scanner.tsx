import { CheckCircle2, Loader2, XCircle } from 'lucide-react';
import QrScanner from 'qr-scanner';
import { useEffect, useRef, useState } from 'react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/responsive-sheet';
import { scan } from '@/routes/student/attendance';
import { postJson } from '@app/lib/csrf-fetch';

type State =
    | { kind: 'starting' }
    | { kind: 'scanning' }
    | { kind: 'sending' }
    | { kind: 'done'; message: string }
    | { kind: 'error'; message: string };

export default function AttendanceScanner({
    open,
    onOpenChange,
    onMarked,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onMarked: () => void;
}) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const scannerRef = useRef<QrScanner | null>(null);
    const [state, setState] = useState<State>({ kind: 'starting' });
    const [attempt, setAttempt] = useState(0);

    useEffect(() => {
        if (!open) {
            return;
        }

        let cancelled = false;
        let handled = false;

        const timer = window.setTimeout(() => {
            const video = videoRef.current;

            if (!video || cancelled) {
                return;
            }

            const scanner = new QrScanner(
                video,
                (result) => {
                    if (handled) {
                        return;
                    }

                    handled = true;
                    scanner.stop();
                    setState({ kind: 'sending' });

                    void postJson<{ message?: string }>(scan.url(), {
                        token: result.data,
                    }).then(({ ok, data }) => {
                        if (cancelled) {
                            return;
                        }

                        if (ok) {
                            setState({
                                kind: 'done',
                                message:
                                    data.message ?? 'You are marked present.',
                            });
                            onMarked();
                        } else {
                            setState({
                                kind: 'error',
                                message:
                                    data.message ??
                                    'Could not mark attendance. Try again.',
                            });
                        }
                    });
                },
                {
                    preferredCamera: 'environment',
                    highlightScanRegion: true,
                    highlightCodeOutline: true,
                    maxScansPerSecond: 8,
                    returnDetailedScanResult: true,
                },
            );

            scannerRef.current = scanner;
            setState({ kind: 'starting' });

            scanner
                .start()
                .then(() => !cancelled && setState({ kind: 'scanning' }))
                .catch(() =>
                    setState({
                        kind: 'error',
                        message:
                            'Camera access is needed to scan. Allow the camera in your browser settings and try again.',
                    }),
                );
        }, 150);

        return () => {
            cancelled = true;
            window.clearTimeout(timer);
            scannerRef.current?.destroy();
            scannerRef.current = null;
        };
    }, [open, attempt, onMarked]);

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="gap-0 rounded-t-3xl p-0 pb-[env(safe-area-inset-bottom)] md:mx-auto md:max-w-md"
            >
                <SheetHeader className="border-b border-border/60 p-5 pr-12">
                    <SheetTitle>Scan attendance QR</SheetTitle>
                    <SheetDescription>
                        Point your camera at the QR your trainer is showing.
                    </SheetDescription>
                </SheetHeader>

                <div className="flex flex-col gap-4 p-5">
                    <div className="relative aspect-square overflow-hidden rounded-3xl bg-black">
                        <video
                            ref={videoRef}
                            className="size-full object-cover"
                            playsInline
                            muted
                        />
                        {(state.kind === 'starting' ||
                            state.kind === 'sending') && (
                            <div className="absolute inset-0 flex items-center justify-center bg-black/60">
                                <Loader2 className="size-8 animate-spin text-white" />
                            </div>
                        )}
                        {state.kind === 'done' && (
                            <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-emerald-600/90 text-white">
                                <CheckCircle2 className="size-14" />
                                <p className="text-lg font-semibold">
                                    {state.message}
                                </p>
                            </div>
                        )}
                    </div>

                    {state.kind === 'error' && (
                        <div className="flex items-start gap-2 rounded-2xl bg-destructive/10 p-3 text-sm text-destructive">
                            <XCircle className="mt-0.5 size-4 shrink-0" />
                            <div className="flex-1">
                                <p>{state.message}</p>
                                <button
                                    type="button"
                                    className="mt-1 font-semibold underline"
                                    onClick={() => setAttempt((n) => n + 1)}
                                >
                                    Try again
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}
