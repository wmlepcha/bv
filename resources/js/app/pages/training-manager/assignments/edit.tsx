import AssignmentEdit from '@app/components/assignments/assignment-edit-page';
import type {
    AssignmentData,
    BatchOption,
    StudentOption,
} from '@app/types/assignments';

type Props = {
    assignment: AssignmentData;
    batches: BatchOption[];
    batchStudents: Record<string, StudentOption[]>;
};

export default function TrainingManagerAssignmentEdit(props: Props) {
    return <AssignmentEdit {...props} portal="training-manager" />;
}

TrainingManagerAssignmentEdit.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/training-manager/assignments' },
        {
            title: props.assignment.title,
            href: `/training-manager/assignments/${props.assignment.id}`,
        },
        {
            title: 'Edit',
            href: `/training-manager/assignments/${props.assignment.id}/edit`,
        },
    ],
});
