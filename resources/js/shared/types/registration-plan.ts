export type RegistrationPlan = {
    id: number;
    name: string;
    description: string | null;
    price_rupees: number;
    duration_months: number;
    features: string[];
    badge_text: string | null;
    is_recommended: boolean;
};
