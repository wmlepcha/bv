import { FadeUp } from '@marketing/components/animations/primitives';

type ProjectsHeroSectionProps = {
    title: string;
    subtitle: string;
};

export function ProjectsHeroSection({ title, subtitle }: ProjectsHeroSectionProps) {
    return (
        <section className="relative px-4 pb-10 pt-[calc(var(--marketing-header-height,5.5rem)+0.75rem)] sm:px-6 sm:pb-14 lg:pb-16 lg:pt-32">
            <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                <FadeUp>
                    <h1 className="changa-one-regular text-4xl tracking-tight md:text-5xl lg:text-6xl">
                        {title}
                    </h1>
                </FadeUp>
                <FadeUp
                    className="mt-4 max-w-3xl text-base leading-relaxed text-muted-foreground md:mt-6 md:text-lg lg:text-xl"
                    delay={0.1}
                >
                    {subtitle}
                </FadeUp>
            </div>
        </section>
    );
}
