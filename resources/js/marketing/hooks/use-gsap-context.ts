import { useGSAP } from '@gsap/react';
import type gsap from 'gsap';
import type { DependencyList, RefObject } from 'react';

type GsapContext = ReturnType<typeof gsap.context>;

type GsapCallback = (context: GsapContext) => void | (() => void);

/** Run GSAP animations scoped to a container ref with automatic cleanup. */
export function useScrollAnimation(
    containerRef: RefObject<HTMLElement | null>,
    callback: GsapCallback,
    dependencies: DependencyList = [],
    disabled = false,
): void {
    useGSAP(
        (context) => {
            if (disabled || !containerRef.current) {
                return;
            }

            return callback(context);
        },
        {
            scope: containerRef,
            dependencies: [disabled, ...dependencies],
        },
    );
}
