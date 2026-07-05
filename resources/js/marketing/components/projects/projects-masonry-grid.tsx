import { ProjectCard } from '@marketing/components/projects/project-card';
import type { ProjectsPagePostCard } from '@marketing/types/projects-page';
import { cn } from '@/lib/utils';

type ProjectsMasonryGridProps = {
    posts: ProjectsPagePostCard[];
};

export function ProjectsMasonryGrid({ posts }: ProjectsMasonryGridProps) {
    if (posts.length === 0) {
        return (
            <p className="text-center text-muted-foreground">
                No articles published yet. Check back soon.
            </p>
        );
    }

    return (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-5 lg:gap-6">
            {posts.map((post, index) => (
                <div
                    key={post.slug}
                    className={cn(
                        post.cardLayout === 'wide' && 'md:col-span-2',
                    )}
                >
                    <ProjectCard post={post} index={index} />
                </div>
            ))}
        </div>
    );
}
