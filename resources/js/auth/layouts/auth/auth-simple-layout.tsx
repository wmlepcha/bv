import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@app/components/app-logo-icon';
import { AuthPageBackground } from '@shared/components/auth-page-background';
import { BRAND_NAME_TM } from '@shared/lib/brand';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';
import { cn } from '@/lib/utils';

export default function AuthSimpleLayout({
    children,
    title,
    description,
    containerClassName = 'max-w-md',
}: AuthLayoutProps) {
    const { logoUrlLight, logoUrlDark } = usePage().props.siteLayout.header;

    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center overflow-hidden p-4 sm:p-6 md:p-10">
            <AuthPageBackground />
            <div className={cn('relative z-10 w-full', containerClassName)}>
                <div className="flex flex-col gap-8 rounded-2xl border border-white/10 bg-card/90 p-6 shadow-xl shadow-black/20 backdrop-blur-md sm:p-8">
                    <div className="flex flex-col items-center gap-5">
                        <Link
                            href={home()}
                            className="flex flex-col items-center"
                        >
                            <AppLogoIcon
                                className="h-12 w-auto dark:hidden sm:h-14"
                                alt={BRAND_NAME_TM}
                                src={logoUrlLight}
                            />
                            <AppLogoIcon
                                className="hidden h-12 w-auto dark:block sm:h-14"
                                alt={BRAND_NAME_TM}
                                src={logoUrlDark}
                            />
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-semibold tracking-tight sm:text-2xl">{title}</h1>
                            <p className="text-center text-sm leading-relaxed text-muted-foreground">
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
