import type { QuestionDraft, SectionDraft } from '@app/types/assignments';

/** Matches server StoreAssignmentRequest limits (KB → bytes). */
export const ASSIGNMENT_AUDIO_MAX_BYTES = 20 * 1024 * 1024;
export const ASSIGNMENT_MEDIA_MAX_BYTES = 20 * 1024 * 1024;
export const ASSIGNMENT_IMAGE_MAX_BYTES = 10 * 1024 * 1024;

export function mediaMaxBytesForType(
    type: QuestionDraft['type'],
): number {
    if (type === 'image') {
        return ASSIGNMENT_IMAGE_MAX_BYTES;
    }

    return ASSIGNMENT_MEDIA_MAX_BYTES;
}

export function mediaSizeErrorForType(
    type: QuestionDraft['type'],
): string {
    if (type === 'image') {
        return 'Images must be 10MB or smaller.';
    }

    if (type === 'pdf') {
        return 'PDF files must be 20MB or smaller.';
    }

    if (type === 'audio') {
        return 'Audio files must be 20MB or smaller.';
    }

    return 'Files must be 20MB or smaller.';
}

export function normalizeBlankTemplate(template: string): string {
    return template.replace(/_{3,}/g, '___');
}

export function countBlanks(template: string): number {
    return (normalizeBlankTemplate(template).match(/___/g) ?? []).length;
}

export type SectionValidationIssue = {
    message: string;
    questionIndex: number;
};

export type SectionsValidationIssue = {
    message: string;
    sectionIndex: number;
    questionIndex: number;
};

export function validateQuestion(
    question: QuestionDraft,
    itemNumber?: number,
): string | null {
    const label = itemNumber !== undefined ? `Item ${itemNumber}` : 'This item';

    if (question.type === 'fill_in_blank') {
        const template = normalizeBlankTemplate(
            question.template || question.prompt,
        );

        if (!template.trim()) {
            return `${label}: add a sentence with ___ for each blank.`;
        }

        if (countBlanks(template) < 1) {
            return `${label}: use ___ to mark at least one blank.`;
        }

        return null;
    }

    if (question.type === 'word_reorder') {
        const tokens = question.tokens
            .map((token) => token.trim())
            .filter(Boolean);

        if (tokens.length < 2) {
            return `${label}: add at least two words separated by /.`;
        }

        return null;
    }

    if (question.type === 'mcq') {
        if (!question.prompt.trim()) {
            return `${label}: add a prompt.`;
        }

        const options = question.options
            .map((option) => option.trim())
            .filter(Boolean);

        if (options.length < 2) {
            return `${label}: add at least two answer options.`;
        }

        return null;
    }

    if (question.type === 'link') {
        if (!question.prompt.trim()) {
            return `${label}: add instructions for this link.`;
        }

        if (!question.media_url.trim()) {
            return `${label}: add a link URL.`;
        }

        return null;
    }

    if (
        question.type === 'pdf' ||
        question.type === 'image' ||
        question.type === 'audio'
    ) {
        if (!question.prompt.trim()) {
            return `${label}: add instructions for this item.`;
        }

        if (
            !question.mediaFile &&
            !question.has_media &&
            !question.existing_media_path
        ) {
            return `${label}: upload a ${question.type === 'pdf' ? 'PDF' : question.type} file.`;
        }

        if (
            question.mediaFile !== null &&
            question.mediaFile.size > mediaMaxBytesForType(question.type)
        ) {
            return `${label}: ${mediaSizeErrorForType(question.type)}`;
        }

        return null;
    }

    if (!question.prompt.trim()) {
        return `${label}: add a prompt.`;
    }

    return null;
}

export function validateSection(
    section: SectionDraft,
): SectionValidationIssue | null {
    if (!section.label.trim()) {
        return {
            message: 'Section needs a label (e.g. A).',
            questionIndex: 0,
        };
    }

    if (section.questions.length === 0) {
        return {
            message: 'Section needs at least one item.',
            questionIndex: 0,
        };
    }

    for (let index = 0; index < section.questions.length; index++) {
        const message = validateQuestion(section.questions[index], index + 1);

        if (message) {
            return { message, questionIndex: index };
        }
    }

    return null;
}

export function validateSections(
    sections: SectionDraft[],
): SectionsValidationIssue | null {
    if (sections.length === 0) {
        return {
            message: 'Add at least one worksheet section.',
            sectionIndex: 0,
            questionIndex: 0,
        };
    }

    for (let sectionIndex = 0; sectionIndex < sections.length; sectionIndex++) {
        const section = sections[sectionIndex];
        const issue = validateSection(section);

        if (issue) {
            const sectionName =
                section.label.trim() || String.fromCharCode(65 + sectionIndex);

            return {
                message: `Section ${sectionName}: ${issue.message}`,
                sectionIndex,
                questionIndex: issue.questionIndex,
            };
        }
    }

    return null;
}

export function firstServerErrorMessage(
    errors: Record<string, string | string[]>,
): string | null {
    for (const value of Object.values(errors)) {
        if (Array.isArray(value)) {
            const first = value.find(
                (item): item is string =>
                    typeof item === 'string' && item.trim() !== '',
            );

            if (first) {
                return first;
            }

            continue;
        }

        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }
    }

    return null;
}

export function sectionIndexFromServerErrors(
    errors: Record<string, string>,
): number | null {
    for (const key of Object.keys(errors)) {
        const match = key.match(/^sections\.(\d+)/);

        if (match) {
            return Number(match[1]);
        }
    }

    return null;
}

export type StudentAnswerState = {
    selected_index?: number;
    text?: string;
    blanks?: string[];
    ordered_tokens?: string[];
};

export type StudentAnswerValidationIssue = {
    message: string;
    questionId: number;
};

export function validateStudentAnswer(
    question: {
        id?: number;
        type: string;
        blank_count?: number | null;
        tokens?: string[] | null;
    },
    answer: StudentAnswerState,
    itemNumber?: number,
): string | null {
    const label = itemNumber !== undefined ? `Item ${itemNumber}` : 'This item';

    if (question.type === 'mcq') {
        if (answer.selected_index === undefined) {
            return `${label}: select an option.`;
        }

        return null;
    }

    if (question.type === 'fill_in_blank') {
        const blankCount = question.blank_count ?? 1;
        const blanks = answer.blanks ?? [];

        if (
            blanks.length < blankCount ||
            blanks.some((blank) => blank.trim() === '')
        ) {
            return `${label}: fill in all blanks.`;
        }

        return null;
    }

    if (question.type === 'word_reorder') {
        const expectedCount = question.tokens?.length ?? 0;
        const ordered = answer.ordered_tokens ?? [];

        if (ordered.length !== expectedCount) {
            return `${label}: use all word tiles from the bank.`;
        }

        return null;
    }

    if (!answer.text?.trim()) {
        return `${label}: enter an answer.`;
    }

    return null;
}

export function validateStudentAnswers(
    assignment: {
        sections?: Array<{
            label: string;
            questions?: Array<{
                id?: number;
                type: string;
                blank_count?: number | null;
                tokens?: string[] | null;
            }>;
        }>;
        questions?: Array<{
            id?: number;
            type: string;
            blank_count?: number | null;
            tokens?: string[] | null;
        }>;
    },
    answers: Record<number, StudentAnswerState>,
): StudentAnswerValidationIssue | null {
    const sections = assignment.sections ?? [];

    if (sections.length > 0) {
        for (const section of sections) {
            for (const [index, question] of (section.questions ?? []).entries()) {
                if (question.id === undefined) {
                    continue;
                }

                const message = validateStudentAnswer(
                    question,
                    answers[question.id] ?? {},
                    index + 1,
                );

                if (message) {
                    const sectionName = section.label.trim() || 'Worksheet';

                    return {
                        message: `Section ${sectionName}: ${message}`,
                        questionId: question.id,
                    };
                }
            }
        }

        return null;
    }

    for (const [index, question] of (assignment.questions ?? []).entries()) {
        if (question.id === undefined) {
            continue;
        }

        const message = validateStudentAnswer(
            question,
            answers[question.id] ?? {},
            index + 1,
        );

        if (message) {
            return {
                message,
                questionId: question.id,
            };
        }
    }

    return null;
}

export function questionIdFromServerErrors(
    errors: Record<string, string>,
): number | null {
    for (const key of Object.keys(errors)) {
        const match = key.match(/^answers\.(\d+)/);

        if (match) {
            return Number(match[1]);
        }
    }

    return null;
}
