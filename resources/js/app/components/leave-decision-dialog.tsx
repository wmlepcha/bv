import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/responsive-dialog';
import { update } from '@/routes/teacher/leave-requests';

export type LeaveRow = {
    id: number;
    student: string;
    batch: string;
    from_date: string;
    to_date: string;
    reason: string;
    status: 'pending' | 'approved' | 'rejected';
    decision_note: string | null;
};

export default function LeaveDecisionDialog({
    leave,
    decision,
    onClose,
}: {
    leave: LeaveRow | null;
    decision: 'approve' | 'reject';
    onClose: () => void;
}) {
    const [note, setNote] = useState('');
    const approve = decision === 'approve';

    return (
        <Dialog
            open={leave !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent className="max-w-[calc(100%-2rem)] rounded-3xl sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {approve ? 'Approve' : 'Reject'} leave for{' '}
                        {leave?.student}?
                    </DialogTitle>
                    <DialogDescription>
                        {approve
                            ? 'The days are marked as leave on the student’s calendar and in the roll, and they are told.'
                            : 'The student is told their request was not approved.'}
                    </DialogDescription>
                </DialogHeader>
                <textarea
                    rows={3}
                    maxLength={255}
                    value={note}
                    onChange={(event) => setNote(event.target.value)}
                    placeholder="Add a note for the student (optional)"
                    className="w-full rounded-xl border border-input bg-background p-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <DialogFooter className="flex-row gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        className="h-11 flex-1 rounded-full"
                        onClick={onClose}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant={approve ? 'default' : 'destructive'}
                        className="h-11 flex-1 rounded-full"
                        onClick={() => {
                            if (!leave) {
                                return;
                            }

                            router.put(
                                update.url(leave.id),
                                { decision, note: note || null },
                                {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        setNote('');
                                        onClose();
                                    },
                                },
                            );
                        }}
                    >
                        {approve ? 'Approve' : 'Reject'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
