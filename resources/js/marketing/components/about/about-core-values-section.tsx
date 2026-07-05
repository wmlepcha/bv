import { FadeUp } from '@marketing/components/animations/primitives';
import type { AboutPageProps } from '@marketing/types/about-page';

interface AboutCoreValuesSectionProps {
    coreValues: AboutPageProps['coreValues'];
}

export function AboutCoreValuesSection({ coreValues }: AboutCoreValuesSectionProps) {
    return (
        <section className="px-4 pb-16 pt-12 sm:px-6 sm:pb-24 sm:pt-12 md:pb-32">
            <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                <FadeUp>
                    <h2 className="changa-one-regular text-3xl tracking-tight md:text-4xl lg:text-5xl">
                        Core Values
                    </h2>
                </FadeUp>

                <div className="mt-6 space-y-6">
                    {coreValues.intro.map((paragraph, index) => (
                        <FadeUp
                            key={index}
                            delay={0.1 + index * 0.05}
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
