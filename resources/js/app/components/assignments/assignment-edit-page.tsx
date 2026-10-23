import { AssignmentForm } from '@app/components/assignments/assignment-form';
import type {
    AssignmentData,
    BatchOption,
    StudentOption,
} from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    assignment: AssignmentData;
    batches: BatchOption[];
    batchStudents: Record<string, StudentOption[]>;
    portal: 'teacher' | 'training-manager';
};

export default function AssignmentEdit({
    assignment,
    batches,
    batchStudents,
    portal,
}: Props) {
    const base =
        portal === 'teacher'
            ? '/teacher/assignments'
            : '/training-manager/assignments';

    const isDraftLike =
        assignment.status === 'draft' ||
        assignment.status === 'rejected' ||
        assignment.status === 'pending_approval';

    return (
        <>
            <PrivateSeoHead title={`Edit ${assignment.title}`} />
            <div className="mx-auto max-w-3xl p-2 sm:px-4">
                <h1 className="mb-6 text-2xl font-semibold">Edit assignment</h1>
                <AssignmentForm
                    action={`${base}/${assignment.id}`}
                    method="put"
                    batches={batches}
                    batchStudents={batchStudents}
                    assignment={assignment}
                    submitLabel={
                        isDraftLike ? 'Publish assignment' : 'Save changes'
                    }
                    draftLabel="Save draft"
                    allowSaveDraft={isDraftLike}
                />
            </div>
        </>
    );
}
