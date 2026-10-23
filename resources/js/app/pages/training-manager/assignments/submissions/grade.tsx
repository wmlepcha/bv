import GradeSubmissionPage from '@app/components/assignments/grade-submission-page';
import type {
    AssignmentData,
    AssignmentSubmissionData,
} from '@app/types/assignments';

type Props = {
    assignment: AssignmentData;
    submission: AssignmentSubmissionData;
};

export default function TrainingManagerGradeSubmission(props: Props) {
    return <GradeSubmissionPage {...props} portal="training-manager" />;
}

TrainingManagerGradeSubmission.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/training-manager/assignments' },
        {
            title: props.assignment.title,
            href: `/training-manager/assignments/${props.assignment.id}`,
        },
        { title: 'Grade', href: '#' },
    ],
});
