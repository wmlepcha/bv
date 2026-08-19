import { createInertiaApp } from '@inertiajs/react';
import type { ResolvedComponent } from '@inertiajs/react';
import { TooltipProvider } from '@/components/ui/tooltip';
import AppLayout from '@app/layouts/app-layout';
import AuthLayout from '@auth/layouts/auth-layout';
import AuthTrainingManagerLayout from '@auth/layouts/auth-training-manager-layout';
import CinematicLayout from '@marketing/layouts/cinematic-layout';
import SettingsLayout from '@app/layouts/settings/layout';
import { BRAND_NAME_TM } from '@shared/lib/brand';

const appName = BRAND_NAME_TM;

const pages = import.meta.glob<{ default: ResolvedComponent }>([
    './marketing/pages/**/*.tsx',
    './app/pages/**/*.tsx',
    './auth/pages/**/*.tsx',
]);

function resolvePagePath(name: string): string | null {
    if (name.startsWith('marketing/')) {
        return `./marketing/pages/${name.slice('marketing/'.length)}.tsx`;
    }

    if (name.startsWith('app/')) {
        return `./app/pages/${name.slice('app/'.length)}.tsx`;
    }

    if (name.startsWith('auth/')) {
        return `./auth/pages/${name.slice('auth/'.length)}.tsx`;
    }

    return null;
}

export default createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const path = resolvePagePath(name);

        if (!path || !pages[path]) {
            throw new Error(`Page not found: ${name}`);
        }

        const module = await pages[path]();

        return module.default;
    },
    layout: (name) => {
        if (name.startsWith('marketing/')) {
            return CinematicLayout;
        }

        if (name === 'auth/training-manager-login') {
            return AuthTrainingManagerLayout;
        }

        if (name.startsWith('auth/')) {
            return AuthLayout;
        }

        if (name.startsWith('app/settings/')) {
            return [AppLayout, SettingsLayout];
        }

        if (name.startsWith('app/')) {
            return AppLayout;
        }

        return AppLayout;
    },
    strictMode: true,
    withApp(app) {
        return <TooltipProvider delayDuration={0}>{app}</TooltipProvider>;
    },
});
