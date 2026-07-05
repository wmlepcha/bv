import type { BlogSection } from '@marketing/types/projects-page';
import { cn } from '@/lib/utils';

type BlogSectionRendererProps = {
    section: BlogSection;
};

function getYouTubeEmbedUrl(url: string): string | null {
    const match = url.match(
        /(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/,
    );

    return match ? `https://www.youtube.com/embed/${match[1]}` : null;
}

function getVimeoEmbedUrl(url: string): string | null {
    const match = url.match(/vimeo\.com\/(\d+)/);

    return match ? `https://player.vimeo.com/video/${match[1]}` : null;
}

function HeadingSection({ content }: { content: Record<string, unknown> }) {
    const level = Math.min(6, Math.max(1, Number(content.level ?? 2)));
    const text = String(content.text ?? '');
    const className = cn(
        level <= 2 ? 'changa-one-regular text-2xl md:text-3xl' : 'text-lg font-semibold md:text-xl',
        'mt-8 first:mt-0',
    );

    switch (level) {
        case 1:
            return <h1 className={className}>{text}</h1>;
        case 2:
            return <h2 className={className}>{text}</h2>;
        case 3:
            return <h3 className={className}>{text}</h3>;
        case 4:
            return <h4 className={className}>{text}</h4>;
        case 5:
            return <h5 className={className}>{text}</h5>;
        default:
            return <h6 className={className}>{text}</h6>;
    }
}

function ParagraphSection({ content }: { content: Record<string, unknown> }) {
    return (
        <div
            className="mt-4 text-base leading-relaxed text-foreground/90 md:text-lg [&_a]:text-primary [&_a]:underline [&_em]:italic [&_strong]:font-semibold"
            dangerouslySetInnerHTML={{ __html: String(content.text ?? '') }}
        />
    );
}

function BulletListSection({ content }: { content: Record<string, unknown> }) {
    const items = Array.isArray(content.items) ? content.items : [];

    return (
        <ul className="mt-4 list-disc space-y-2 pl-5 text-base leading-relaxed text-foreground/90 md:text-lg">
            {items.map((item, index) => (
                <li key={index}>{String(item)}</li>
            ))}
        </ul>
    );
}

function FaqSection({ content }: { content: Record<string, unknown> }) {
    const items = Array.isArray(content.items) ? content.items : [];

    return (
        <div className="mt-6 space-y-3">
            {items.map((item, index) => {
                if (!item || typeof item !== 'object') {
                    return null;
                }

                const faq = item as { question?: string; answer?: string };

                return (
                    <details
                        key={index}
                        className="group rounded-xl border border-border bg-card/50 px-4 py-3"
                    >
                        <summary className="cursor-pointer font-medium text-foreground">
                            {faq.question}
                        </summary>
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground md:text-base">
                            {faq.answer}
                        </p>
                    </details>
                );
            })}
        </div>
    );
}

function QuoteSection({ content }: { content: Record<string, unknown> }) {
    return (
        <blockquote className="my-10 border-l-4 border-primary pl-6">
            <p className="changa-one-regular text-xl italic leading-relaxed text-foreground md:text-2xl">
                &ldquo;{String(content.text ?? '')}&rdquo;
            </p>
            {content.attribution ? (
                <cite className="mt-3 block text-sm not-italic text-muted-foreground">
                    — {String(content.attribution)}
                </cite>
            ) : null}
        </blockquote>
    );
}

function ImageSection({ content }: { content: Record<string, unknown> }) {
    const url = String(content.url ?? content.path ?? '');
    const placement = String(content.placement ?? 'full');

    if (!url) {
        return null;
    }

    return (
        <figure
            className={cn(
                'mt-6',
                placement === 'inline' && 'max-w-md',
                placement === 'below' && 'mt-8',
            )}
        >
            <img
                src={url}
                alt={String(content.alt ?? '')}
                className="w-full rounded-2xl object-cover"
                loading="lazy"
            />
            {content.caption ? (
                <figcaption className="mt-2 text-sm text-muted-foreground">
                    {String(content.caption)}
                </figcaption>
            ) : null}
        </figure>
    );
}

export function BlogSectionRenderer({ section }: BlogSectionRendererProps) {
    switch (section.type) {
        case 'heading':
            return <HeadingSection content={section.content} />;
        case 'paragraph':
            return <ParagraphSection content={section.content} />;
        case 'bullet_list':
            return <BulletListSection content={section.content} />;
        case 'faq':
            return <FaqSection content={section.content} />;
        case 'quote':
            return <QuoteSection content={section.content} />;
        case 'image':
            return <ImageSection content={section.content} />;
        default:
            return null;
    }
}

export function BlogVideoSection({ section }: BlogSectionRendererProps) {
    const content = section.content;
    const source = String(content.source ?? 'youtube');
    const caption = content.caption ? String(content.caption) : null;

    let embedUrl: string | null = null;
    let videoSrc: string | null = null;

    if (source === 'upload') {
        videoSrc = String(content.url ?? content.path ?? '');
    } else {
        const url = String(content.url ?? '');
        embedUrl =
            source === 'vimeo' ? getVimeoEmbedUrl(url) : getYouTubeEmbedUrl(url);
    }

    if (!embedUrl && !videoSrc) {
        return null;
    }

    return (
        <figure className="my-10 w-full">
            <div className="overflow-hidden rounded-2xl bg-black/5">
                {embedUrl ? (
                    <div className="aspect-video w-full">
                        <iframe
                            src={embedUrl}
                            title={caption ?? 'Video'}
                            className="h-full w-full"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowFullScreen
                        />
                    </div>
                ) : (
                    <video
                        src={videoSrc ?? undefined}
                        controls
                        className="aspect-video w-full"
                    >
                        <track kind="captions" />
                    </video>
                )}
            </div>
            {caption ? (
                <figcaption className="mt-2 text-sm text-muted-foreground">
                    {caption}
                </figcaption>
            ) : null}
        </figure>
    );
}
