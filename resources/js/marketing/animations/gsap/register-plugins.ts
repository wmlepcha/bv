import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollToPlugin } from 'gsap/ScrollToPlugin';

let registered = false;

/** Register GSAP plugins once for the entire application. */
export function registerGsapPlugins(): void {
    if (registered) {
        return;
    }

    gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);
    registered = true;
}

export { gsap, ScrollTrigger, ScrollToPlugin };
