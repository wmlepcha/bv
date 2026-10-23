import { Button } from '@/components/ui/button';

const CHARS = ['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'];

export function GermanCharToolbar({
    onInsert,
}: {
    onInsert: (char: string) => void;
}) {
    return (
        <div className="flex flex-wrap gap-1">
            {CHARS.map((char) => (
                <Button
                    key={char}
                    type="button"
                    variant="outline"
                    size="sm"
                    className="h-7 min-w-7 px-2 font-serif"
                    onClick={() => onInsert(char)}
                >
                    {char}
                </Button>
            ))}
        </div>
    );
}

export function insertAtCursor(
    value: string,
    insert: string,
    selectionStart: number,
    selectionEnd: number,
): { value: string; cursor: number } {
    const next =
        value.slice(0, selectionStart) + insert + value.slice(selectionEnd);

    return { value: next, cursor: selectionStart + insert.length };
}
