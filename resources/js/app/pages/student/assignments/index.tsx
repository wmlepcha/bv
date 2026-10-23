import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { AssignmentStatusBadge } from '@app/components/assignments/assignment-status-badge';
import type { AssignmentData } from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = { assignments: AssignmentData[] };

export default function StudentAssignmentsIndex({ assignments }: Props) {
    return (
        <>
            <PrivateSeoHead title="Assignments" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                        Assignments
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Complete your batch assignments here.
                    </p>
                </div>
                <div className="grid gap-3 max-md:gap-2.5">
                    {assignments.map((assignment) => (
                        <Link
                            key={assignment.id}
                            href={`/student/assignments/${assignment.id}`}
                            className="rounded-2xl border border-border/70 bg-card p-4 transition-transform active:scale-[0.98] max-md:flex max-md:items-center max-md:gap-3"
                        >
                            <div className="min-w-0 flex-1">
                                <div className="flex items-center justify-between gap-2">
                                    <p className="font-medium">
                                        {assignment.title}
                                    </p>
                                    {assignment.submission_status ===
                                    'graded' ? (
                                        <AssignmentStatusBadge
                                            status="published"
                                            label="Graded"
                                        />
                                    ) : assignment.submission_status ===
                                      'submitted' ? (
                                        <AssignmentStatusBadge
                                            status="published"
                                            label="Submitted"
                                        />
                                    ) : (
                                        <AssignmentStatusBadge
                                            status="published"
                                            label="Open"
                                        />
                                    )}
                                </div>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {assignment.batch.name}
                                    {assignment.due_at &&
                                        ` · Due ${new Date(assignment.due_at).toLocaleString()}`}
                                </p>
                            </div>
                            <ChevronRight className="size-5 shrink-0 text-muted-foreground/60 md:hidden" />
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}

StudentAssignmentsIndex.layout = {
    breadcrumbs: [{ title: 'Assignments', href: '/student/assignments' }],
};
