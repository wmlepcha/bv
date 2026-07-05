import { Link, usePage } from '@inertiajs/react';
import { SeoHead } from '@shared/components/seo-head';
import {
    BlogSectionRenderer,
    BlogVideoSection,
} from '@marketing/components/projects/blog-section-renderer';
import { ProjectPostSidebar } from '@marketing/components/projects/project-post-sidebar';
import { FadeUp } from '@marketing/components/animations/primitives';
import { buildArticleJsonLd } from '@marketing/seo/article-json-ld';
import type { ProjectShowPageProps } from '@marketing/types/projects-page';

export default function ProjectShow() {
    const { post } = usePage<ProjectShowPageProps>().props;
    const { appUrl } = usePage().props;
    const canonical = `/projects/${post.slug}`;
    const shareUrl = `${appUrl}${canonical}`;

    const jsonLd = buildArticleJsonLd({
        appUrl,
        headline: post.seo.title,
        description: post.seo.description,
        image: post.seo.ogImage ?? post.heroImageUrl ?? undefined,
        datePublished: post.publishedAt ?? undefined,
        authorName: post.author.name ?? 'Bilingual Vitae Editorial',
        url: shareUrl,
    });

    return (
        <>
            <SeoHead
                title={post.seo.title}
                description={post.seo.description}
                canonical={canonical}
                ogImage={post.seo.ogImage ?? undefined}
                ogType="article"
                keywords={post.seo.keywords ?? undefined}
                noindex={post.seo.noindex}
                jsonLd={jsonLd}
            />
            <main className="px-4 pb-20 pt-[calc(var(--marketing-header-height,5.5rem)+0.75rem)] sm:px-6 sm:pb-28 lg:pt-32">
                <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                    <FadeUp>
                        <Link
                            href="/projects"
                            className="mb-8 inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-sm text-muted-foreground transition-colors hover:border-primary hover:text-primary"
                        >
                            <span aria-hidden>←</span>
                            Back to Projects
                        </Link>
                    </FadeUp>

                    <div className="flex flex-col gap-8 lg:flex-row lg:items-start lg:gap-16">
                        <div className="flex min-w-0 flex-1 flex-col gap-8">
                            <FadeUp delay={0.05}>
                                <h1 className="changa-one-regular max-w-4xl text-3xl tracking-tight md:text-4xl lg:text-5xl">
                                    {post.title}
                                </h1>
                                <p className="mt-4 max-w-3xl border-b border-border pb-8 text-base leading-relaxed text-muted-foreground md:text-lg lg:max-w-none">
                                    {post.excerpt}
                                </p>
                            </FadeUp>

                            <ProjectPostSidebar
                                post={post}
                                shareUrl={shareUrl}
                                className="border-t border-border pt-8 lg:hidden"
                            />

                            {post.heroImageUrl ? (
                                <FadeUp delay={0.15}>
                                    <img
                                        src={post.heroImageUrl}
                                        alt={post.heroImageAlt ?? post.title}
                                        className="w-full rounded-2xl object-cover"
                                    />
                                </FadeUp>
                            ) : null}

                            <article>
                                {post.sections.map((section, index) => (
                                    <FadeUp
                                        key={index}
                                        delay={Math.min(index * 0.05, 0.3)}
                                    >
                                        {section.type === 'video' ? (
                                            <div className="relative left-1/2 my-10 w-screen max-w-none -translate-x-1/2 px-4 sm:px-6 lg:static lg:my-10 lg:w-auto lg:max-w-none lg:translate-x-0 lg:px-0">
                                                <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                                                    <BlogVideoSection section={section} />
                                                </div>
                                            </div>
                                        ) : (
                                            <BlogSectionRenderer section={section} />
                                        )}
                                    </FadeUp>
                                ))}
                            </article>
                        </div>

                        <ProjectPostSidebar
                            post={post}
                            shareUrl={shareUrl}
                            className="hidden lg:sticky lg:top-[calc(var(--marketing-header-height,5.5rem)+1rem)] lg:block lg:w-[280px] lg:shrink-0 lg:self-start lg:border-l lg:border-border lg:pl-8"
                        />
                    </div>
                </div>
            </main>
        </>
    );
}
