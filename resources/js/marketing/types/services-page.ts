export type ServiceItemKind = 'course' | 'placement';

export type ServiceItem = {
    id: string;
    title: string;
    familyLabel?: string | null;
    familyDescription?: string | null;
    kind: ServiceItemKind;
    deliveryModes?: string[];
    levelRange?: string | null;
    hours?: string | null;
    durationMonths?: string | null;
    centres?: string[];
    intakePeriods?: string[];
    venues?: string[];
    timeline?: string | null;
    certificates?: string[];
    sectors?: string[];
    notes?: string | null;
    imageUrl?: string | null;
    imageAlt?: string | null;
};

export type ServiceGroup = {
    id: string;
    title: string;
    description?: string | null;
    items: ServiceItem[];
};

export type ServiceCategory = {
    id: string;
    title: string;
    groups: ServiceGroup[];
};

export type ServicesPageProps = {
    hero: {
        title: string;
        subtitle?: string;
    };
    categories: ServiceCategory[];
    seo: {
        title: string;
        description: string;
    };
};
