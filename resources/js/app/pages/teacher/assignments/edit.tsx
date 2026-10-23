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

export default function TeacherAssignmentEdit(props: Props) {
    return <AssignmentEdit {...props} portal="teacher" />;
}

TeacherAssignmentEdit.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/teacher/assignments' },
        {
            title: props.assignment.title,
            href: `/teacher/assignments/${props.assignment.id}`,
        },
        { title: 'Edit', href: `/teacher/assignments/${props.assignment.id}/edit` },
    ],
});
