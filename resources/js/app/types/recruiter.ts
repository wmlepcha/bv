export type CandidateCard = {
    id: number;
    name: string;
    student_number: string | null;
    headline: string | null;
    photo_url: string | null;
    experience: 'fresher' | 'experienced';
    language_level: string | null;
    viewable: boolean;
    placement_status: 'open' | 'hired';
    placement_label: string;
    professions: string[];
    entry?: ProjectEntry;
};

export type LanguageAssignment = {
    id: number;
    title: string;
    due_at: string | null;
    status: 'graded' | 'submitted' | 'pending' | 'overdue';
    score: number | null;
    max_score: number;
    percent: number | null;
};

export type LanguageProgress = {
    course: {
        language_label: string;
        level: string;
        batch_name: string;
        center_name: string | null;
        trainer_name: string | null;
        starts_on: string | null;
        ends_on: string | null;
        enrolled_on: string | null;
        elapsed_percent: number | null;
    } | null;
    assignments: {
        total: number;
        graded: number;
        submitted: number;
        pending: number;
        overdue: number;
        average_percent: number | null;
        items: LanguageAssignment[];
    };
    attendance: {
        total_sessions: number;
        attended: number;
        excused: number;
        absent: number;
        percentage: number | null;
        recent: { date: string; status: 'present' | 'excused' | 'absent' }[];
    };
    self_assessment: {
        level: string;
        confidence: number;
        most_improved: string;
        needs_improvement: string;
    } | null;
    study_bank_saved: number;
};

export type ProjectRequirement = {
    id: number;
    title: string;
    uploaded: boolean;
    file_name: string | null;
    download_url: string | null;
    view_url: string | null;
    review_status: 'pending' | 'approved' | 'rejected';
    rejection_note: string | null;
    sample_name: string | null;
    sample_url: string | null;
    sample_view_url: string | null;
};

export type ProjectEntry = {
    status:
        | 'draft'
        | 'awaiting'
        | 'ready'
        | 'sent'
        | 'changes_requested'
        | 'approved';
    uploaded: number;
    total: number;
    approved_at: string | null;
    requirements: ProjectRequirement[];
};

export type ProjectStage = 'draft' | 'in_progress' | 'approved';

export type ProjectSummary = {
    id: number;
    name: string;
    country: string;
    sector: string;
    timeline: string;
    candidates_count: number;
    approved_count: number;
    finalized: boolean;
    stage: ProjectStage;
    requirement_template: string[];
};

export type ShortlistProject = {
    id: number;
    name: string;
    added: boolean;
};
