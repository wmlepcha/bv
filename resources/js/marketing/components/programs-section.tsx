import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { FadeUp, StaggerChildren } from '@marketing/components/animations/primitives';
import { ProgramCard } from '@marketing/components/program-card';
import type { HomePageProps } from '@marketing/types/home-page';

interface ProgramsSectionProps {
    programs: HomePageProps['programs'];
}

export function ProgramsSection({ programs }: ProgramsSectionProps) {
    if (programs.cards.length === 0) {
        return null;
    }

    const showCta = programs.ctaLabel.trim().length > 0 && programs.ctaHref.trim().length > 0;

    return (
        <section className="border-y marketing-hairline-soft bg-background px-4 py-16 sm:px-6 sm:py-24 md:py-32">
            <div className="mx-auto max-w-7xl">
                <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <FadeUp className="max-w-2xl">
                        <h2 className="changa-one-regular text-3xl tracking-tight md:text-4xl lg:text-5xl">
                            {programs.heading}
                        </h2>
                    </FadeUp>

                    {showCta && (
                        <FadeUp delay={0.15}>
                            <Link
                                href={programs.ctaHref}
                                className={cn(
                                    'inline-flex min-h-11 items-center justify-center rounded-full bg-foreground px-6 py-2.5 text-sm font-semibold text-background',
                                    'transition-colors hover:bg-primary hover:text-primary-foreground',
                                )}
                            >
                                {programs.ctaLabel} →
                            </Link>
                        </FadeUp>
                    )}
                </div>

                <StaggerChildren
                    className={cn(
                        'mt-12 grid gap-6 sm:gap-8',
                        programs.cards.length === 1 && 'max-w-md',
                        programs.cards.length === 2 && 'sm:grid-cols-2',
                        programs.cards.length >= 3 && 'sm:grid-cols-2 lg:grid-cols-3',
                    )}
                >
                    {programs.cards.map((program, index) => (
                        <ProgramCard
                            key={`${program.href}-${program.title}`}
                            title={program.title}
                            description={program.description}
                            href={program.href}
                            image={program.imageUrl}
                            index={index}
                        />
                    ))}
                </StaggerChildren>
            </div>
        </section>
    );
}
