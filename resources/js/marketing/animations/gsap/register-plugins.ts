import gsap from 'gsap';
import { ScrollToPlugin } from 'gsap/ScrollToPlugin';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

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
