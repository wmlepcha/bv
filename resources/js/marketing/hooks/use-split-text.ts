import { useCallback, useRef, useState } from 'react';
import {
    createSplitText,
    revertSplitText,
    type SplitMode,
    type SplitResult,
} from '@marketing/animations/split/split-text';

export function useSplitText(mode: SplitMode = 'chars') {
    const splitRef = useRef<SplitResult | null>(null);
    const elementRef = useRef<HTMLElement | null>(null);
    const [replayKey, setReplayKey] = useState(0);

    const split = useCallback(
        (element: HTMLElement | null) => {
            if (!element) {
                return null;
            }

            revertSplitText(splitRef.current);
            elementRef.current = element;
            splitRef.current = createSplitText(element, mode);

            return splitRef.current;
        },
        [mode, replayKey],
    );

    const replay = useCallback(() => {
        setReplayKey((k) => k + 1);
    }, []);

    const cleanup = useCallback(() => {
        revertSplitText(splitRef.current);
        splitRef.current = null;
    }, []);

    return { split, replay, cleanup, replayKey, elementRef };
}
