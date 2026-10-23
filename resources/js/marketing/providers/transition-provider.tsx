import type { PendingVisit, VisitOptions } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import {
    createContext,
    useContext,
    useEffect,
    useRef,
    useState
    
} from 'react';
import type {ReactNode} from 'react';
import { gsap } from '@marketing/animations/gsap/register-plugins';
import {
    createEnterTransition,
    createExitTransition,
    resetPageTransition
    
} from '@marketing/animations/gsap/timelines/transitions';
import type {TransitionType} from '@marketing/animations/gsap/timelines/transitions';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';
import { useScrollConfig } from '@marketing/hooks/use-scroll-config';
import {
    refreshScrollAnimations,
    resetScrollPosition,
} from '@marketing/utils/scroll-cleanup';

interface TransitionContextValue {
    type: TransitionType;
    setType: (type: TransitionType) => void;
    isTransitioning: boolean;
}

const TransitionContext = createContext<TransitionContextValue>({
    type: 'fade',
    setType: () => undefined,
    isTransitioning: false,
});

export function usePageTransition(): TransitionContextValue {
    return useContext(TransitionContext);
}

const DISABLED_PREFIXES = ['auth/', 'app/settings/'];

function shouldDisableTransitions(component: string): boolean {
    return DISABLED_PREFIXES.some((prefix) => component.startsWith(prefix));
}

function isSamePageVisit(visit: PendingVisit): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    const currentUrl = new URL(window.location.href);

    return visit.url.pathname === currentUrl.pathname && visit.url.search === currentUrl.search;
}

function shouldAnimateVisit(visit: PendingVisit): boolean {
    if (visit.prefetch) {
        return false;
    }

    const isPartialReload =
        visit.only.length > 0 ||
        visit.except.length > 0 ||
        visit.reset.length > 0;

    if (isPartialReload) {
        return false;
    }

    if (visit.method !== 'get') {
        return false;
    }

    if (visit.preserveState && isSamePageVisit(visit)) {
        return false;
    }

    const component = String(visit.component ?? '');

    return !shouldDisableTransitions(component);
}

function visitOptionsFromPendingVisit(visit: PendingVisit): VisitOptions {
    return {
        method: visit.method,
        data: visit.data,
        replace: visit.replace,
        preserveScroll: visit.preserveScroll,
        preserveState: visit.preserveState,
        only: visit.only,
        except: visit.except,
        headers: visit.headers,
        errorBag: visit.errorBag,
        forceFormData: visit.forceFormData,
        queryStringArrayFormat: visit.queryStringArrayFormat,
        async: visit.async,
        showProgress: visit.showProgress,
        fresh: visit.fresh,
        reset: visit.reset,
        preserveUrl: visit.preserveUrl,
        preserveErrors: visit.preserveErrors,
        invalidateCacheTags: visit.invalidateCacheTags,
        viewTransition: visit.viewTransition,
    };
}

export function TransitionProvider({ children }: { children: ReactNode }) {
    const { transitions: transitionsEnabled } = useScrollConfig();
    const reducedMotion = useReducedMotion();
    const [type, setType] = useState<TransitionType>('fade');
    const [isTransitioning, setIsTransitioning] = useState(false);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const isResumingVisit = useRef(false);
    const typeRef = useRef(type);

    useEffect(() => {
        typeRef.current = type;
    }, [type]);

    useEffect(() => {
        if (!transitionsEnabled || reducedMotion) {
            const removeNavigate = router.on('navigate', () => {
                refreshScrollAnimations();
            });

            return () => {
                removeNavigate();
            };
        }

        const removeBefore = router.on('before', (event) => {
            if (isResumingVisit.current) {
                isResumingVisit.current = false;

                return;
            }

            const visit = event.detail.visit;

            if (!shouldAnimateVisit(visit) || !wrapperRef.current) {
                return;
            }

            event.preventDefault();
            setIsTransitioning(true);

            createExitTransition(wrapperRef.current, typeRef.current).then(() => {
                if (!visit.preserveScroll) {
                    resetScrollPosition(true);
                }

                isResumingVisit.current = true;
                router.visit(visit.url.href, visitOptionsFromPendingVisit(visit));
            });

            return false;
        });

        const removeNavigate = router.on('navigate', () => {
            if (!wrapperRef.current) {
                refreshScrollAnimations();

                return;
            }

            createEnterTransition(wrapperRef.current, typeRef.current).then(() => {
                refreshScrollAnimations();
                setIsTransitioning(false);
            });
        });

        const removeFinish = router.on('finish', () => {
            if (!wrapperRef.current) {
                return;
            }

            const opacity = Number(gsap.getProperty(wrapperRef.current, 'opacity') ?? 1);

            if (opacity < 1) {
                resetPageTransition(wrapperRef.current);
                refreshScrollAnimations();
                setIsTransitioning(false);
            }
        });

        return () => {
            removeBefore();
            removeNavigate();
            removeFinish();
        };
    }, [transitionsEnabled, reducedMotion]);

    return (
        <TransitionContext.Provider value={{ type, setType, isTransitioning }}>
            <div
                ref={wrapperRef}
                id="page-transition-wrapper"
                className="relative"
                data-transitioning={isTransitioning}
            >
                {children}
            </div>
        </TransitionContext.Provider>
    );
}
