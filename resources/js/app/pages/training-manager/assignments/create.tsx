import { AssignmentForm } from '@app/components/assignments/assignment-form';
import type { BatchOption, StudentOption } from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    batches: BatchOption[];
    batchStudents: Record<string, StudentOption[]>;
};

export default function TrainingManagerCreateAssignment({
    batches,
    batchStudents,
}: Props) {
    return (
        <>
            <PrivateSeoHead title="Create assignment" />
            <div className="mx-auto max-w-3xl p-2 sm:px-4">
                <h1 className="mb-6 text-2xl font-semibold">Create assignment</h1>
                <AssignmentForm
                    action="/training-manager/assignments"
                    method="post"
                    batches={batches}
                    batchStudents={batchStudents}
                    submitLabel="Publish assignment"
                    draftLabel="Save draft"
                    allowSaveDraft
                />
            </div>
        </>
    );
}

TrainingManagerCreateAssignment.layout = {
    breadcrumbs: [
        { title: 'Assignments', href: '/training-manager/assignments' },
        { title: 'Create', href: '/training-manager/assignments/create' },
    ],
};
