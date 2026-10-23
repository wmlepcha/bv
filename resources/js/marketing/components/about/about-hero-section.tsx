import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { FadeUp } from '@marketing/components/animations/primitives';

interface AboutHeroSectionProps {
    title: string;
    className?: string;
}

export function AboutHeroSection({ title, className }: AboutHeroSectionProps) {
    return (
        <section
            className={cn(
                'relative flex flex-col justify-start px-4 pb-8 pt-[calc(var(--marketing-header-height,5.5rem)+0.75rem)] sm:px-6 sm:pb-12 lg:min-h-[60vh] lg:justify-end lg:pb-20 lg:pt-32 xl:min-h-0 xl:justify-start xl:pb-12',
                className,
            )}
        >
            <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                <FadeUp>
                    <Link
                        href="/"
                        className="mb-4 inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-primary sm:mb-8"
                    >
                        <span aria-hidden>←</span>
                        Back to Home
                    </Link>
                </FadeUp>

                <FadeUp delay={0.1}>
                    <h1 className="changa-one-regular max-w-4xl text-3xl tracking-tight md:text-4xl lg:text-5xl xl:text-6xl">
                        {title}
                    </h1>
                </FadeUp>
            </div>
        </section>
    );
}
