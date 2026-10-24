import { Link, usePage } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';
import AppLogoIcon from '@app/components/app-logo-icon';
import { BRAND_NAME_TM } from '@shared/lib/brand';

export default function AuthTrainingManagerLayout({
    children,
    title,
    description,
    tagline = 'Train. Lead. Grow.',
    blurb = 'Manage trainers, batches, and materials in one place.',
    panel,
}: AuthLayoutProps) {
    const { header, auth } = usePage().props.siteLayout;
    const { logoUrlLight, logoUrlDark } = header;
    const panelImageUrl =
        panel === 'recruitment_manager'
            ? auth.recruitmentManagerImageUrl
            : panel === 'training_manager'
              ? auth.trainingManagerImageUrl
              : null;

    return (
        <div className="grid min-h-svh lg:grid-cols-2">
            <aside className="relative hidden overflow-hidden lg:flex lg:flex-col lg:justify-end">
                {panelImageUrl ? (
                    <>
                        <img
                            src={panelImageUrl}
                            alt=""
                            className="absolute inset-0 h-full w-full object-cover"
                        />
                        <div
                            aria-hidden
                            className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/25 to-black/10"
                        />
                    </>
                ) : (
                    <>
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
                    </>
                )}
                <div className="relative z-10 p-10 xl:p-14">
                    <p className="max-w-md text-3xl font-semibold tracking-tight text-white xl:text-4xl">
                        {tagline}
                    </p>
                    <p className="mt-3 max-w-sm text-sm leading-relaxed text-white/80">
                        {blurb}
                    </p>
                </div>
            </aside>

            <div className="auth-ios flex flex-col justify-center bg-background px-5 py-10 max-sm:pt-[calc(env(safe-area-inset-top)+2.5rem)] max-sm:pb-[calc(env(safe-area-inset-bottom)+2.5rem)] sm:px-10 lg:px-12">
                <div className="mx-auto w-full max-w-md">
                    <Link href={home()} className="mb-8 flex justify-start">
                        <AppLogoIcon
                            className="h-11 w-auto sm:h-12 dark:hidden"
                            alt={BRAND_NAME_TM}
                            src={logoUrlLight}
                        />
                        <AppLogoIcon
                            className="hidden h-11 w-auto sm:h-12 dark:block"
                            alt={BRAND_NAME_TM}
                            src={logoUrlDark}
                        />
                    </Link>

                    <div className="mb-8 space-y-1.5 text-left max-sm:mb-6 sm:space-y-2">
                        <h1 className="text-2xl font-semibold tracking-tight max-sm:text-[1.75rem] max-sm:leading-tight max-sm:font-bold sm:text-3xl">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-sm leading-relaxed text-muted-foreground max-sm:text-[0.9375rem]">
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
