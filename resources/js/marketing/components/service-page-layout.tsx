import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FadeUp } from '@marketing/components/animations/primitives';
import { cn } from '@/lib/utils';

interface ServicePageLayoutProps {
    title: string;
    subtitle?: string;
    children?: ReactNode;
    className?: string;
}

/** Shared marketing shell for service detail pages. */
export function ServicePageLayout({
    title,
    subtitle,
    children,
    className,
}: ServicePageLayoutProps) {
    return (
        <main>
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

                        {subtitle && (
                            <FadeUp
                                className="mt-4 max-w-2xl text-base leading-relaxed text-muted-foreground md:mt-6 md:text-lg lg:text-xl"
                                delay={0.2}
                            >
                                {subtitle}
                            </FadeUp>
                        )}
                    </div>
                </section>

                {children && (
                    <section className="px-4 py-16 sm:px-6 sm:py-24 md:py-32">
                        <div className="mx-auto w-full max-w-7xl 2xl:max-w-[90rem]">
                            {children}
                        </div>
                    </section>
                )}
            </main>
    );
}
