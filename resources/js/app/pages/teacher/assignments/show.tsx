import AssignmentShow from '@app/components/assignments/assignment-show-page';
import type {
    AssignmentData,
    StudentWorkEntry,
    StudentWorkSummary,
} from '@app/types/assignments';

type Props = {
    assignment: AssignmentData;
    canEdit: boolean;
    canDuplicate: boolean;
    canGrade: boolean;
    roster: StudentWorkEntry[];
    summary: StudentWorkSummary;
};

export default function TeacherAssignmentShow(props: Props) {
    return <AssignmentShow {...props} portal="teacher" />;
}

TeacherAssignmentShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/teacher/assignments' },
        {
            title: props.assignment.title,
            href: `/teacher/assignments/${props.assignment.id}`,
        },
    ],
});
