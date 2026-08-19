import { FadeUp, StaggerChildren } from '@marketing/components/animations/primitives';
import { LeapfrogValueCard } from '@marketing/components/about/leapfrog-value-card';
import type { AboutPageProps, CoreValue } from '@marketing/types/about-page';

interface AboutLeapfrogSectionProps {
    leapfrog: AboutPageProps['leapfrog'];
    values: CoreValue[];
}

export function AboutLeapfrogSection({ leapfrog, values }: AboutLeapfrogSectionProps) {
    return (
        <section className="px-4 py-16 sm:px-6 sm:py-24 md:py-32">
            <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                <FadeUp>
                    <h2 className="changa-one-regular text-4xl tracking-tight md:text-5xl lg:text-6xl">
                        &ldquo;{leapfrog.title}&rdquo;
                    </h2>
                </FadeUp>

                <div className="mt-8 space-y-6">
                    {leapfrog.paragraphs.map((paragraph, index) => (
                        <FadeUp
                            key={index}
                            delay={0.15 + index * 0.05}
                            className="max-w-3xl text-base leading-relaxed text-muted-foreground md:text-lg"
                        >
                            {paragraph}
                        </FadeUp>
                    ))}
                </div>

                <StaggerChildren className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {values.map((value) => (
                        <LeapfrogValueCard key={value.title} value={value} />
                    ))}
                </StaggerChildren>
            </div>
        </section>
    );
}
