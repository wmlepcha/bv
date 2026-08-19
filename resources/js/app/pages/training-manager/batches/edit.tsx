import { GraduationCap } from 'lucide-react';
import { PrivateSeoHead } from '@shared/components/seo-head';
import { BatchForm } from '@app/pages/training-manager/batches/create';

type FormOptions = {
    centers: Array<{ id: number; name: string }>;
    languages: Array<{ value: string; label: string }>;
    levels: Array<{ value: string; label: string }>;
    trainers: Array<{ id: number; name: string }>;
};

type Props = {
    batch: {
        id: number;
        name: string;
        center_id: number;
        language: string;
        level: string;
        teacher_id: number;
        is_active: boolean;
    };
    formOptions: FormOptions;
};

export default function EditBatch({ batch, formOptions }: Props) {
    return (
        <>
            <PrivateSeoHead title={`Edit ${batch.name}`} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-2 sm:px-4">
                <div className="flex items-start gap-3">
                    <span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-[#2A80CC] text-white shadow-sm">
                        <GraduationCap className="size-5" />
                    </span>
                    <div className="min-w-0">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Edit batch
                        </h1>
                        <p className="mt-1 truncate text-sm text-muted-foreground">
                            Update “{batch.name}” details and faculty
                            assignment.
                        </p>
                    </div>
                </div>

                <BatchForm
                    action={`/training-manager/batches/${batch.id}`}
                    method="put"
                    formOptions={formOptions}
                    submitLabel="Save changes"
                    defaults={batch}
                />
            </div>
        </>
    );
}

EditBatch.layout = {
    breadcrumbs: [
        {
            title: 'Batches',
            href: '/training-manager/batches',
        },
        {
            title: 'Edit',
            href: '#',
        },
    ],
};
