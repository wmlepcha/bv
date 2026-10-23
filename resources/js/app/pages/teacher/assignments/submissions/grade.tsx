import GradeSubmissionPage from '@app/components/assignments/grade-submission-page';
import type {
    AssignmentData,
    AssignmentSubmissionData,
} from '@app/types/assignments';

type Props = {
    assignment: AssignmentData;
    submission: AssignmentSubmissionData;
};

export default function TeacherGradeSubmission(props: Props) {
    return <GradeSubmissionPage {...props} portal="teacher" />;
}

TeacherGradeSubmission.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/teacher/assignments' },
        {
            title: props.assignment.title,
            href: `/teacher/assignments/${props.assignment.id}`,
        },
        { title: 'Grade', href: '#' },
    ],
});
