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

export default function TrainingManagerSubmissionsIndex({
    canGrade = true,
    ...props
}: Props) {
    return (
        <StudentWorkPage
            {...props}
            portal="training-manager"
            canGrade={canGrade}
        />
    );
}

TrainingManagerSubmissionsIndex.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/training-manager/assignments' },
        {
            title: props.assignment.title,
            href: `/training-manager/assignments/${props.assignment.id}`,
        },
        {
            title: 'Student work',
            href: `/training-manager/assignments/${props.assignment.id}/submissions`,
        },
    ],
});
