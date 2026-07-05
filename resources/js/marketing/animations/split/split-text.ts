import SplitType from 'split-type';

export type SplitResult = InstanceType<typeof SplitType>;

export type SplitMode = 'chars' | 'words' | 'lines';

export interface SplitOptions {
    types?: string;
    tagName?: string;
}

/** Create a SplitType instance for the given element. */
export function createSplitText(
    element: HTMLElement,
    mode: SplitMode = 'chars',
    options: SplitOptions = {},
): SplitResult {
    const typesMap: Record<SplitMode, string> = {
        chars: 'chars',
        words: 'words',
        lines: 'lines',
    };

    return new SplitType(element, {
        types: (options.types ?? typesMap[mode]) as 'lines' | 'words' | 'chars',
        tagName: options.tagName ?? 'span',
    });
}

/** Safely revert a SplitType instance and restore original text. */
export function revertSplitText(split: SplitResult | null): void {
    split?.revert();
}
