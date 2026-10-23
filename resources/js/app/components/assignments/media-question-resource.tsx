import { ExternalLink } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { isMediaFileType  } from '@app/types/assignments';
import type {AssignmentQuestion} from '@app/types/assignments';

export function questionMediaStreamUrl(
    assignmentId: number,
    questionId: number,
    portal: 'student' | 'training-manager' | 'teacher',
): string {
    const base =
        portal === 'student'
            ? '/student/assignments'
            : portal === 'teacher'
              ? '/teacher/assignments'
              : '/training-manager/assignments';

    return `${base}/${assignmentId}/questions/${questionId}/media`;
}

export function MediaQuestionResource({
    assignmentId,
    question,
    portal,
}: {
    assignmentId: number;
    question: AssignmentQuestion;
    portal: 'student' | 'training-manager' | 'teacher';
}) {
    if (question.type === 'link') {
        const url = question.media_url ?? '';

        if (!url) {
            return null;
        }

        let hostname = url;

        try {
            hostname = new URL(url).hostname;
        } catch {
            // keep raw url
        }

        return (
            <div className="rounded-xl border border-border/70 bg-card p-4">
                <p className="mb-3 text-sm text-muted-foreground">{hostname}</p>
                <Button asChild variant="default">
                    <a
                        href={url}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <ExternalLink className="mr-2 size-4" />
                        Open resource
                    </a>
                </Button>
            </div>
        );
    }

    if (!isMediaFileType(question.type) || !question.id || !question.has_media) {
        return null;
    }

    const src = questionMediaStreamUrl(assignmentId, question.id, portal);

    if (question.type === 'pdf') {
        return (
            <div className="grid gap-2">
                <div className="overflow-hidden rounded-xl border border-border/70 bg-card">
                    <iframe
                        title={question.media_original_name ?? 'PDF'}
                        src={src}
                        className="h-[28rem] w-full"
                    />
                </div>
                <a
                    href={src}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-sm text-primary underline-offset-4 hover:underline"
                >
                    Download PDF
                    {question.media_original_name
                        ? ` (${question.media_original_name})`
                        : ''}
                </a>
            </div>
        );
    }

    if (question.type === 'image') {
        return (
            <div className="overflow-hidden rounded-xl border border-border/70 bg-card p-2">
                <img
                    src={src}
                    alt={question.media_original_name ?? 'Assignment image'}
                    className="mx-auto max-h-[28rem] w-auto max-w-full object-contain"
                />
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-border/70 bg-card p-4">
            <p className="mb-2 text-sm font-medium">
                {question.media_original_name ?? 'Audio'}
            </p>
            <audio controls className="w-full" src={src}>
                Your browser does not support audio playback.
            </audio>
        </div>
    );
}
