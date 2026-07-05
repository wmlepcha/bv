import type { Auth } from '@/types/auth';

export type SiteLayoutNavigationItem = {
    label: string;
    href: string;
    icon: string;
};

export type SiteLayoutPartner = {
    name: string;
    imageUrl: string;
    altText: string;
};

export type SiteLayoutOffice = {
    name: string;
    address: string;
};

export type SiteLayoutSocialLink = {
    label: string;
    url: string;
};

export type SiteLayout = {
    header: {
        logoUrl: string;
        partnerLabel: string;
        navigation: SiteLayoutNavigationItem[];
        partners: SiteLayoutPartner[];
    };
    footer: {
        contactEmail: string;
        contactPhone: string;
        copyright: string;
        offices: SiteLayoutOffice[];
        socialLinks: SiteLayoutSocialLink[];
    };
};

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            appUrl: string;
            seo: {
                siteName: string;
                defaultDescription: string;
                defaultOgImage: string;
                twitterHandle: string | null;
            };
            auth: Auth;
            sidebarOpen: boolean;
            razorpayKey: string | null;
            registrationFeeAmount: number;
            siteLayout: SiteLayout;
            [key: string]: unknown;
        };
    }
}
