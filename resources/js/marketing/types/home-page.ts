export type HomePageTestimonial = {
    quote: string;
    author: string;
    role: string;
};

export type HomePageContentSection = {
    badge: string;
    heading: string;
    body: string;
    imageUrl: string;
    imageAlt: string;
    testimonial: HomePageTestimonial | null;
};

export type HomePageCraftingSection = HomePageContentSection & {
    pillars: { label: string }[];
};

export type HomePageRecruitersSection = HomePageContentSection & {
    benefits: { label: string }[];
};

export type HomePageProps = {
    hero: {
        badge: string;
        headline: string;
        subtitle: string;
        cardText: string;
        highlights: { label: string }[];
        stats: { value: number; suffix: string; label: string }[];
    };
    crafting: HomePageCraftingSection;
    recruiters: HomePageRecruitersSection;
    services: {
        heading: string;
        subtitle: string;
        cards: { title: string; href: string; imageUrl: string }[];
    };
    seo: {
        title: string;
        description: string;
    };
};
