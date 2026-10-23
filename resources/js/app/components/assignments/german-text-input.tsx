import { useRef } from 'react';
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
                />
            ) : (
                <Input
                    ref={ref}
                    id={id}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={placeholder}
                />
            )}
        </div>
    );
}
