import { Link } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { AssignmentAudioPlayer } from '@app/components/assignments/assignment-audio-player';
import { AssignmentStatusBadge } from '@app/components/assignments/assignment-status-badge';
import { StudentWorkPage } from '@app/components/assignments/student-work-page';
import type {
    AssignmentData,
    StudentWorkEntry,
    StudentWorkSummary,
} from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    assignment: AssignmentData;
    canEdit: boolean;
    portal: 'teacher' | 'training-manager';
    canDuplicate?: boolean;
    canGrade?: boolean;
    roster?: StudentWorkEntry[];
    summary?: StudentWorkSummary;
};

type Tab = 'student-work' | 'instructions';

export default function AssignmentShow({
    assignment,
    canEdit,
    portal,
    canDuplicate = false,
    canGrade = false,
    roster = [],
    summary = {
        turned_in_count: 0,
        assigned_count: 0,
        graded_count: 0,
        not_submitted_count: 0,
    },
}: Props) {
    const base =
        portal === 'teacher'
            ? '/teacher/assignments'
            : '/training-manager/assignments';
    const isPublished = assignment.status === 'published';
    const [tab, setTab] = useState<Tab>(
        isPublished ? 'student-work' : 'instructions',
    );

    const showStudentWork = isPublished && roster.length >= 0;

    return (
        <>
            <PrivateSeoHead title={assignment.title} />
            <div className="mx-auto flex max-w-3xl flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            {assignment.title}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {assignment.batch.name}
                        </p>
                    </div>
                    <AssignmentStatusBadge
                        status={assignment.status}
                        label={assignment.status_label}
                    />
                </div>

                {showStudentWork && (
                    <div
                        className="flex gap-1 border-b border-border"
                        role="tablist"
                    >
                        <button
                            type="button"
                            role="tab"
                            aria-selected={tab === 'student-work'}
                            className={cn(
                                'border-b-2 px-4 py-2 text-sm font-medium transition',
                                tab === 'student-work'
                                    ? 'border-primary text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                            onClick={() => setTab('student-work')}
                        >
                            Student work
                        </button>
                        <button
                            type="button"
                            role="tab"
                            aria-selected={tab === 'instructions'}
                            className={cn(
                                'border-b-2 px-4 py-2 text-sm font-medium transition',
                                tab === 'instructions'
                                    ? 'border-primary text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                            onClick={() => setTab('instructions')}
                        >
                            Instructions
                        </button>
                    </div>
                )}

                {tab === 'student-work' && showStudentWork ? (
                    <StudentWorkPage
                        assignment={assignment}
                        roster={roster}
                        summary={summary}
                        portal={portal}
                        canGrade={canGrade}
                        embedded
                    />
                ) : (
                    <>
                        {assignment.rejection_reason && (
                            <p className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-900">
                                Rejected: {assignment.rejection_reason}
                            </p>
                        )}

                        {assignment.instructions && (
                            <p className="text-sm leading-relaxed">
                                {assignment.instructions}
                            </p>
                        )}

                        {assignment.has_audio && (
                            <AssignmentAudioPlayer
                                assignmentId={assignment.id}
                                label={assignment.audio_original_name}
                                portal={portal}
                            />
                        )}

                        <div className="grid gap-4">
                            {(assignment.sections &&
                            assignment.sections.length > 0
                                ? assignment.sections
                                : [
                                      {
                                          label: 'General',
                                          questions: assignment.questions,
                                      },
                                  ]
                            ).map((section, sectionIndex) => (
                                <div
                                    key={section.id ?? sectionIndex}
                                    className="rounded-xl border border-border/70 p-4"
                                >
                                    <h3 className="font-medium">
                                        {section.label}
                                        {section.title
                                            ? `. ${section.title}`
                                            : ''}
                                    </h3>
                                    {section.instructions && (
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {section.instructions}
                                        </p>
                                    )}
                                    <div className="mt-3 grid gap-3">
                                        {(section.questions ?? []).map(
                                            (question, index) => (
                                                <div
                                                    key={question.id ?? index}
                                                >
                                                    <p className="text-sm font-medium">
                                                        {index + 1}.{' '}
                                                        {question.prompt}
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {question.type_label ??
                                                            question.type}{' '}
                                                        · {question.points}{' '}
                                                        pt(s)
                                                    </p>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </>
                )}

                <div className="flex flex-wrap gap-2">
                    {canDuplicate && (
                        <Button variant="outline" asChild>
                            <Link href={`${base}/${assignment.id}/duplicate`}>
                                Duplicate to batch
                            </Link>
                        </Button>
                    )}
                    {canEdit && (
                        <Button asChild variant="outline">
                            <Link href={`${base}/${assignment.id}/edit`}>
                                Edit
                            </Link>
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}
