import type { ReactNode } from 'react';
import { LenisProvider } from '@marketing/providers/lenis-provider';
import { ScrollConfigContext } from '@marketing/hooks/use-scroll-config';

export interface ScrollConfigProviderProps {
    children: ReactNode;
    enabled?: boolean;
    transitions?: boolean;
}

/** Per-page scroll and transition overrides. */
export function ScrollConfigProvider({
    children,
    enabled = true,
    transitions = true,
}: ScrollConfigProviderProps) {
    return (
        <ScrollConfigContext.Provider value={{ enabled, transitions }}>
            {children}
        </ScrollConfigContext.Provider>
    );
}

export function ScrollProvider({ children }: { children: ReactNode }) {
    return <LenisProvider>{children}</LenisProvider>;
}

export { useScrollConfig } from '@marketing/hooks/use-scroll-config';
