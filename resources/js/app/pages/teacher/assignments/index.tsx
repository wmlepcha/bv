import AssignmentsIndex from '@app/components/assignments/assignments-index-page';
import type {AssignmentListTab} from '@app/components/assignments/assignments-index-page';
import type { AssignmentData } from '@app/types/assignments';

type Props = {
    assignments: AssignmentData[];
    status?: AssignmentListTab;
};

export default function TeacherAssignmentsIndex({
    assignments,
    status = 'published',
}: Props) {
    return (
        <AssignmentsIndex
            assignments={assignments}
            basePath="/teacher/assignments"
            createPath="/teacher/assignments/create"
            initialTab={status}
        />
    );
}

TeacherAssignmentsIndex.layout = {
    breadcrumbs: [
        { title: 'Assignments', href: '/teacher/assignments' },
    ],
};
