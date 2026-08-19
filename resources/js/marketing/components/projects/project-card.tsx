import { Link } from '@inertiajs/react';
import { ScaleIn } from '@marketing/components/animations/primitives';
import type { ProjectsPagePostCard } from '@marketing/types/projects-page';
import { cn } from '@/lib/utils';

type ProjectCardProps = {
    post: ProjectsPagePostCard;
    index: number;
};

const overlayGradients = [
    'from-brand-warm-dark/85 via-brand-warm/55 to-brand-cool/45',
    'from-brand-cool-dark/85 via-brand-cool/60 to-brand-warm/40',
    'from-brand-warm/80 via-brand-warm-light/50 to-brand-cool-light/45',
    'from-brand-cool/85 via-brand-cool-dark/55 to-brand-warm/40',
];

export function ProjectCard({ post, index }: ProjectCardProps) {
    const gradient = overlayGradients[index % overlayGradients.length];

    return (
        <ScaleIn delay={index * 0.08}>
            <Link
                href={`/success-stories/${post.slug}`}
                className={cn(
                    'group relative flex min-h-[280px] overflow-hidden rounded-2xl border marketing-hairline shadow-lg transition-transform duration-300 hover:scale-[1.01] hover:shadow-xl md:min-h-[320px]',
                    post.cardLayout === 'wide' ? 'md:min-h-[300px]' : 'md:min-h-[420px]',
                )}
            >
                {post.featuredImageUrl ? (
                    <img
                        src={post.featuredImageUrl}
                        alt={post.featuredImageAlt ?? post.title}
                        className="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                        loading="lazy"
                    />
                ) : (
                    <div className="absolute inset-0 bg-gradient-to-br from-primary/30 to-secondary/40" />
                )}

                <div
                    className={cn(
                        'absolute inset-0 bg-gradient-to-br',
                        gradient,
                    )}
                />

                <div className="relative flex h-full w-full flex-col justify-between p-6 md:p-8">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-white/80">
                            {post.category.name}
                        </p>
                        <h2 className="changa-one-regular mt-3 max-w-xl text-2xl leading-tight text-white md:text-3xl lg:text-4xl">
                            {post.title}
                        </h2>
                    </div>

                    <span className="inline-flex w-fit rounded-full bg-white px-4 py-2 text-sm font-medium text-neutral-900 transition-colors group-hover:bg-white/90">
                        {post.readingTimeMinutes} min read
                    </span>
                </div>
            </Link>
        </ScaleIn>
    );
}
