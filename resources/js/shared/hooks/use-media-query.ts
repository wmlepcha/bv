import { useEffect, useState } from 'react';

/** Subscribe to a CSS media query match. */
export function useMediaQuery(query: string): boolean {
    const [matches, setMatches] = useState(() => {
        if (typeof window === 'undefined') {
            return false;
        }

        return window.matchMedia(query).matches;
    });

    useEffect(() => {
        const media = window.matchMedia(query);
        const onChange = (event: MediaQueryListEvent) => setMatches(event.matches);

        setMatches(media.matches);
        media.addEventListener('change', onChange);

        return () => media.removeEventListener('change', onChange);
    }, [query]);

    return matches;
}

export function useIsTablet(): boolean {
    return useMediaQuery('(min-width: 768px) and (max-width: 1023px)');
}

export function useIsDesktop(): boolean {
    return useMediaQuery('(min-width: 1024px)');
}

export function useIsMobile(): boolean {
    return useMediaQuery('(max-width: 639px)');
}

/** Short mobile viewports (e.g. iPhone SE at 375×667). */
export function useIsExtraSmallPhone(): boolean {
    return useMediaQuery('(max-height: 720px) and (max-width: 639px)');
}
