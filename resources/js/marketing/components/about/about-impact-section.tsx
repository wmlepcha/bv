import { FadeUp } from '@marketing/components/animations/primitives';
import { MagneticButton } from '@marketing/components/magnetic-button';
import type { AboutPageProps } from '@marketing/types/about-page';

interface AboutImpactSectionProps {
    impact: AboutPageProps['impact'];
    onLearnMore?: () => void;
}

export function AboutImpactSection({ impact, onLearnMore }: AboutImpactSectionProps) {
    return (
        <section className="px-4 py-16 sm:px-6 sm:py-24 md:py-32">
            <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                <FadeUp>
                    <h2 className="changa-one-regular text-3xl tracking-tight md:text-4xl lg:text-5xl">
                        Our Impact
                    </h2>
                </FadeUp>

                <FadeUp
                    delay={0.1}
                    className="mt-6 max-w-3xl text-base leading-relaxed text-muted-foreground md:text-lg"
                >
                    {impact.paragraph}
                </FadeUp>

                <FadeUp
                    delay={0.15}
                    className="mt-6 max-w-3xl text-base font-medium leading-relaxed md:text-lg"
                >
                    {impact.closing}
                </FadeUp>

                {onLearnMore && (
                    <FadeUp delay={0.2} className="mt-10">
                        <MagneticButton type="button" onClick={onLearnMore}>
                            Learn More
                        </MagneticButton>
                    </FadeUp>
                )}
            </div>
        </section>
    );
}
