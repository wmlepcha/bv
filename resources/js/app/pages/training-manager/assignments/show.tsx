import AssignmentShow from '@app/components/assignments/assignment-show-page';
import type {
    AssignmentData,
    StudentWorkEntry,
    StudentWorkSummary,
} from '@app/types/assignments';

type Props = {
    assignment: AssignmentData;
    canEdit: boolean;
    canGrade?: boolean;
    roster: StudentWorkEntry[];
    summary: StudentWorkSummary;
};

export default function TrainingManagerAssignmentShow({
    assignment,
    canEdit,
    canGrade = true,
    roster,
    summary,
}: Props) {
    return (
        <AssignmentShow
            assignment={assignment}
            canEdit={canEdit}
            portal="training-manager"
            canDuplicate
            canGrade={canGrade}
            roster={roster}
            summary={summary}
        />
    );
}

TrainingManagerAssignmentShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/training-manager/assignments' },
        {
            title: props.assignment.title,
            href: `/training-manager/assignments/${props.assignment.id}`,
        },
    ],
});
