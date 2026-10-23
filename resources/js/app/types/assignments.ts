export type AssignmentQuestionType =
    | 'mcq'
    | 'short_text'
    | 'fill_in_blank'
    | 'word_reorder'
    | 'pdf'
    | 'image'
    | 'audio'
    | 'link';

export type AssignmentStatus =
    | 'draft'
    | 'pending_approval'
    | 'rejected'
    | 'published'
    | 'archived';

export type AssignmentQuestion = {
    id?: number;
    section_id?: number;
    sort_order?: number;
    type: AssignmentQuestionType;
    type_label?: string;
    prompt: string;
    points: number;
    options?: string[];
    correct_index?: number;
    template?: string | null;
    hints?: string[];
    blank_count?: number | null;
    tokens?: string[];
    model_sentence?: string | null;
    model_answers?: string[][] | null;
    has_media?: boolean;
    media_original_name?: string | null;
    existing_media_path?: string | null;
    media_url?: string | null;
};

export type AssignmentSection = {
    id?: number;
    label: string;
    title?: string | null;
    instructions?: string | null;
    sort_order?: number;
    questions?: AssignmentQuestion[];
};

export type AssignmentFolder = {
    id: number;
    name: string;
    assignments_count: number;
};

export type AssignmentData = {
    id: number;
    batch_id: number;
    folder_id?: number | null;
    batch: {
        id: number;
        name: string;
        language: string;
        level: string;
    };
    title: string;
    instructions: string | null;
    due_at: string | null;
    max_score: number;
    status: AssignmentStatus;
    status_label: string;
    target_type: 'batch' | 'student';
    student_ids: number[];
    has_audio: boolean;
    audio_original_name: string | null;
    published_at: string | null;
    rejection_reason: string | null;
    created_by: { id: number; name: string };
    submissions_count?: number;
    turned_in_count?: number;
    assigned_count?: number;
    graded_count?: number;
    sections?: AssignmentSection[];
    questions?: AssignmentQuestion[];
    submission_status?: string;
    submission_id?: number;
};

export type SubmissionAnnotation = {
    id?: number;
    type: 'highlight' | 'memo' | 'pen';
    question_id: number | null;
    payload: Record<string, unknown>;
};

export type AssignmentSubmissionData = {
    id: number;
    assignment_id: number;
    student: { id: number; name: string; email: string };
    status: 'submitted' | 'graded';
    submitted_at: string;
    auto_score: number | null;
    final_score: number | null;
    score_overridden: boolean;
    feedback: string | null;
    graded_at: string | null;
    graded_by: { id: number; name: string } | null;
    answers: Array<{
        question_id: number;
        question: AssignmentQuestion;
        answer: {
            selected_index?: number;
            text?: string;
            blanks?: string[];
            ordered_tokens?: string[];
        };
        is_correct: boolean | null;
    }>;
    annotations?: SubmissionAnnotation[];
};

export type BatchOption = {
    id: number;
    name: string;
    language: string;
    level: string;
};

export type StudentOption = {
    id: number;
    name: string;
    email: string;
};

export type StudentWorkEntry = {
    student: StudentOption;
    status: 'assigned' | 'submitted' | 'graded';
    status_label: string;
    is_late: boolean;
    submission_id: number | null;
    submitted_at: string | null;
    final_score: number | null;
    auto_score: number | null;
};

export type StudentWorkSummary = {
    turned_in_count: number;
    assigned_count: number;
    graded_count: number;
    not_submitted_count: number;
};

export type QuestionDraft = {
    clientId: string;
    type: AssignmentQuestionType;
    prompt: string;
    points: number;
    options: string[];
    correct_index: number;
    template: string;
    hints: string[];
    tokens: string[];
    model_sentence: string;
    model_answers: string[];
    mediaFile: File | null;
    has_media: boolean;
    media_original_name: string;
    existing_media_path: string;
    media_url: string;
};

export type SectionDraft = {
    clientId: string;
    label: string;
    title: string;
    instructions: string;
    questions: QuestionDraft[];
};

export function isMediaFileType(
    type: AssignmentQuestionType,
): type is 'pdf' | 'image' | 'audio' {
    return type === 'pdf' || type === 'image' || type === 'audio';
}

export function isMediaItemType(type: AssignmentQuestionType): boolean {
    return isMediaFileType(type) || type === 'link';
}
