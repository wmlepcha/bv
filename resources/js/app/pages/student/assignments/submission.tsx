import SubmissionReview from '@app/components/assignments/submission-review';
import type { AssignmentData, AssignmentSubmissionData } from '@app/types/assignments';

type Props = {
    assignment: AssignmentData;
    submission: AssignmentSubmissionData;
};

export default function StudentAssignmentSubmission(props: Props) {
    return <SubmissionReview {...props} portal="student" />;
}

StudentAssignmentSubmission.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/student/assignments' },
        {
            title: props.assignment.title,
            href: `/student/assignments/${props.assignment.id}`,
        },
        { title: 'Results', href: `/student/assignments/${props.assignment.id}/submission` },
    ],
});
