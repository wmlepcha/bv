import { Link } from '@inertiajs/react';
import AppLogoIcon from '@app/components/app-logo-icon';
import { AuthPageBackground } from '@shared/components/auth-page-background';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center overflow-hidden p-4 sm:p-6 md:p-10">
            <AuthPageBackground />
            <div className="relative z-10 w-full max-w-md">
                <div className="flex flex-col gap-8 rounded-2xl border border-white/10 bg-card/90 p-6 shadow-xl shadow-black/20 backdrop-blur-md sm:p-8">
                    <div className="flex flex-col items-center gap-5">
                        <Link
                            href={home()}
                            className="flex flex-col items-center"
                        >
                            <AppLogoIcon
                                className="h-12 w-auto sm:h-14"
                                alt="Bilingual Vitae"
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
