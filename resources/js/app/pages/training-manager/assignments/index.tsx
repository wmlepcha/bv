import type { AssignmentCreatorCardData } from '@app/components/assignments/assignment-creator-card';
import AssignmentsWorkspace from '@app/components/assignments/assignments-workspace';
import type {AssignmentCurrentCreator, AssignmentListTab} from '@app/components/assignments/assignments-workspace';
import type { AssignmentData, AssignmentFolder } from '@app/types/assignments';

type Props = {
    creators?: AssignmentCreatorCardData[];
    assignments: AssignmentData[];
    folders: AssignmentFolder[];
    currentFolder: { id: number; name: string } | null;
    currentCreator?: AssignmentCurrentCreator | null;
    status?: AssignmentListTab;
    tabCounts?: {
        published: number;
        draft: number;
    };
};

export default function TrainingManagerAssignmentsIndex({
    creators = [],
    assignments,
    folders,
    currentFolder,
    currentCreator = null,
    status = 'published',
    tabCounts,
}: Props) {
    return (
        <AssignmentsWorkspace
            creators={creators}
            assignments={assignments}
            folders={folders}
            currentFolder={currentFolder}
            currentCreator={currentCreator}
            initialTab={status}
            tabCounts={tabCounts}
            basePath="/training-manager/assignments"
            createPath="/training-manager/assignments/create"
        />
    );
}

TrainingManagerAssignmentsIndex.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Assignments', href: '/training-manager/assignments' },
        ...(props.currentCreator
            ? [
                  {
                      title: props.currentCreator.name,
                      href: `/training-manager/assignments?creator=${props.currentCreator.id}`,
                  },
              ]
            : []),
        ...(props.currentFolder && props.currentCreator
            ? [
                  {
                      title: props.currentFolder.name,
                      href: `/training-manager/assignments?creator=${props.currentCreator.id}&folder=${props.currentFolder.id}`,
                  },
              ]
            : []),
    ],
});
