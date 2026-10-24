import { useRef } from 'react';
import type { ClipboardEvent, DragEvent } from 'react';
import { toast } from 'sonner';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    GermanCharToolbar,
    insertAtCursor,
} from '@app/components/assignments/german-char-toolbar';

type Props = {
    value: string;
    onChange: (value: string) => void;
    multiline?: boolean;
    rows?: number;
    placeholder?: string;
    id?: string;
};

/** Test answers must be typed: block copy, cut, paste and drop, and say so. */
function blockClipboard(event: ClipboardEvent | DragEvent): void {
    event.preventDefault();
    toast.error('You are not allowed to do that.', { id: 'clipboard-blocked' });
}

export const clipboardGuards = {
    onCopy: blockClipboard,
    onCut: blockClipboard,
    onPaste: blockClipboard,
    onDrop: blockClipboard,
};

export function GermanTextInput({
    value,
    onChange,
    multiline = false,
    rows = 3,
    placeholder,
    id,
}: Props) {
    const ref = useRef<HTMLInputElement & HTMLTextAreaElement>(null);

    const insertChar = (char: string) => {
        const el = ref.current;

        if (!el) {
            return;
        }

        const { value: next, cursor } = insertAtCursor(
            value,
            char,
            el.selectionStart,
            el.selectionEnd,
        );
        onChange(next);
        requestAnimationFrame(() => {
            el.focus();
            el.setSelectionRange(cursor, cursor);
        });
    };

    return (
        <div className="grid gap-2">
            <GermanCharToolbar onInsert={insertChar} />
            {multiline ? (
                <Textarea
                    ref={ref}
                    id={id}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    rows={rows}
                    placeholder={placeholder}
                    {...clipboardGuards}
                />
            ) : (
                <Input
                    ref={ref}
                    id={id}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={placeholder}
                    {...clipboardGuards}
                />
            )}
        </div>
    );
}
