import type { SubmissionAnnotation } from '@app/types/assignments';

export type GradingTool = 'select' | 'highlight' | 'memo' | 'pen';

export type GradingAnnotation = SubmissionAnnotation & {
    clientId: string;
};

export function cloneAnnotations(
    annotations: GradingAnnotation[],
): GradingAnnotation[] {
    return annotations.map((annotation) => ({
        ...annotation,
        payload: { ...annotation.payload },
    }));
}

export function ensureClientIds(
    annotations: SubmissionAnnotation[],
): GradingAnnotation[] {
    return annotations.map((annotation) => ({
        ...annotation,
        clientId:
            (annotation as GradingAnnotation).clientId ??
            crypto.randomUUID(),
    }));
}

export function stripClientIds(
    annotations: GradingAnnotation[],
): SubmissionAnnotation[] {
    return annotations.map(({ clientId: _clientId, ...annotation }) => annotation);
}

export function newAnnotation(
    partial: Omit<GradingAnnotation, 'clientId'> & {
        clientId?: string;
    },
): GradingAnnotation {
    return {
        ...partial,
        clientId: partial.clientId ?? crypto.randomUUID(),
    };
}
