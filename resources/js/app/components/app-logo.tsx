import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import AppLogoIcon from '@app/components/app-logo-icon';
import { BRAND_NAME_TM } from '@shared/lib/brand';

type AppLogoProps = {
    /** Force high-contrast logo treatment for the brand-colored nav rail. */
    onBrandRail?: boolean;
};

export default function AppLogo({ onBrandRail = false }: AppLogoProps) {
    const { logoUrlLight, logoUrlDark } = usePage().props.siteLayout.header;
    const className = cn(
        'h-10 w-full max-w-full object-contain object-left',
        !onBrandRail &&
            'group-data-[collapsible=icon]:h-8 group-data-[collapsible=icon]:w-8',
    );

    if (onBrandRail) {
        return (
            // Rail is always cream — use the light logo in both appearances
            <AppLogoIcon
                className={className}
                alt={BRAND_NAME_TM}
                src={logoUrlLight}
            />
        );
    }

    return (
        <>
            <AppLogoIcon
                className={`${className} dark:hidden`}
                alt={BRAND_NAME_TM}
                src={logoUrlLight}
            />
            <AppLogoIcon
                className={`${className} hidden dark:block`}
                alt={BRAND_NAME_TM}
                src={logoUrlDark}
            />
        </>
    );
}
