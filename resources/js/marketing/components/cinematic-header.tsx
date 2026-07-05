import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import AppLogoIcon from '@app/components/app-logo-icon';
import {
    isMarketingNavLinkActive,
} from '@marketing/components/nav-links';
import { PartnerLogos } from '@marketing/components/partner-logos';
import { useLenis } from '@marketing/providers/lenis-provider';
import { login } from '@/routes';
import { cn } from '@/lib/utils';

/** Transparent navigation that becomes glassmorphic on scroll. */
export function CinematicHeader() {
    const headerRef = useRef<HTMLElement>(null);
    const [scrolled, setScrolled] = useState(false);
    const { lenis } = useLenis();
    const page = usePage();
    const { siteLayout } = page.props;
    const url = page.url;
    const { logoUrl, partnerLabel, navigation, partners } = siteLayout.header;

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
    }, [scrolled]);

    return (
        <header
            ref={headerRef}
            className={cn(
                'fixed top-0 left-0 right-0 z-50 transition-[background-color,box-shadow,border-color,backdrop-filter] duration-500',
                scrolled
                    ? 'border-b border-white/10 bg-background/70 py-3 shadow-lg shadow-black/5 backdrop-blur-xl'
                    : 'bg-transparent py-3 sm:py-5',
            )}
        >
            <div className="mx-auto max-w-7xl px-4 sm:px-6 2xl:max-w-[90rem]">
                <div className="flex items-center justify-between gap-4 lg:grid lg:grid-cols-[1fr_auto_1fr] lg:items-center">
                    <Link href="/" className="shrink-0 justify-self-start">
                        <AppLogoIcon
                            className="h-12 w-auto sm:h-14"
                            alt="Bilingual Vitae"
                            src={logoUrl}
                        />
                    </Link>

                    <Link
                        href={login()}
                        className="text-sm font-medium text-foreground/90 transition-colors hover:text-primary lg:hidden"
                    >
                        Login
                    </Link>

                    <nav className="hidden items-center gap-8 justify-self-center lg:flex">
                        {navigation.map((link) => {
                            const isActive = isMarketingNavLinkActive(url, link.href);

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

                    <div className="hidden shrink-0 items-center justify-end justify-self-end lg:flex">
                        <PartnerLogos partners={partners} partnerLabel={partnerLabel} />
                    </div>
                </div>

                <div className="-mt-0.5 flex justify-center lg:hidden">
                    <PartnerLogos
                        logoClassName="h-6 sm:h-7"
                        labelClassName="max-w-[9.5rem] text-left text-[10px] leading-tight text-foreground/80 sm:max-w-[11rem] sm:text-xs"
                        className="justify-center gap-2 sm:gap-3"
                        partners={partners}
                        partnerLabel={partnerLabel}
                    />
                </div>
            </div>
        </header>
    );
}
