import type { ReactNode } from 'react';
import { AssignmentAudioPlayer } from '@app/components/assignments/assignment-audio-player';
import type { AssignmentData } from '@app/types/assignments';

type Props = {
    assignment: AssignmentData;
    portal?: 'student' | 'teacher' | 'training-manager';
    children: ReactNode;
};

export function WorksheetLayout({ assignment, portal = 'student', children }: Props) {
    return (
        <div className="mx-auto max-w-3xl flex flex-col gap-6">
            <div>
                <h1 className="text-2xl font-semibold">{assignment.title}</h1>
                {assignment.instructions && (
                    <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                        {assignment.instructions}
                    </p>
                )}
            </div>

            {assignment.has_audio && (
                <AssignmentAudioPlayer
                    assignmentId={assignment.id}
                    label={assignment.audio_original_name}
                    portal={portal}
                />
            )}

            {children}
        </div>
    );
}

type SectionProps = {
    label: string;
    title?: string | null;
    instructions?: string | null;
    children: ReactNode;
};

export function WorksheetSection({
    label,
    title,
    instructions,
    children,
}: SectionProps) {
    return (
        <section className="grid gap-4 rounded-xl border border-border/70 p-4">
            <div>
                <h2 className="text-lg font-semibold">
                    {label}
                    {title ? `. ${title}` : ''}
                </h2>
                {instructions && (
                    <p className="mt-1 text-sm text-muted-foreground">
                        {instructions}
                    </p>
                )}
            </div>
            <div className="grid gap-4">{children}</div>
        </section>
    );
}

type ItemProps = {
    number: number;
    children: ReactNode;
};

export function WorksheetItem({ number, children }: ItemProps) {
    return (
        <div className="grid gap-2 sm:grid-cols-[auto_1fr] sm:gap-3">
            <span className="text-sm font-medium text-muted-foreground">
                {number}.
            </span>
            <div>{children}</div>
        </div>
    );
}
