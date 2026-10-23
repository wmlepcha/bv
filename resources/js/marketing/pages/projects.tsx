import { usePage } from '@inertiajs/react';
import { ProjectsHeroSection } from '@marketing/components/projects/projects-hero-section';
import { ProjectsMasonryGrid } from '@marketing/components/projects/projects-masonry-grid';
import type { ProjectsPageProps } from '@marketing/types/projects-page';
import { SeoHead } from '@shared/components/seo-head';

export default function Projects() {
    const { projectsPage } = usePage<{ projectsPage: ProjectsPageProps }>().props;

    return (
        <>
            <SeoHead
                title={projectsPage.seo.title}
                description={projectsPage.seo.description}
                canonical="/success-stories"
            />
            <main>
                <ProjectsHeroSection
                    title={projectsPage.hero.title}
                    subtitle={projectsPage.hero.subtitle}
                />
                <section className="px-4 pb-20 sm:px-6 sm:pb-28">
                    <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                        <ProjectsMasonryGrid posts={projectsPage.posts} />
                    </div>
                </section>
            </main>
        </>
    );
}
