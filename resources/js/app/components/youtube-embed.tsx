import { cn } from '@/lib/utils';

/**
 * YouTube embed with the player's own title bar cropped out: the title is shown in a
 * description below the video instead. The "Watch on YouTube" branding at the bottom is left intact.
 */
export function YoutubeEmbed({
    src,
    title,
    className,
}: {
    src: string;
    title: string;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'relative aspect-video w-full overflow-hidden bg-black',
                className,
            )}
        >
            <iframe
                key={src}
                title={title}
                src={src}
                className="absolute left-0 w-full border-0"
                style={{ top: '-3.75rem', height: 'calc(100% + 3.75rem)' }}
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowFullScreen
                referrerPolicy="strict-origin-when-cross-origin"
            />
        </div>
    );
}

export function YoutubeDescription({
    title,
    url,
}: {
    title: string;
    url?: string | null;
}) {
    return (
        <div className="space-y-1.5 px-4 pt-3">
            <p className="text-lg leading-snug font-semibold">{title}</p>
            <p className="text-sm text-muted-foreground">
                A video from your trainer. Pause and rewind as often as you
                need.
            </p>
            {url && (
                <a
                    href={url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-block text-sm font-medium text-primary underline underline-offset-4"
                >
                    Watch on YouTube
                </a>
            )}
        </div>
    );
}
