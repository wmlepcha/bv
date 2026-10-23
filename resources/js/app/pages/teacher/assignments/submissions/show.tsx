import SubmissionReview from '@app/components/assignments/submission-review';
import type { AssignmentData, AssignmentSubmissionData } from '@app/types/assignments';

type Props = {
    assignment: AssignmentData;
    submission: AssignmentSubmissionData;
};

export default function TeacherSubmissionShow(props: Props) {
    return <SubmissionReview {...props} portal="teacher" />;
}

TeacherSubmissionShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/teacher/assignments' },
        {
            title: props.assignment.title,
            href: `/teacher/assignments/${props.assignment.id}/submissions`,
        },
    ],
});
