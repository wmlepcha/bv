import { index as studyBankIndex } from '@/routes/student/study-bank';
import { StudyBankContents } from '@app/components/study-bank-contents';
import type {
    StudyBankFile,
    StudyBankFolder,
} from '@app/components/study-bank-contents';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    enrolled: boolean;
    folders: StudyBankFolder[];
    files: StudyBankFile[];
};

export default function StudentStudyBankIndex({
    enrolled,
    folders,
    files,
}: Props) {
    const isEmpty = folders.length === 0 && files.length === 0;

    return (
        <>
            <PrivateSeoHead title="Study Bank" />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                        Study Bank
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Materials shared with your batch.
                    </p>
                </div>

                {isEmpty ? (
                    <div className="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        {enrolled
                            ? 'No materials have been shared with your batch yet.'
                            : 'You are not enrolled in a batch yet. Study materials appear here once your batch is assigned.'}
                    </div>
                ) : (
                    <StudyBankContents folders={folders} files={files} />
                )}
            </div>
        </>
    );
}

StudentStudyBankIndex.layout = {
    breadcrumbs: [
        {
            title: 'Study Bank',
            href: studyBankIndex(),
        },
    ],
};
