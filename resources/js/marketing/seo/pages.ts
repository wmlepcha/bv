export type MarketingSeoPage = {
    title: string;
    description: string;
    canonical: string;
    noindex?: boolean;
};

export const marketingSeoPages = {
    home: {
        title: 'Language Training & Career Support in Sikkim',
        description:
            'Bilingual Vitae helps people in Sikkim become workplace-ready through language training, employability skills, placement support, and migration guidance.',
        canonical: '/',
    },
    about: {
        title: 'About Us',
        description:
            'Bilingual Vitae is an EdTech and future work company specializing in bilingual proficiency profiling, language training, and global employability — empowering local talent with technology-driven career pathways.',
        canonical: '/about',
    },
    services: {
        title: 'Products & Services',
        description:
            'Explore Bilingual Vitae courses in German, Japanese, French, and Spanish, employability training, expatriate programmes, and placement support in Germany and Austria.',
        canonical: '/services',
    },
    projects: {
        title: 'Projects',
        description:
            'Discover Bilingual Vitae projects and initiatives advancing language education, employability, and workforce development across Sikkim and beyond.',
        canonical: '/projects',
    },
} as const satisfies Record<string, MarketingSeoPage>;
