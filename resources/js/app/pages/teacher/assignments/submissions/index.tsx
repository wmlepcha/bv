import { StudentWorkPage } from '@app/components/assignments/student-work-page';
import type {
    AssignmentData,
    StudentWorkEntry,
    StudentWorkSummary,
} from '@app/types/assignments';

type Props = {
    assignment: AssignmentData;
    roster: StudentWorkEntry[];
    summary: StudentWorkSummary;
    canGrade?: boolean;
};

export default function TeacherSubmissionsIndex({
    canGrade = false,
    ...props
}: Props) {
    return (
        <StudentWorkPage
            {...props}
            portal="teacher"
            canGrade={canGrade}
        />
    );
}

TeacherSubmissionsIndex.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/teacher/assignments' },
        {
            title: props.assignment.title,
            href: `/teacher/assignments/${props.assignment.id}`,
        },
        {
            title: 'Student work',
            href: `/teacher/assignments/${props.assignment.id}/submissions`,
        },
    ],
});
