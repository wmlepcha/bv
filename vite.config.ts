import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny, google } from 'laravel-vite-plugin/fonts';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

// Set VITE_TUNNEL=true when sharing the dev server through `cloudflared tunnel
// --url http://127.0.0.1:5173` (see README/CLAUDE.md). In that mode the tunnel
// becomes the page origin, so HMR must ride the tunnel's HTTPS port (443)
// instead of a bare 127.0.0.1 address the remote browser can't reach, and any
// request that isn't a Vite/dev asset must be proxied on to Herd so the app
// still works end to end through the single tunneled origin.
const tunnelMode = process.env.VITE_TUNNEL === 'true';

export default defineConfig({
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: tunnelMode ? { clientPort: 443 } : { host: '127.0.0.1', port: 5173 },
        proxy: tunnelMode
            ? {
                  '^/(?!(@vite|@id|@fs|@react-refresh|node_modules|resources|storage|build|favicon|apple-touch-icon|mstile|pwa-|manifest\\.webmanifest|sw\\.js|registerSW\\.js)).*':
                      {
                          target: 'https://bilingualvitae.test',
                          changeOrigin: true,
                          secure: false,
                      },
              }
            : undefined,
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
