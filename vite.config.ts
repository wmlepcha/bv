import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny, google } from 'laravel-vite-plugin/fonts';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
            port: 5173,
        },
    },
    resolve: {
        alias: {
            '@/components/ui': fileURLToPath(
                new URL('./resources/js/shared/components/ui', import.meta.url),
            ),
            '@/lib': fileURLToPath(new URL('./resources/js/shared/lib', import.meta.url)),
            '@/hooks': fileURLToPath(new URL('./resources/js/shared/hooks', import.meta.url)),
            '@/types': fileURLToPath(new URL('./resources/js/shared/types', import.meta.url)),
            '@shared': fileURLToPath(new URL('./resources/js/shared', import.meta.url)),
            '@marketing': fileURLToPath(new URL('./resources/js/marketing', import.meta.url)),
            '@app': fileURLToPath(new URL('./resources/js/app', import.meta.url)),
            '@auth': fileURLToPath(new URL('./resources/js/auth', import.meta.url)),
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            // Avoid binding Vite to bilingualvitae.test when Herd DNS is unavailable
            // (e.g. while using `php artisan serve` on 127.0.0.1:8000).
            detectTls: false,
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                google('Changa One', {
                    weights: [400],
                    styles: ['normal', 'italic'],
                }),
            ],
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: [
                'favicon.ico',
                'favicon.svg',
                'apple-touch-icon*.png',
                'favicon-*.png',
                'mstile-*.png',
                'pwa-192x192.png',
                'pwa-512x512.png',
            ],
            manifest: {
                name: `${process.env.VITE_APP_NAME || 'Bilingual Vitae'}®`,
                short_name: 'BV',
                description:
                    'Helping people become workplace-ready with strong communication and professional skills.',
                theme_color: '#FFF8E7',
                background_color: '#FFF8E7',
                display: 'standalone',
                start_url: '/',
                scope: '/',
                icons: [
                    {
                        src: '/pwa-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/pwa-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable',
                    },
                ],
            },
            workbox: {
                navigateFallback: null,
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/images\.unsplash\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'unsplash-images',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 30 * 24 * 60 * 60,
                            },
                        },
                    },
                    {
                        urlPattern: /\/storage\/images\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'local-images',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 30 * 24 * 60 * 60,
                            },
                        },
                    },
                    {
                        urlPattern: ({ request }) => request.headers.get('X-Inertia') === 'true',
                        handler: 'NetworkOnly',
                    },
                    {
                        urlPattern: ({ request, url }) =>
                            request.method === 'GET' &&
                            url.pathname.match(/^\/(about|services|projects|success-stories)?$/),
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'marketing-pages',
                            networkTimeoutSeconds: 3,
                        },
                    },
                ],
            },
        }),
    ],
});
