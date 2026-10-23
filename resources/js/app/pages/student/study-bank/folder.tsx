import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { index as studyBankIndex } from '@/routes/student/study-bank';
import { show as studyBankFolder } from '@/routes/student/study-bank/folders';
import { StudyBankContents } from '@app/components/study-bank-contents';
import type {
    StudyBankFile,
    StudyBankFolder,
} from '@app/components/study-bank-contents';
import { PrivateSeoHead } from '@shared/components/seo-head';

type Props = {
    folder: {
        id: number;
        name: string;
        parent_id: number | null;
    };
    breadcrumbs: Array<{ id: number; name: string }>;
    folders: StudyBankFolder[];
    files: StudyBankFile[];
};

export default function StudentStudyBankFolder({
    folder,
    breadcrumbs,
    folders,
    files,
}: Props) {
    const parentAccessible =
        folder.parent_id !== null &&
        breadcrumbs.some((crumb) => crumb.id === folder.parent_id);

    const backHref = parentAccessible
        ? studyBankFolder.url(folder.parent_id!)
        : studyBankIndex.url();

    const isEmpty = folders.length === 0 && files.length === 0;

    return (
        <>
            <PrivateSeoHead title={folder.name} />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight md:text-2xl md:font-semibold">
                            {folder.name}
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Materials shared with your batch.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={backHref}>
                            {parentAccessible
                                ? 'Up one level'
                                : 'Back to Study Bank'}
                        </Link>
                    </Button>
                </div>

                <nav className="flex flex-wrap items-center gap-2 text-sm">
                    <Link
                        href={studyBankIndex.url()}
                        className="text-muted-foreground hover:text-foreground"
                    >
                        Study Bank
                    </Link>
                    {breadcrumbs.map((crumb) => (
                        <span
                            key={crumb.id}
                            className="flex items-center gap-2"
                        >
                            <span className="text-muted-foreground">/</span>
                            <Link
                                href={studyBankFolder.url(crumb.id)}
                                className="hover:text-foreground"
                            >
                                {crumb.name}
                            </Link>
                        </span>
                    ))}
                </nav>

                {isEmpty ? (
                    <div className="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        This folder is empty.
                    </div>
                ) : (
                    <StudyBankContents folders={folders} files={files} />
                )}
            </div>
        </>
    );
}

StudentStudyBankFolder.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Study Bank',
            href: studyBankIndex(),
        },
        ...props.breadcrumbs.map((crumb) => ({
            title: crumb.name,
            href: studyBankFolder.url(crumb.id),
        })),
    ],
});
