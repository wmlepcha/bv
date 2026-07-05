import type { SiteLayoutNavigationItem } from '@/types/global';

export type MarketingNavLink = SiteLayoutNavigationItem;

export function isMarketingNavLinkActive(url: string, href: string): boolean {
    if (!url) {
        return false;
    }

    if (href === '/') {
        return url === '/' || url === '';
    }

    return url === href || url.startsWith(`${href}/`);
}

export function getMarketingNavActiveIndex(
    url: string,
    links: MarketingNavLink[],
): number {
    const index = links.findIndex((link) => isMarketingNavLinkActive(url, link.href));

    return index === -1 ? 0 : index;
}
