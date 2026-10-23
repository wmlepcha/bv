import {
    DEFAULT_HIGHLIGHT_COLOR,
    DEFAULT_MEMO_COLOR,
    DEFAULT_PEN_COLOR,
    STROKE_WIDTHS,
} from '@app/components/assignments/grading/grading-colors';
import type { GradingAnnotation } from '@app/components/assignments/grading/grading-types';
import { ensureClientIds } from '@app/components/assignments/grading/grading-types';
import {
    GradingAnswerBlock
    
} from '@app/components/assignments/grading-workspace';
import type {GradingTool} from '@app/components/assignments/grading-workspace';
import { MediaQuestionResource } from '@app/components/assignments/media-question-resource';
import { isMediaItemType } from '@app/types/assignments';
import type {
    AssignmentQuestion,
    SubmissionAnnotation,
} from '@app/types/assignments';

type AnswerPayload = {
    selected_index?: number;
    text?: string;
    blanks?: string[];
    ordered_tokens?: string[];
};

export function formatAnswerDisplayText(
    question: AssignmentQuestion,
    answer: AnswerPayload,
): string {
    if (question.type === 'mcq') {
        const index = answer.selected_index;

        if (index === undefined) {
            return '';
        }

        return question.options?.[index] ?? '';
    }

    if (question.type === 'fill_in_blank') {
        const template = question.template ?? question.prompt;
        const blanks = answer.blanks ?? [];
        let blankIndex = 0;

        return template.replace(/___/g, () => blanks[blankIndex++] ?? '___');
    }

    if (question.type === 'word_reorder') {
        return (answer.ordered_tokens ?? []).join(' ');
    }

    return answer.text ?? '';
}

type Props = {
    questionId: number;
    question: AssignmentQuestion;
    answer: AnswerPayload;
    isCorrect?: boolean | null;
    tool?: GradingTool;
    penColor?: string;
    highlightColor?: string;
    memoColor?: string;
    strokeWidth?: number;
    annotations: SubmissionAnnotation[] | GradingAnnotation[];
    selectedClientId?: string | null;
    onSelect?: (clientId: string | null) => void;
    onMutate?: (
        updater: (current: GradingAnnotation[]) => GradingAnnotation[],
    ) => void;
    onMemoPlaced?: (clientId: string) => void;
    autoFocusClientId?: string | null;
    onAutoFocusComplete?: () => void;
    readOnly?: boolean;
    assignmentId?: number;
    portal?: 'student' | 'training-manager' | 'teacher';
};

export function GradableAnswerCard({
    questionId,
    question,
    answer,
    isCorrect,
    tool = 'select',
    penColor = DEFAULT_PEN_COLOR,
    highlightColor = DEFAULT_HIGHLIGHT_COLOR,
    memoColor = DEFAULT_MEMO_COLOR,
    strokeWidth = STROKE_WIDTHS[1],
    annotations,
    selectedClientId = null,
    onSelect,
    onMutate,
    onMemoPlaced,
    autoFocusClientId,
    onAutoFocusComplete,
    readOnly = false,
    assignmentId,
    portal = 'training-manager',
}: Props) {
    const displayText = formatAnswerDisplayText(question, answer);
    const gradingAnnotations = ensureClientIds(annotations);

    const meta =
        question.type === 'mcq' ? (
            <p className="text-xs text-muted-foreground">
                {isCorrect === true && 'Correct'}
                {isCorrect === false && 'Incorrect'}
                {isCorrect !== null && isCorrect !== undefined && (
                    <> · {isCorrect ? question.points : 0} / {question.points} pts</>
                )}
            </p>
        ) : isMediaItemType(question.type) ? (
            <p className="text-xs text-muted-foreground">
                {question.type_label ?? question.type}
                {question.media_original_name
                    ? ` · ${question.media_original_name}`
                    : question.media_url
                      ? ` · ${question.media_url}`
                      : ''}
            </p>
        ) : null;

    return (
        <div className="grid gap-2">
            {meta}
            {assignmentId !== undefined && isMediaItemType(question.type) && (
                <MediaQuestionResource
                    assignmentId={assignmentId}
                    question={question}
                    portal={portal}
                />
            )}
            <GradingAnswerBlock
                questionId={questionId}
                prompt={question.prompt}
                answerText={displayText}
                tool={tool}
                penColor={penColor}
                highlightColor={highlightColor}
                memoColor={memoColor}
                strokeWidth={strokeWidth}
                annotations={gradingAnnotations}
                selectedClientId={selectedClientId}
                onSelect={onSelect ?? (() => undefined)}
                onMutate={onMutate ?? (() => undefined)}
                onMemoPlaced={onMemoPlaced}
                autoFocusClientId={autoFocusClientId}
                onAutoFocusComplete={onAutoFocusComplete}
                readOnly={readOnly}
            />
        </div>
    );
}
