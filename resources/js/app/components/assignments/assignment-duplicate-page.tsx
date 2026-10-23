import { AssignmentForm } from '@app/components/assignments/assignment-form';
import type {
    AssignmentData,
    BatchOption,
    StudentOption,
} from '@app/types/assignments';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    source: AssignmentData;
    batches: BatchOption[];
    batchStudents: Record<string, StudentOption[]>;
    portal: 'teacher' | 'training-manager';
};

export default function AssignmentDuplicatePage({
    source,
    batches,
    batchStudents,
    portal,
}: Props) {
    const base =
        portal === 'teacher'
            ? '/teacher/assignments'
            : '/training-manager/assignments';

    const template: AssignmentData = {
        ...source,
        title: `${source.title} (copy)`,
        batch_id: 0,
        status: 'draft',
    };

    return (
        <>
            <PrivateSeoHead title={`Duplicate ${source.title}`} />
            <div className="mx-auto max-w-3xl p-2 sm:px-4">
                <h1 className="mb-2 text-2xl font-semibold">Duplicate to batch</h1>
                <p className="mb-6 text-sm text-muted-foreground">
                    Copy worksheet content from &ldquo;{source.title}&rdquo; to
                    another batch or student group.
                </p>
                <AssignmentForm
                    action={`${base}/${source.id}/duplicate`}
                    method="post"
                    batches={batches}
                    batchStudents={batchStudents}
                    assignment={template}
                    isDuplicate
                    submitLabel="Publish to batch"
                    draftLabel="Save as draft"
                    allowSaveDraft
                />
            </div>
        </>
    );
}
