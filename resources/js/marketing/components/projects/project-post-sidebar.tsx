import { Facebook, Linkedin, Twitter } from 'lucide-react';
import type { ProjectShowPost } from '@marketing/types/projects-page';
import { cn } from '@/lib/utils';

type ProjectPostSidebarProps = {
    post: ProjectShowPost;
    shareUrl: string;
    className?: string;
};

export function ProjectPostSidebar({
    post,
    shareUrl,
    className,
}: ProjectPostSidebarProps) {
    return (
        <aside className={cn('space-y-10', className)}>
            <div className="flex flex-row flex-wrap gap-6 lg:flex-col">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.15em] text-muted-foreground">
                        Date
                    </p>
                    <p className="mt-1 text-sm font-medium md:text-base">
                        {post.publishedAtFormatted ?? '—'}
                    </p>
                </div>
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.15em] text-muted-foreground">
                        Category
                    </p>
                    <p className="mt-1 text-sm font-medium uppercase md:text-base">
                        {post.category.name}
                    </p>
                </div>
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.15em] text-muted-foreground">
                        Reading time
                    </p>
                    <p className="mt-1 text-sm font-medium md:text-base">
                        {post.readingTimeMinutes} min
                    </p>
                </div>
            </div>

            {post.author.name ? (
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.15em] text-muted-foreground">
                        Author
                    </p>
                    <p className="mt-2 text-lg font-semibold">{post.author.name}</p>
                    {post.author.bio ? (
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {post.author.bio}
                        </p>
                    ) : null}
                </div>
            ) : null}

            <div>
                <p className="text-xs font-semibold uppercase tracking-[0.15em] text-muted-foreground">
                    Share
                </p>
                <div className="mt-3 flex gap-3">
                    <a
                        href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex h-10 w-10 items-center justify-center rounded-full border border-border text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                        aria-label="Share on Facebook"
                    >
                        <Facebook className="h-4 w-4" />
                    </a>
                    <a
                        href={`https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}&text=${encodeURIComponent(post.title)}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex h-10 w-10 items-center justify-center rounded-full border border-border text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                        aria-label="Share on X"
                    >
                        <Twitter className="h-4 w-4" />
                    </a>
                    <a
                        href={`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex h-10 w-10 items-center justify-center rounded-full border border-border text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                        aria-label="Share on LinkedIn"
                    >
                        <Linkedin className="h-4 w-4" />
                    </a>
                </div>
            </div>
        </aside>
    );
}
