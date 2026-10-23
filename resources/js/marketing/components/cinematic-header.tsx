import { Link, usePage } from '@inertiajs/react';
import { CircleUserRound, LogIn } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import AppLogoIcon from '@app/components/app-logo-icon';
import { isMarketingNavLinkActive } from '@marketing/components/nav-links';
import { PartnerLogos } from '@marketing/components/partner-logos';
import { useLenis } from '@marketing/providers/lenis-provider';
import { AppearanceToggle } from '@shared/components/appearance-toggle';
import { BRAND_NAME_TM } from '@shared/lib/brand';

const dashboardUrl = dashboard().url;

/** Transparent navigation that becomes glassmorphic on scroll. */
export function CinematicHeader() {
    const headerRef = useRef<HTMLElement>(null);
    const [scrolled, setScrolled] = useState(false);
    const { lenis } = useLenis();
    const page = usePage();
    const { siteLayout, auth } = page.props;
    const user = auth.user;
    const url = page.url;
    const {
        logoUrlLight,
        logoUrlDark,
        partnerLabel,
        navigation,
        partners,
        showAppearanceToggle,
    } = siteLayout.header;
    const loginLink = navigation.find((link) => link.href === '/login');

    useEffect(() => {
        const readScrollY = () => lenis?.scroll ?? window.scrollY;
        const updateScrolled = () => setScrolled(readScrollY() > 60);

        updateScrolled();

        if (lenis) {
            lenis.on('scroll', updateScrolled);
        }

        window.addEventListener('scroll', updateScrolled, { passive: true });

        return () => {
            if (lenis) {
                lenis.off('scroll', updateScrolled);
            }

            window.removeEventListener('scroll', updateScrolled);
        };
    }, [lenis]);

    useEffect(() => {
        const header = headerRef.current;

        if (!header) {
            return;
        }

        const updateHeaderHeight = () => {
            document.documentElement.style.setProperty(
                '--marketing-header-height',
                `${header.offsetHeight}px`,
            );
        };

        updateHeaderHeight();

        const observer = new ResizeObserver(updateHeaderHeight);
        observer.observe(header);

        return () => observer.disconnect();
    }, [scrolled, showAppearanceToggle]);

    return (
        <header
            ref={headerRef}
            className={cn(
                'fixed top-0 right-0 left-0 z-50 transition-[background-color,box-shadow,border-color,backdrop-filter] duration-500',
                scrolled
                    ? 'border-b border-border bg-background/70 py-3 shadow-lg shadow-black/5 backdrop-blur-xl'
                    : 'bg-transparent py-3 sm:py-5',
            )}
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 2xl:max-w-[90rem]">
                <div className="flex items-center justify-between gap-4">
                    <div className="flex min-w-0 items-center gap-8 lg:gap-10">
                        <Link href="/" className="shrink-0">
                            <AppLogoIcon
                                className="h-12 w-auto sm:h-14 dark:hidden"
                                alt={BRAND_NAME_TM}
                                src={logoUrlLight}
                            />
                            <AppLogoIcon
                                className="hidden h-12 w-auto sm:h-14 dark:block"
                                alt={BRAND_NAME_TM}
                                src={logoUrlDark}
                            />
                        </Link>

                        <nav className="hidden items-center gap-8 lg:flex">
                            {navigation.map((link) => {
                                const isAccountLink = link.href === '/login';
                                const showAccount = isAccountLink && !!user;

                                if (showAccount && user) {
                                    return (
                                        <Link
                                            key={link.href}
                                            href={dashboardUrl}
                                            aria-label="Your dashboard"
                                            className="flex size-9 shrink-0 items-center justify-center rounded-full ring-1 ring-border/60 transition-colors hover:ring-primary"
                                        >
                                            {user.avatar_url ? (
                                                <img
                                                    src={user.avatar_url}
                                                    alt={user.name}
                                                    className="size-full rounded-full object-cover"
                                                />
                                            ) : (
                                                <CircleUserRound
                                                    className="size-6 text-foreground/80"
                                                    strokeWidth={1.5}
                                                />
                                            )}
                                        </Link>
                                    );
                                }

                                const isActive = isMarketingNavLinkActive(
                                    url,
                                    link.href,
                                );

                                return (
                                    <Link
                                        key={link.href}
                                        href={link.href}
                                        className={cn(
                                            'relative text-sm transition-colors hover:text-primary',
                                            isActive && 'text-primary',
                                        )}
                                    >
                                        {link.label}
                                        {isActive && (
                                            <span className="absolute -bottom-1 left-0 h-px w-full bg-primary" />
                                        )}
                                    </Link>
                                );
                            })}
                        </nav>
                    </div>

                    <div className="hidden shrink-0 items-center justify-end gap-3 lg:flex">
                        {showAppearanceToggle && <AppearanceToggle />}
                        <PartnerLogos
                            partners={partners}
                            partnerLabel={partnerLabel}
                        />
                    </div>

                    {loginLink && (
                        <div className="flex shrink-0 items-center lg:hidden">
                            {user ? (
                                <Link
                                    href={dashboardUrl}
                                    aria-label="Your dashboard"
                                    className="flex size-9 items-center justify-center rounded-full ring-1 ring-border/60 transition-colors hover:ring-primary"
                                >
                                    {user.avatar_url ? (
                                        <img
                                            src={user.avatar_url}
                                            alt={user.name}
                                            className="size-full rounded-full object-cover"
                                        />
                                    ) : (
                                        <CircleUserRound
                                            className="size-6 text-foreground/80"
                                            strokeWidth={1.5}
                                        />
                                    )}
                                </Link>
                            ) : (
                                <Link
                                    href={loginLink.href}
                                    className="flex h-9 items-center gap-1.5 rounded-full px-3 text-sm ring-1 ring-border/60 transition-colors hover:ring-primary"
                                >
                                    <LogIn
                                        className="size-4 text-foreground/80"
                                        strokeWidth={1.75}
                                    />
                                    {loginLink.label}
                                </Link>
                            )}
                        </div>
                    )}
                </div>

                <div className="-mt-0.5 flex items-center justify-between gap-3 lg:hidden">
                    <PartnerLogos
                        logoClassName="h-6 sm:h-7"
                        labelClassName="max-w-[9.5rem] text-left text-[10px] leading-tight text-foreground/80 sm:max-w-[11rem] sm:text-xs"
                        className="justify-start gap-2 sm:gap-3"
                        partners={partners}
                        partnerLabel={partnerLabel}
                    />
                    {showAppearanceToggle && <AppearanceToggle />}
                </div>
            </div>
        </header>
    );
}
