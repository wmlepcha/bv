import { Form } from '@inertiajs/react';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export type ShareBatchOption = {
    id: number;
    name: string;
};

type ShareTarget = {
    type: 'folder' | 'file';
    id: number;
    name: string;
    selected: number[];
};

type ShareToBatchesModalProps = {
    open: boolean;
    batches: ShareBatchOption[];
    target: ShareTarget | null;
    actionUrl: string | null;
    onClose: () => void;
    /** When true, empty state speaks to faculty with no assigned batches. */
    facultyOnly?: boolean;
};

export function ShareToBatchesModal({
    open,
    batches,
    target,
    actionUrl,
    onClose,
    facultyOnly = false,
}: ShareToBatchesModalProps) {
    if (!target || !actionUrl) {
        return null;
    }

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                if (!nextOpen) {
                    onClose();
                }
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Share with batches</DialogTitle>
                    <DialogDescription>
                        {facultyOnly
                            ? `Share “${target.name}” with batches assigned to you. Only your batches are listed — students in those batches get access, not individual picks.`
                            : `Share “${target.name}” with batches. Students in those batches will get access — you don’t pick individual students.`}
                    </DialogDescription>
                </DialogHeader>

                {batches.length === 0 ? (
                    <div className="space-y-2 rounded-lg border border-dashed border-border bg-muted/30 p-4">
                        <p className="text-sm font-medium text-foreground">
                            {facultyOnly
                                ? 'No batches assigned to you yet'
                                : 'No batches available'}
                        </p>
                        <p className="text-sm text-muted-foreground">
                            {facultyOnly
                                ? 'A training manager hasn’t assigned you to any batches yet. You can still browse, download, and prepare your PDFs and materials in the meantime — sharing will unlock once a batch is assigned to you.'
                                : 'Create or activate a batch first, then share materials with it.'}
                        </p>
                    </div>
                ) : (
                    <Form
                        action={actionUrl}
                        method="post"
                        className="space-y-4"
                        options={{ preserveScroll: true }}
                        onSuccess={onClose}
                    >
                        {({ processing, errors }) => (
                            <>
                                <fieldset className="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-border p-3">
                                    <legend className="sr-only">
                                        Your assigned batches
                                    </legend>
                                    {batches.map((batch) => {
                                        const checked =
                                            target.selected.includes(batch.id);

                                        return (
                                            <Label
                                                key={batch.id}
                                                className="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 hover:bg-muted/60"
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="batch_ids[]"
                                                    value={batch.id}
                                                    defaultChecked={checked}
                                                    className="size-4 rounded border-input"
                                                />
                                                <span className="text-sm font-medium">
                                                    {batch.name}
                                                </span>
                                            </Label>
                                        );
                                    })}
                                </fieldset>
                                {errors.batch_ids && (
                                    <p className="text-sm text-destructive">
                                        {errors.batch_ids}
                                    </p>
                                )}
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
                                        Save sharing
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                )}

                {batches.length === 0 && (
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Close
                            </Button>
                        </DialogClose>
                    </DialogFooter>
                )}
            </DialogContent>
        </Dialog>
    );
}
