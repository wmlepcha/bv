import { useIsMobile } from '@/hooks/use-mobile';
import { useReducedMotion } from '@marketing/hooks/use-reduced-motion';

/** Whether scroll-driven GSAP effects (pinning, horizontal scroll) should run. */
export function useScrollEffectsEnabled(): boolean {
    const isMobile = useIsMobile();
    const reducedMotion = useReducedMotion();

    return !isMobile && !reducedMotion;
}
