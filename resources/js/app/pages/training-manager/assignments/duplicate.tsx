import AssignmentDuplicatePage from '@app/components/assignments/assignment-duplicate-page';
import type {
    AssignmentData,
    BatchOption,
    StudentOption,
} from '@app/types/assignments';

type Props = {
    source: AssignmentData;
    batches: BatchOption[];
    batchStudents: Record<string, StudentOption[]>;
};

export default function TrainingManagerDuplicateAssignment({
    source,
    batches,
    batchStudents,
}: Props) {
    return (
        <AssignmentDuplicatePage
            source={source}
            batches={batches}
            batchStudents={batchStudents}
            portal="training-manager"
        />
    );
}

TrainingManagerDuplicateAssignment.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/training-manager/assignments' },
        {
            title: props.source.title,
            href: `/training-manager/assignments/${props.source.id}`,
        },
        {
            title: 'Duplicate',
            href: `/training-manager/assignments/${props.source.id}/duplicate`,
        },
    ],
});
