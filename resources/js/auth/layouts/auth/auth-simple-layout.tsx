import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';
import AppLogoIcon from '@app/components/app-logo-icon';
import { AuthPageBackground } from '@shared/components/auth-page-background';
import { BRAND_NAME_TM } from '@shared/lib/brand';

export default function AuthSimpleLayout({
    children,
    title,
    description,
    containerClassName = 'max-w-md',
    backHref,
}: AuthLayoutProps) {
    const { logoUrlLight, logoUrlDark } = usePage().props.siteLayout.header;

    const logo = (className: string) => (
        <>
            <AppLogoIcon
                className={cn('w-auto dark:hidden', className)}
                alt={BRAND_NAME_TM}
                src={logoUrlLight}
            />
            <AppLogoIcon
                className={cn('hidden w-auto dark:block', className)}
                alt={BRAND_NAME_TM}
                src={logoUrlDark}
            />
        </>
    );

    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center overflow-hidden p-4 max-sm:justify-start max-sm:bg-background max-sm:p-0 sm:p-6 md:p-10">
            {/* Phones use the same plain page background as the rest of the site. */}
            <div className="contents max-sm:hidden">
                <AuthPageBackground />
            </div>

            <header
                className="fixed inset-x-0 top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur-xl sm:hidden"
                style={{ paddingTop: 'env(safe-area-inset-top)' }}
            >
                <div className="relative flex h-14 items-center justify-center px-2">
                    {backHref && (
                        <Link
                            href={backHref}
                            className="absolute left-2 flex size-10 items-center justify-center rounded-full text-foreground/80 transition-transform active:scale-90 active:bg-foreground/10"
                            aria-label="Back"
                        >
                            <ChevronLeft className="size-6" />
                        </Link>
                    )}
                    <Link href={home()} className="flex items-center">
                        {logo('h-8')}
                    </Link>
                </div>
            </header>

            <div
                className={cn(
                    'relative z-10 w-full max-sm:flex-1',
                    containerClassName,
                )}
            >
                <div className="auth-ios flex flex-col gap-8 rounded-2xl border border-white/10 bg-card/90 p-6 shadow-xl shadow-black/20 backdrop-blur-md max-sm:min-h-svh max-sm:gap-6 max-sm:rounded-none max-sm:border-0 max-sm:bg-transparent max-sm:px-5 max-sm:pt-[calc(env(safe-area-inset-top)+5rem)] max-sm:pb-[calc(1.5rem+env(safe-area-inset-bottom))] max-sm:shadow-none max-sm:backdrop-blur-none sm:p-8">
                    <div className="flex flex-col items-center gap-5 max-sm:items-start">
                        <Link
                            href={home()}
                            className="flex flex-col items-center max-sm:hidden"
                        >
                            {logo('h-12 sm:h-14')}
                        </Link>

                        <div className="space-y-2 text-center max-sm:space-y-1.5 max-sm:text-left">
                            <h1 className="text-xl font-semibold tracking-tight max-sm:text-[1.75rem] max-sm:leading-tight max-sm:font-bold sm:text-2xl">
                                {title}
                            </h1>
                            <p className="text-center text-sm leading-relaxed text-muted-foreground max-sm:text-left max-sm:text-[0.9375rem]">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
