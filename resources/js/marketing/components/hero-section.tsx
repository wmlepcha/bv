import { SplitStaggerReveal } from '@marketing/components/animations/text';
import { FadeUp } from '@marketing/components/animations/primitives';
import { ParallaxBackground } from '@marketing/components/animations/parallax';
import { CountUpStat } from '@marketing/components/count-up-stat';
import { ScrollIndicator } from '@marketing/components/scroll-indicator';
import { StaggerChildren } from '@marketing/components/animations/primitives';
import type { HomePageProps } from '@marketing/types/home-page';
import { cn } from '@/lib/utils';

interface HeroSectionProps {
    hero: HomePageProps['hero'];
}

export function HeroSection({ hero }: HeroSectionProps) {
    return (
        <ParallaxBackground
            className="relative min-h-screen overflow-hidden"
            speed={0.2}
        >
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,oklch(1_0_0/0.04)_1px,transparent_1px),linear-gradient(to_bottom,oklch(1_0_0/0.04)_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_at_center,black_30%,transparent_80%)]"
            />

            <section className="relative flex min-h-screen flex-col px-4 pt-28 pb-10 sm:px-6 lg:pt-32">
                <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col justify-center gap-14 lg:gap-20 2xl:max-w-[90rem]">
                    <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16 xl:gap-24">
                        <div>
                            <FadeUp>
                                <p className="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-xs font-medium tracking-wide text-foreground/80 backdrop-blur-sm">
                                    <span className="h-1.5 w-1.5 rounded-full bg-primary" />
                                    {hero.badge}
                                </p>
                            </FadeUp>

                            <SplitStaggerReveal
                                as="h1"
                                className="changa-one-regular text-4xl leading-[1.08] tracking-tight md:text-5xl lg:text-6xl xl:text-7xl"
                                split="words"
                            >
                                {hero.headline}
                            </SplitStaggerReveal>

                            <FadeUp
                                className="mt-6 max-w-xl text-base leading-relaxed text-muted-foreground md:mt-8 md:text-lg lg:text-xl"
                                delay={0.35}
                            >
                                {hero.subtitle}
                            </FadeUp>

                            <FadeUp className="mt-6 lg:hidden" delay={0.4}>
                                <div className="flex flex-wrap gap-2">
                                    {hero.highlights.map((item) => (
                                        <span
                                            key={item.label}
                                            className="rounded-full border border-white/10 bg-white/5 px-3.5 py-1.5 text-xs font-medium text-foreground/75"
                                        >
                                            {item.label}
                                        </span>
                                    ))}
                                </div>
                            </FadeUp>
                        </div>

                        <FadeUp
                            className="relative hidden lg:block"
                            delay={0.45}
                        >
                            <div className="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-white/10 via-white/5 to-transparent p-10 backdrop-blur-md">
                                <div className="absolute -top-10 -right-10 h-36 w-36 rounded-full bg-brand-warm/20 blur-3xl" />
                                <div className="absolute -bottom-12 -left-12 h-44 w-44 rounded-full bg-brand-cool-light/15 blur-3xl" />

                                <p className="relative text-2xl leading-snug font-medium text-foreground/90 xl:text-3xl">
                                    {hero.cardText}
                                </p>

                                <div className="relative mt-8 flex flex-wrap gap-2">
                                    {hero.highlights.map((item) => (
                                        <span
                                            key={item.label}
                                            className="rounded-full border border-white/10 bg-white/5 px-3.5 py-1.5 text-xs font-medium text-foreground/75"
                                        >
                                            {item.label}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </FadeUp>
                    </div>

                    <FadeUp delay={0.55}>
                        <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-6 backdrop-blur-sm md:p-8 lg:rounded-3xl lg:p-10">
                            <StaggerChildren className="grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 md:grid-cols-2 lg:grid-cols-5 lg:gap-y-0">
                                {hero.stats.map((stat, index) => (
                                    <CountUpStat
                                        key={stat.label}
                                        value={stat.value}
                                        suffix={stat.suffix}
                                        label={stat.label}
                                        className={cn(
                                            'text-left',
                                            index < hero.stats.length - 1 &&
                                                'lg:border-r lg:border-white/10 lg:pr-6',
                                            index > 0 && 'lg:pl-6',
                                        )}
                                    />
                                ))}
                            </StaggerChildren>
                        </div>
                    </FadeUp>
                </div>

                <ScrollIndicator className="mt-10" />
            </section>
        </ParallaxBackground>
    );
}
