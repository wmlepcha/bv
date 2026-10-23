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

export default function TeacherDuplicateAssignment({
    source,
    batches,
    batchStudents,
}: Props) {
    return (
        <AssignmentDuplicatePage
            source={source}
            batches={batches}
            batchStudents={batchStudents}
            portal="teacher"
        />
    );
}

TeacherDuplicateAssignment.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/teacher/assignments' },
        {
            title: props.source.title,
            href: `/teacher/assignments/${props.source.id}`,
        },
        {
            title: 'Duplicate',
            href: `/teacher/assignments/${props.source.id}/duplicate`,
        },
    ],
});
