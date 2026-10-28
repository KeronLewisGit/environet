import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Fonts are downloaded at build time and served from /build, so visitors never hit a font CDN.
// Two families only: Space Grotesk, a technical grotesque with instrument-panel numerals, for
// headings, figures and the registration tag; IBM Plex Sans, an engineered sans built for
// documents and data, for reading.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Space Grotesk', {
                    alias: 'display',
                    variable: '--ff-display',
                    weights: [500, 600, 700],
                    styles: ['normal'],
                    subsets: ['latin', 'latin-ext'],
                    preload: [{ weight: 700 }],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
                bunny('IBM Plex Sans', {
                    alias: 'sans',
                    variable: '--ff-sans',
                    weights: [400, 500, 600, 700],
                    styles: ['normal'],
                    subsets: ['latin', 'latin-ext'],
                    preload: [{ weight: 400 }, { weight: 600 }],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: { watch: { ignored: ['**/storage/framework/views/**'] } },
});
