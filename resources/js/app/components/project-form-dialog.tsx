import { useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/responsive-dialog';
import { store, update } from '@/routes/recruiter/projects';
import type { ProjectSummary } from '@app/types/recruiter';
import InputError from '@shared/components/input-error';

const FIELDS = [
    { key: 'name', label: 'Project name', placeholder: 'Nurses for Germany' },
    {
        key: 'country',
        label: 'Hiring for which country',
        placeholder: 'Germany',
    },
    { key: 'sector', label: 'Sector', placeholder: 'Nursing' },
    { key: 'timeline', label: 'Timeline', placeholder: 'Within 3 months' },
] as const;

export default function ProjectFormDialog({
    project,
    children,
}: {
    project?: ProjectSummary;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        name: project?.name ?? '',
        country: project?.country ?? '',
        sector: project?.sector ?? '',
        timeline: project?.timeline ?? '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);

                if (!project) {
                    form.reset();
                }
            },
        };

        if (project) {
            form.put(update.url(project.id), options);
        } else {
            form.post(store.url(), options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-sm:top-auto max-sm:bottom-0 max-sm:left-0 max-sm:max-w-full max-sm:translate-x-0 max-sm:translate-y-0 max-sm:rounded-t-3xl max-sm:rounded-b-none max-sm:pb-[calc(1.5rem+env(safe-area-inset-bottom))] sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {project ? 'Edit project' : 'Create project'}
                    </DialogTitle>
                    <DialogDescription>
                        Describe the candidates you are looking for. You can
                        shortlist students into this project afterwards.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-4">
                    {FIELDS.map((field) => (
                        <div key={field.key} className="grid gap-2">
                            <Label htmlFor={`project-${field.key}`}>
                                {field.label}
                            </Label>
                            <Input
                                id={`project-${field.key}`}
                                value={form.data[field.key]}
                                onChange={(event) =>
                                    form.setData(field.key, event.target.value)
                                }
                                placeholder={field.placeholder}
                                list={
                                    field.key === 'country'
                                        ? 'project-country-options'
                                        : undefined
                                }
                                maxLength={255}
                                required
                            />
                            <InputError message={form.errors[field.key]} />
                        </div>
                    ))}
                    <datalist id="project-country-options">
                        <option value="Germany" />
                        <option value="Austria" />
                    </datalist>
                    <Button type="submit" disabled={form.processing}>
                        {project ? 'Save changes' : 'Create project'}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
