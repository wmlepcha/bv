import { ImageIcon, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import InputError from '@shared/components/input-error';

const MAX_BYTES = 2 * 1024 * 1024;

type Props = {
    error?: string;
    existingUrl?: string | null;
    /** When true, posts remove_avatar=1 if the existing photo was cleared. */
    allowRemove?: boolean;
    description?: string;
};

export function TrainerPhotoField({
    error,
    existingUrl = null,
    allowRemove = false,
    description = 'JPG or PNG, up to 2MB. Shown on the trainers list.',
}: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [fileName, setFileName] = useState<string | null>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [removed, setRemoved] = useState(false);
    const [clientError, setClientError] = useState<string | undefined>();

    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    const displayUrl = previewUrl ?? (removed ? null : existingUrl);
    const shownError = clientError ?? error;

    function clearPendingFile() {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        setPreviewUrl(null);
        setFileName(null);

        if (inputRef.current) {
            inputRef.current.value = '';
        }
    }

    function handleFileChange(next: File | null) {
        if (!next) {
            return;
        }

        if (!['image/jpeg', 'image/png'].includes(next.type)) {
            setClientError('Use a JPG or PNG image.');
            clearPendingFile();

            return;
        }

        if (next.size > MAX_BYTES) {
            setClientError('Photo must be 2MB or smaller.');
            clearPendingFile();

            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        setClientError(undefined);
        setRemoved(false);
        setFileName(next.name);
        setPreviewUrl(URL.createObjectURL(next));
    }

    return (
        <div className="grid gap-2">
            <Label htmlFor="avatar">Profile photo</Label>
            <p className="text-xs text-muted-foreground">{description}</p>

            {displayUrl ? (
                <div
                    className={cn(
                        'overflow-hidden rounded-xl border bg-foreground/[0.03]',
                        shownError
                            ? 'border-destructive/60'
                            : 'border-border/80',
                    )}
                >
                    <div className="relative aspect-[4/5] max-h-56 w-full bg-muted/30 sm:max-h-48">
                        <img
                            src={displayUrl}
                            alt="Profile photo"
                            className="size-full object-cover"
                        />
                    </div>
                    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-border/60 px-3 py-2.5">
                        <p className="min-w-0 truncate text-xs text-muted-foreground">
                            {fileName ?? 'Current photo'}
                        </p>
                        <div className="flex shrink-0 items-center gap-2">
                            <label
                                htmlFor="avatar"
                                className="cursor-pointer text-xs font-medium text-primary hover:underline"
                            >
                                Replace
                            </label>
                            {(fileName ||
                                (allowRemove && existingUrl && !removed)) && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setClientError(undefined);
                                        clearPendingFile();

                                        if (allowRemove && !fileName) {
                                            setRemoved(true);
                                        }
                                    }}
                                    className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-foreground"
                                >
                                    <X className="size-3.5" />
                                    {fileName ? 'Clear' : 'Remove'}
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            ) : (
                <label
                    htmlFor="avatar"
                    className={cn(
                        'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed bg-foreground/[0.03] px-4 py-8 text-center transition-colors hover:border-primary/40 hover:bg-foreground/[0.05]',
                        shownError
                            ? 'border-destructive/60'
                            : 'border-foreground/15',
                    )}
                >
                    <ImageIcon className="size-8 text-muted-foreground/70" />
                    <span className="text-sm font-medium text-foreground/90">
                        Click to upload photo
                    </span>
                    <span className="text-xs text-muted-foreground">
                        JPG or PNG, up to 2MB
                    </span>
                </label>
            )}

            <input
                ref={inputRef}
                id="avatar"
                name="avatar"
                type="file"
                accept="image/jpeg,image/png"
                className="sr-only"
                onChange={(e) => {
                    handleFileChange(e.target.files?.[0] ?? null);
                }}
            />

            {allowRemove && removed && !fileName && (
                <input type="hidden" name="remove_avatar" value="1" />
            )}

            <InputError message={shownError} />
        </div>
    );
}
