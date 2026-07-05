import { createContext, useContext } from 'react';

export interface ScrollConfig {
    enabled: boolean;
    transitions: boolean;
}

const defaultConfig: ScrollConfig = {
    enabled: true,
    transitions: true,
};

export const ScrollConfigContext = createContext<ScrollConfig>(defaultConfig);

export function useScrollConfig(): ScrollConfig {
    return useContext(ScrollConfigContext);
}
