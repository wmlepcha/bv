import { Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { AssignmentCard } from '@app/components/assignments/assignment-card';
import type { AssignmentData, AssignmentStatus } from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

export type AssignmentListTab = 'published' | 'draft';

type Props = {
    assignments: AssignmentData[];
    basePath: string;
    createPath: string;
    initialTab?: AssignmentListTab;
};

const TABS: { value: AssignmentListTab; label: string }[] = [
    { value: 'published', label: 'Published' },
    { value: 'draft', label: 'Drafts' },
];

function matchesTab(status: AssignmentStatus, tab: AssignmentListTab): boolean {
    if (tab === 'published') {
        return status === 'published';
    }

    return (
        status === 'draft' ||
        status === 'rejected' ||
        status === 'pending_approval'
    );
}

function emptyMessage(tab: AssignmentListTab): string {
    if (tab === 'published') {
        return 'No published assignments yet.';
    }

    return 'No draft assignments yet.';
}

export default function AssignmentsIndex({
    assignments,
    basePath,
    createPath,
    initialTab = 'published',
}: Props) {
    const [tab, setTab] = useState<AssignmentListTab>(initialTab);

    useEffect(() => {
        setTab(initialTab);
    }, [initialTab]);

    const selectTab = (nextTab: AssignmentListTab) => {
        setTab(nextTab);
        router.get(
            basePath,
            { status: nextTab },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const counts = useMemo(() => {
        const tally: Record<AssignmentListTab, number> = {
            published: 0,
            draft: 0,
        };

        for (const assignment of assignments) {
            for (const entry of TABS) {
                if (matchesTab(assignment.status, entry.value)) {
                    tally[entry.value] += 1;
                }
            }
        }

        return tally;
    }, [assignments]);

    const visibleAssignments = useMemo(
        () => assignments.filter((assignment) => matchesTab(assignment.status, tab)),
        [assignments, tab],
    );

    return (
        <>
            <PrivateSeoHead title="Assignments" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Assignments
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create, review, and track batch assignments.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={createPath}>Create assignment</Link>
                    </Button>
                </div>

                <div
                    className="flex gap-1 overflow-x-auto border-b border-border"
                    role="tablist"
                >
                    {TABS.map((entry) => (
                        <button
                            key={entry.value}
                            type="button"
                            role="tab"
                            aria-selected={tab === entry.value}
                            className={cn(
                                'shrink-0 border-b-2 px-4 py-2 text-sm font-medium transition',
                                tab === entry.value
                                    ? 'border-primary text-foreground'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            )}
                            onClick={() => selectTab(entry.value)}
                        >
                            {entry.label}
                            <span className="ml-1.5 tabular-nums text-muted-foreground">
                                ({counts[entry.value]})
                            </span>
                        </button>
                    ))}
                </div>

                {visibleAssignments.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {assignments.length === 0
                            ? 'No assignments yet.'
                            : emptyMessage(tab)}
                    </p>
                ) : (
                    <div
                        className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
                        role="tabpanel"
                    >
                        {visibleAssignments.map((assignment) => (
                            <AssignmentCard
                                key={assignment.id}
                                assignment={assignment}
                                basePath={basePath}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
