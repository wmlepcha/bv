import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@app/components/app-logo-icon';
import { BRAND_NAME_TM } from '@shared/lib/brand';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthTrainingManagerLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { logoUrlLight, logoUrlDark } = usePage().props.siteLayout.header;

    return (
        <div className="grid min-h-svh lg:grid-cols-2">
            <aside className="relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-end">
                <div
                    aria-hidden
                    className="absolute inset-0 bg-gradient-to-br from-brand-cool-dark via-brand-cool to-primary"
                />
                <div
                    aria-hidden
                    className="absolute inset-0 opacity-30"
                    style={{
                        backgroundImage:
                            'radial-gradient(circle at 20% 20%, rgba(255,255,255,0.35) 0, transparent 45%), radial-gradient(circle at 80% 10%, rgba(4,206,255,0.35) 0, transparent 40%), radial-gradient(circle at 70% 80%, rgba(251,162,20,0.25) 0, transparent 45%)',
                    }}
                />
                <div
                    aria-hidden
                    className="absolute inset-0 opacity-[0.12]"
                    style={{
                        backgroundImage:
                            'linear-gradient(rgba(255,255,255,0.35) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.35) 1px, transparent 1px)',
                        backgroundSize: '28px 28px',
                    }}
                />
                <div className="relative z-10 p-10 xl:p-14">
                    <p className="max-w-md text-3xl font-semibold tracking-tight text-white xl:text-4xl">
                        Train. Lead. Grow.
                    </p>
                    <p className="mt-3 max-w-sm text-sm leading-relaxed text-white/80">
                        Manage trainers, batches, and materials in one place.
                        Image placeholder — branding visual coming soon.
                    </p>
                </div>
            </aside>

            <div className="flex flex-col justify-center bg-background px-6 py-10 sm:px-10 lg:px-12">
                <div className="mx-auto w-full max-w-md">
                    <Link
                        href={home()}
                        className="mb-8 flex justify-center lg:justify-start"
                    >
                        <AppLogoIcon
                            className="h-11 w-auto dark:hidden sm:h-12"
                            alt={BRAND_NAME_TM}
                            src={logoUrlLight}
                        />
                        <AppLogoIcon
                            className="hidden h-11 w-auto dark:block sm:h-12"
                            alt={BRAND_NAME_TM}
                            src={logoUrlDark}
                        />
                    </Link>

                    <div className="mb-8 space-y-2 text-center lg:text-left">
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-sm leading-relaxed text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>

                    {children}
                </div>
            </div>
        </div>
    );
}
