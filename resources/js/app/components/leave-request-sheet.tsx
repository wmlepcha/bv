import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/responsive-sheet';
import { useMediaQuery } from '@/hooks/use-media-query';
import { cn } from '@/lib/utils';
import { store } from '@/routes/student/leave-requests';
import { DateRow, FormGroup, TextAreaRow } from '@app/components/native/form';

function dateKey(offsetDays: number): string {
    const date = new Date();
    date.setDate(date.getDate() + offsetDays);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

export default function LeaveRequestSheet({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const isDesktop = useMediaQuery('(min-width: 768px)');
    const form = useForm({
        from_date: dateKey(0),
        to_date: dateKey(0),
        reason: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className={cn('gap-0 p-0', isDesktop && 'sm:max-w-md')}
            >
                <SheetHeader className="border-b border-border/60 p-5 pr-12">
                    <SheetTitle>Request leave</SheetTitle>
                    <SheetDescription>
                        Your trainer reviews it. Once approved, the days are
                        marked as leave on your calendar automatically.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={submit} className="flex flex-col gap-4 p-5">
                    <FormGroup>
                        <DateRow
                            label="From"
                            min={dateKey(-7)}
                            value={form.data.from_date}
                            onChange={(event) => {
                                form.setData('from_date', event.target.value);

                                if (event.target.value > form.data.to_date) {
                                    form.setData('to_date', event.target.value);
                                }
                            }}
                            required
                            error={form.errors.from_date}
                        />
                        <DateRow
                            label="To"
                            min={form.data.from_date}
                            value={form.data.to_date}
                            onChange={(event) =>
                                form.setData('to_date', event.target.value)
                            }
                            required
                            error={form.errors.to_date}
                        />
                    </FormGroup>

                    <FormGroup>
                        <TextAreaRow
                            label="Reason"
                            maxLength={500}
                            placeholder="e.g. Family function, medical appointment"
                            value={form.data.reason}
                            onChange={(event) =>
                                form.setData('reason', event.target.value)
                            }
                            required
                            error={form.errors.reason}
                        />
                    </FormGroup>

                    <Button
                        type="submit"
                        className="h-12 rounded-full text-base"
                        disabled={form.processing}
                    >
                        Send request
                    </Button>
                </form>
            </SheetContent>
        </Sheet>
    );
}
