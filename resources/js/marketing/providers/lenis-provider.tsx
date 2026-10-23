import type Lenis from 'lenis';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useRef,
    useState
    
} from 'react';
import type {ReactNode} from 'react';
import { useIsMobile } from '@/hooks/use-mobile';
import { createLenis } from '@marketing/animations/lenis/create-lenis';
import {
    syncLenisWithScrollTrigger,
    unsyncLenisFromScrollTrigger,
} from '@marketing/animations/lenis/sync-scroll-trigger';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';
import { useScrollConfig } from '@marketing/hooks/use-scroll-config';
import { refreshScrollAnimations } from '@marketing/utils/scroll-cleanup';

interface LenisContextValue {
    lenis: Lenis | null;
    scrollTo: (target: number | string | HTMLElement, options?: object) => void;
}

const LenisContext = createContext<LenisContextValue>({
    lenis: null,
    scrollTo: () => undefined,
});

export function useLenis(): LenisContextValue {
    return useContext(LenisContext);
}

export function LenisProvider({ children }: { children: ReactNode }) {
    const { enabled } = useScrollConfig();
    const reducedMotion = useReducedMotion();
    const isMobile = useIsMobile();
    const lenisRef = useRef<Lenis | null>(null);
    const [lenis, setLenis] = useState<Lenis | null>(null);

    useEffect(() => {
        if (!enabled || reducedMotion) {
            document.documentElement.classList.remove('lenis', 'lenis-smooth');
            setLenis(null);

            return;
        }

        const instance = createLenis({ isMobile, reducedMotion });

        if (!instance) {
            setLenis(null);

            return;
        }

        lenisRef.current = instance;
        setLenis(instance);
        document.documentElement.classList.add('lenis', 'lenis-smooth');
        syncLenisWithScrollTrigger(instance);

        const onResize = debounce(() => {
            instance.resize();
            refreshScrollAnimations();
        }, 200);

        window.addEventListener('resize', onResize);

        return () => {
            window.removeEventListener('resize', onResize);
            unsyncLenisFromScrollTrigger(instance);
            instance.destroy();
            lenisRef.current = null;
            setLenis(null);
            document.documentElement.classList.remove('lenis', 'lenis-smooth');
        };
    }, [enabled, reducedMotion, isMobile]);

    const scrollTo = useCallback(
        (target: number | string | HTMLElement, options?: object) => {
            lenisRef.current?.scrollTo(target, options);
        },
        [],
    );

    const value = useMemo(
        () => ({ lenis, scrollTo }),
        [lenis, scrollTo],
    );

    return (
        <LenisContext.Provider value={value}>{children}</LenisContext.Provider>
    );
}

function debounce<T extends (...args: never[]) => void>(fn: T, ms: number): T {
    let timeout: ReturnType<typeof setTimeout>;

    return ((...args: Parameters<T>) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), ms);
    }) as T;
}
