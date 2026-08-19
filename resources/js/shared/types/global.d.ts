import type { Auth } from '@/types/auth';
import type { RegistrationPlan } from '@shared/types/registration-plan';

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
        logoUrlLight: string;
        logoUrlDark: string;
        partnerLabel: string;
        showAppearanceToggle: boolean;
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

export type InquirySelectOption = {
    value: string;
    label: string;
};

export type InquiryOptions = {
    educationLevels: InquirySelectOption[];
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
            cashfreeAppId: string | null;
            registrationPlans: RegistrationPlan[];
            registrationTermsHtml: string;
            studentPhone: string | null;
            siteLayout: SiteLayout;
            inquiryOptions: InquiryOptions;
            [key: string]: unknown;
        };
    }
}
