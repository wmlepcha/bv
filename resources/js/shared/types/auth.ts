export type UserRole =
    | 'student'
    | 'recruiter'
    | 'employee'
    | 'admin'
    | 'teacher'
    | 'training_manager';

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    role: UserRole;
    student_number: string | null;
    registration_paid_at: string | null;
    registration_expires_at: string | null;
    registration_plan_id: number | null;
    can_access_dashboard: boolean;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
