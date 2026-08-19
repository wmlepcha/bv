import { Form, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { PrivateSeoHead } from '@shared/components/seo-head';
import InputError from '@shared/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type CandidateOption = { id: number; label: string };

type Enrollment = {
    id: number;
    student_id: number;
    name: string;
    email: string;
    student_number: string | null;
    enrolled_at: string | null;
};

type ImportResult = {
    enrolled: Array<{ row: number; candidate_id: string; name: string }>;
    skipped: Array<{ row: number; candidate_id: string; reason: string }>;
    failed: Array<{
        row: number;
        candidate_id: string | null;
        errors: string;
    }>;
};

type Props = {
    batch: {
        id: number;
        name: string;
        center_name: string | null;
        language_label: string;
        level: string;
        teacher_name: string | null;
        is_active: boolean;
    };
    enrollments: Enrollment[];
    candidateOptions: CandidateOption[];
    importResult: ImportResult | null;
};

function formatEnrolledAt(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleString();
}

export default function BatchStudents({
    batch,
    enrollments,
    candidateOptions,
    importResult,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };
    const [enrollOpen, setEnrollOpen] = useState(false);
    const [importOpen, setImportOpen] = useState(false);
    const [removeTarget, setRemoveTarget] = useState<Enrollment | null>(null);
    const [removing, setRemoving] = useState(false);
    const [search, setSearch] = useState('');
    const [options, setOptions] = useState(candidateOptions);
    const [selected, setSelected] = useState<number[]>([]);
    const [searching, setSearching] = useState(false);

    useEffect(() => {
        setOptions(candidateOptions);
    }, [candidateOptions]);

    useEffect(() => {
        if (!enrollOpen) {
            return;
        }

        const query = search.trim();

        if (query === '') {
            setOptions(candidateOptions);
            return;
        }

        const handle = window.setTimeout(async () => {
            setSearching(true);

            try {
                const response = await fetch(
                    `/training-manager/batches/unenrolled-candidates?q=${encodeURIComponent(query)}`,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    },
                );

                if (!response.ok) {
                    return;
                }

                const data = (await response.json()) as {
                    candidates: CandidateOption[];
                };
                setOptions(data.candidates);
            } finally {
                setSearching(false);
            }
        }, 250);

        return () => window.clearTimeout(handle);
    }, [search, enrollOpen, candidateOptions]);

    const filteredLocal = useMemo(() => {
        const query = search.trim().toLowerCase();

        if (query === '') {
            return options;
        }

        return options.filter((option) =>
            option.label.toLowerCase().includes(query),
        );
    }, [options, search]);

    function toggleStudent(id: number) {
        setSelected((current) =>
            current.includes(id)
                ? current.filter((value) => value !== id)
                : [...current, id],
        );
    }

    function confirmRemove() {
        if (!removeTarget || removing) {
            return;
        }

        setRemoving(true);
        router.delete(
            `/training-manager/batches/${batch.id}/enrollments/${removeTarget.id}`,
            {
                onFinish: () => {
                    setRemoving(false);
                    setRemoveTarget(null);
                },
            },
        );
    }

    return (
        <>
            <PrivateSeoHead title={`${batch.name} · Students`} />
            <div className="flex flex-col gap-6 p-2 sm:px-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {batch.name}
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            {batch.language_label} {batch.level}
                            {batch.center_name ? ` · ${batch.center_name}` : ''}
                            {batch.teacher_name
                                ? ` · Trainer: ${batch.teacher_name}`
                                : ''}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Enroll candidates into this batch. Each candidate
                            can only be in one batch at a time.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/training-manager/batches">
                                Back to batches
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={`/training-manager/batches/${batch.id}/edit`}
                            >
                                Edit batch
                            </Link>
                        </Button>
                    </div>
                </div>

                {flash?.success && (
                    <p className="text-sm font-medium text-green-600">
                        {flash.success}
                    </p>
                )}
                {flash?.error && (
                    <p className="text-sm font-medium text-destructive">
                        {flash.error}
                    </p>
                )}

                {importResult && (
                    <ImportSummary result={importResult} />
                )}

                <div className="flex flex-wrap gap-2">
                    <Button type="button" onClick={() => setEnrollOpen(true)}>
                        Enroll candidates
                    </Button>
                    <Button variant="outline" asChild>
                        <a href="/training-manager/batches/enrollment-template">
                            Download template
                        </a>
                    </Button>
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => setImportOpen(true)}
                    >
                        Import enrollments
                    </Button>
                </div>

                {enrollments.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-border p-10 text-center text-sm text-muted-foreground">
                        No students in this batch yet. Enroll candidates or
                        import an XLSX file.
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-border">
                        <table className="w-full min-w-[640px] text-left text-sm">
                            <thead className="border-b border-border bg-muted/40">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Candidate ID
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Email
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Enrolled
                                    </th>
                                    <th className="px-4 py-3 font-medium text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {enrollments.map((enrollment) => (
                                    <tr
                                        key={enrollment.id}
                                        className="border-b border-border last:border-0"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {enrollment.name}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {enrollment.student_number ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {enrollment.email}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {formatEnrolledAt(
                                                enrollment.enrolled_at,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                onClick={() =>
                                                    setRemoveTarget(enrollment)
                                                }
                                            >
                                                Remove
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <Dialog
                open={enrollOpen}
                onOpenChange={(open) => {
                    setEnrollOpen(open);
                    if (!open) {
                        setSearch('');
                        setSelected([]);
                        setOptions(candidateOptions);
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Enroll candidates</DialogTitle>
                        <DialogDescription>
                            Select candidates who are not yet in any batch.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        action={`/training-manager/batches/${batch.id}/enrollments`}
                        method="post"
                        className="space-y-4"
                        options={{ preserveScroll: true }}
                        onSuccess={() => {
                            setEnrollOpen(false);
                            setSelected([]);
                            setSearch('');
                        }}
                    >
                        {({ processing, errors }) => (
                            <>
                                {selected.map((id) => (
                                    <input
                                        key={id}
                                        type="hidden"
                                        name="student_ids[]"
                                        value={id}
                                    />
                                ))}
                                <div className="space-y-2">
                                    <Label htmlFor="candidate-search">
                                        Search
                                    </Label>
                                    <Input
                                        id="candidate-search"
                                        value={search}
                                        onChange={(event) =>
                                            setSearch(event.target.value)
                                        }
                                        placeholder="Name, email, or candidate ID"
                                    />
                                </div>
                                <fieldset className="max-h-64 space-y-1 overflow-y-auto rounded-lg border border-border p-3">
                                    <legend className="sr-only">
                                        Candidates
                                    </legend>
                                    {searching && (
                                        <p className="px-2 py-1 text-xs text-muted-foreground">
                                            Searching…
                                        </p>
                                    )}
                                    {filteredLocal.length === 0 ? (
                                        <p className="px-2 py-2 text-sm text-muted-foreground">
                                            No unenrolled candidates found.
                                        </p>
                                    ) : (
                                        filteredLocal.map((option) => (
                                            <Label
                                                key={option.id}
                                                className="flex cursor-pointer items-start gap-3 rounded-md px-2 py-2 hover:bg-muted/60"
                                            >
                                                <input
                                                    type="checkbox"
                                                    className="mt-1 size-4 rounded border-input"
                                                    checked={selected.includes(
                                                        option.id,
                                                    )}
                                                    onChange={() =>
                                                        toggleStudent(option.id)
                                                    }
                                                />
                                                <span className="text-sm">
                                                    {option.label}
                                                </span>
                                            </Label>
                                        ))
                                    )}
                                </fieldset>
                                <InputError message={errors.student_ids} />
                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            disabled={processing}
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        disabled={
                                            processing || selected.length === 0
                                        }
                                    >
                                        {processing && <Spinner />}
                                        Enroll {selected.length || ''}
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            <Dialog open={importOpen} onOpenChange={setImportOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Import enrollments</DialogTitle>
                        <DialogDescription>
                            Upload an XLSX with a candidate_id column. Download
                            the template for unenrolled candidates, delete rows
                            you do not want, then upload.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        action={`/training-manager/batches/${batch.id}/enrollments/import`}
                        method="post"
                        encType="multipart/form-data"
                        className="space-y-4"
                        options={{ preserveScroll: true }}
                        onSuccess={() => setImportOpen(false)}
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="space-y-2">
                                    <Label htmlFor="spreadsheet">
                                        Enrollments XLSX
                                    </Label>
                                    <Input
                                        id="spreadsheet"
                                        name="spreadsheet"
                                        type="file"
                                        accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                                        required
                                    />
                                    <InputError message={errors.spreadsheet} />
                                </div>
                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            disabled={processing}
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Import
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={removeTarget !== null}
                onOpenChange={(open) => {
                    if (!open && !removing) {
                        setRemoveTarget(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove from batch?</DialogTitle>
                        <DialogDescription>
                            “{removeTarget?.name}” will be removed from this
                            batch. They can be enrolled in another batch
                            afterward.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" disabled={removing}>
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            disabled={removing}
                            onClick={confirmRemove}
                        >
                            {removing && <Spinner />}
                            Remove
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function ImportSummary({ result }: { result: ImportResult }) {
    return (
        <div className="space-y-3 rounded-xl border border-border bg-muted/20 p-4 text-sm">
            <p className="font-medium">
                Import summary — enrolled {result.enrolled.length}, skipped{' '}
                {result.skipped.length}, failed {result.failed.length}.
            </p>
            {result.failed.length > 0 && (
                <ul className="list-disc space-y-1 pl-5 text-destructive">
                    {result.failed.map((row) => (
                        <li key={`fail-${row.row}`}>
                            Row {row.row}
                            {row.candidate_id ? ` (${row.candidate_id})` : ''}:{' '}
                            {row.errors}
                        </li>
                    ))}
                </ul>
            )}
            {result.skipped.length > 0 && (
                <ul className="list-disc space-y-1 pl-5 text-muted-foreground">
                    {result.skipped.map((row) => (
                        <li key={`skip-${row.row}`}>
                            Row {row.row} ({row.candidate_id}): {row.reason}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

BatchStudents.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Batches',
            href: '/training-manager/batches',
        },
        {
            title: props.batch.name,
            href: `/training-manager/batches/${props.batch.id}/students`,
        },
        {
            title: 'Students',
            href: `/training-manager/batches/${props.batch.id}/students`,
        },
    ],
});
