import { FadeUp } from '@marketing/components/animations/primitives';
import type { AboutPageProps } from '@marketing/types/about-page';

interface AboutOverviewSectionProps {
    overview: AboutPageProps['overview'];
}

export function AboutOverviewSection({ overview }: AboutOverviewSectionProps) {
    return (
        <section className="px-4 pt-4 pb-16 sm:px-6 sm:pt-8 sm:pb-24 md:pt-12 md:pb-32">
            <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                <FadeUp>
                    <h2 className="changa-one-regular max-w-4xl text-3xl tracking-tight md:text-4xl lg:text-5xl">
                        {overview.tagline}
                    </h2>
                </FadeUp>

                <div className="mt-8 space-y-6">
                    {overview.paragraphs.map((paragraph, index) => (
                        <FadeUp
                            key={index}
                            delay={0.15 + index * 0.05}
                            className="max-w-3xl text-base leading-relaxed text-muted-foreground md:text-lg"
                        >
                            {paragraph}
                        </FadeUp>
                    ))}
                </div>
            </div>
        </section>
    );
}
