import { Loader2 } from 'lucide-react';

export function PullToRefreshIndicator({
    distance,
    refreshing,
}: {
    distance: number;
    refreshing: boolean;
}) {
    if (distance === 0 && !refreshing) {
        return null;
    }

    const offset = refreshing ? 16 : distance;

    return (
        <div
            aria-hidden
            className="pointer-events-none fixed inset-x-0 z-30 flex justify-center transition-transform"
            style={{
                top: 'calc(var(--app-top-h, 0px) + 4px)',
                transform: `translateY(${offset}px)`,
            }}
        >
            <span className="flex size-9 items-center justify-center rounded-full bg-card shadow-lg ring-1 ring-border">
                <Loader2
                    className="size-5 text-primary"
                    style={{
                        animation: refreshing
                            ? 'spin 0.8s linear infinite'
                            : undefined,
                        transform: refreshing
                            ? undefined
                            : `rotate(${distance * 5}deg)`,
                    }}
                />
            </span>
        </div>
    );
}
