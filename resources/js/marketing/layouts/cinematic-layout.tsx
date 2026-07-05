import type { ReactNode } from 'react';
import { applyGsapDefaults } from '@marketing/animations/gsap/defaults';
import { registerGsapPlugins } from '@marketing/animations/gsap/register-plugins';
import { CinematicHeader } from '@marketing/components/cinematic-header';
import { HomeFooter } from '@marketing/components/home-footer';
import { MagicNavigationMenu } from '@marketing/components/magic-navigation-menu';
import { ScrollProvider } from '@marketing/providers/scroll-provider';
import { TransitionProvider } from '@marketing/providers/transition-provider';
import { BrandPageBackground } from '@shared/components/brand-page-background';

registerGsapPlugins();
applyGsapDefaults();

interface CinematicLayoutProps {
    children: ReactNode;
}

/** Full-bleed marketing layout without app sidebar. */
export default function CinematicLayout({ children }: CinematicLayoutProps) {
    return (
        <ScrollProvider>
            <div className="relative min-h-screen overflow-x-clip bg-background pb-[calc(5.5rem+env(safe-area-inset-bottom))] lg:pb-0">
                <BrandPageBackground />
                <CinematicHeader />
                <TransitionProvider>{children}</TransitionProvider>
                <HomeFooter />
                <MagicNavigationMenu />
            </div>
        </ScrollProvider>
    );
}
