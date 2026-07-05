export type JourneyLayout = 'center' | 'text-left' | 'text-right';

export type JourneyMilestone = {
    id: string;
    period: string;
    title: string;
    bullets: string[];
    layout: JourneyLayout;
    imageUrl?: string | null;
    imageAlt?: string | null;
};

export type CoreValue = {
    letter: string;
    title: string;
    description: string;
};

export type AboutPageProps = {
    hero: {
        title: string;
    };
    overview: {
        tagline: string;
        paragraphs: string[];
    };
    mission: string;
    vision: string;
    leapfrog: {
        title: string;
        subtitle: string;
        paragraphs: string[];
    };
    coreValues: {
        intro: string[];
        values: CoreValue[];
    };
    impact: {
        paragraph: string;
        closing: string;
    };
    journey: {
        milestones: JourneyMilestone[];
    };
    seo: {
        title: string;
        description: string;
    };
};
