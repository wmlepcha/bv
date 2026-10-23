import { Form, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    GraduationCap,
    Languages,
    UserRound,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import InputError from '@shared/components/input-error';
import { PrivateSeoHead } from '@shared/components/seo-head';

type FormOptions = {
    centers: Array<{ id: number; name: string }>;
    languages: Array<{ value: string; label: string }>;
    levels: Array<{ value: string; label: string }>;
    trainers: Array<{ id: number; name: string }>;
};

type Props = {
    formOptions: FormOptions;
};

const fieldClass =
    'flex h-11 w-full rounded-xl border border-border/80 bg-background px-3.5 text-sm shadow-none outline-none transition-[color,box-shadow,border-color] placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-primary/20';

export default function CreateBatch({ formOptions }: Props) {
    return (
        <>
            <PrivateSeoHead title="Add batch" />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-2 sm:px-4">
                <div className="flex items-start gap-3">
                    <span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-sm">
                        <GraduationCap className="size-5" />
                    </span>
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Add batch
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create a batch and assign a trainer as faculty.
                        </p>
                    </div>
                </div>

                <BatchForm
                    action="/training-manager/batches"
                    method="post"
                    formOptions={formOptions}
                    submitLabel="Create batch"
                />
            </div>
        </>
    );
}

function BatchForm({
    action,
    method,
    formOptions,
    submitLabel,
    defaults,
}: {
    action: string;
    method: 'post' | 'put';
    formOptions: FormOptions;
    submitLabel: string;
    defaults?: {
        name?: string;
        center_id?: number;
        language?: string;
        level?: string;
        teacher_id?: number;
        is_active?: boolean;
        starts_on?: string;
        ends_on?: string;
    };
}) {
    return (
        <Form
            action={action}
            method={method}
            className="overflow-hidden rounded-[1.75rem] border border-border/70 bg-card shadow-[0_18px_40px_-28px_rgba(3,24,53,0.35)]"
        >
            {({ processing, errors }) => (
                <>
                    <div className="border-b border-border/60 bg-gradient-to-br from-primary/12 via-brand-cool/10 to-transparent px-5 py-4 sm:px-6">
                        <p className="text-sm font-medium text-foreground">
                            Batch details
                        </p>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            Name, location, course level, and faculty.
                        </p>
                    </div>

                    <div className="flex flex-col gap-5 px-5 py-5 sm:px-6 sm:py-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name" className="text-sm font-medium">
                                Name
                            </Label>
                            <Input
                                id="name"
                                name="name"
                                required
                                defaultValue={defaults?.name}
                                placeholder="German B1 · Morning · July 2026"
                                autoFocus
                                className={cn(fieldClass, 'h-11')}
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="center_id"
                                className="inline-flex items-center gap-1.5 text-sm font-medium"
                            >
                                <Building2 className="size-3.5 text-muted-foreground" />
                                Center
                            </Label>
                            <select
                                id="center_id"
                                name="center_id"
                                required
                                defaultValue={defaults?.center_id ?? ''}
                                className={fieldClass}
                            >
                                <option value="" disabled>
                                    Select center
                                </option>
                                {formOptions.centers.map((center) => (
                                    <option key={center.id} value={center.id}>
                                        {center.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.center_id} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="language"
                                    className="inline-flex items-center gap-1.5 text-sm font-medium"
                                >
                                    <Languages className="size-3.5 text-muted-foreground" />
                                    Language
                                </Label>
                                <select
                                    id="language"
                                    name="language"
                                    required
                                    defaultValue={defaults?.language ?? ''}
                                    className={fieldClass}
                                >
                                    <option value="" disabled>
                                        Select language
                                    </option>
                                    {formOptions.languages.map((language) => (
                                        <option
                                            key={language.value}
                                            value={language.value}
                                        >
                                            {language.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.language} />
                            </div>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="level"
                                    className="text-sm font-medium"
                                >
                                    Level
                                </Label>
                                <select
                                    id="level"
                                    name="level"
                                    required
                                    defaultValue={defaults?.level ?? ''}
                                    className={fieldClass}
                                >
                                    <option value="" disabled>
                                        Select level
                                    </option>
                                    {formOptions.levels.map((level) => (
                                        <option
                                            key={level.value}
                                            value={level.value}
                                        >
                                            {level.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.level} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="teacher_id"
                                className="inline-flex items-center gap-1.5 text-sm font-medium"
                            >
                                <UserRound className="size-3.5 text-muted-foreground" />
                                Faculty / Trainer
                            </Label>
                            <select
                                id="teacher_id"
                                name="teacher_id"
                                required
                                defaultValue={defaults?.teacher_id ?? ''}
                                className={fieldClass}
                            >
                                <option value="" disabled>
                                    Select trainer
                                </option>
                                {formOptions.trainers.map((trainer) => (
                                    <option key={trainer.id} value={trainer.id}>
                                        {trainer.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.teacher_id} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="starts_on">Start date</Label>
                                <Input
                                    id="starts_on"
                                    name="starts_on"
                                    type="date"
                                    defaultValue={defaults?.starts_on}
                                    className={cn(fieldClass, 'h-11')}
                                />
                                <InputError message={errors.starts_on} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="ends_on">End date</Label>
                                <Input
                                    id="ends_on"
                                    name="ends_on"
                                    type="date"
                                    defaultValue={defaults?.ends_on}
                                    className={cn(fieldClass, 'h-11')}
                                />
                                <InputError message={errors.ends_on} />
                            </div>
                        </div>

                        <div className="rounded-2xl border border-border/70 bg-muted/30 px-4 py-3">
                            <input type="hidden" name="is_active" value="0" />
                            <label
                                htmlFor="is_active"
                                className="flex cursor-pointer items-center justify-between gap-3"
                            >
                                <span>
                                    <span className="block text-sm font-medium">
                                        Active batch
                                    </span>
                                    <span className="mt-0.5 block text-xs text-muted-foreground">
                                        Inactive batches stay hidden from new
                                        enrollments.
                                    </span>
                                </span>
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    defaultChecked={defaults?.is_active ?? true}
                                    className="size-5 rounded-md border-input accent-primary"
                                />
                            </label>
                            <InputError message={errors.is_active} />
                        </div>
                    </div>

                    <div className="flex flex-col-reverse gap-3 border-t border-border/60 bg-muted/20 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <Button
                            variant="ghost"
                            asChild
                            className="rounded-full text-muted-foreground"
                        >
                            <Link href="/training-manager/batches">
                                <ArrowLeft className="size-4" />
                                Back to batches
                            </Link>
                        </Button>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                asChild
                                className="rounded-full"
                            >
                                <Link href="/training-manager/batches">
                                    Cancel
                                </Link>
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="rounded-full px-6"
                            >
                                {processing && <Spinner />}
                                {submitLabel}
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}

CreateBatch.layout = {
    breadcrumbs: [
        {
            title: 'Batches',
            href: '/training-manager/batches',
        },
        {
            title: 'Add',
            href: '/training-manager/batches/create',
        },
    ],
};

export { BatchForm };
