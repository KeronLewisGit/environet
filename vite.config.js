import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Fonts are downloaded at build time and served from /build, so visitors never hit a font CDN.
// Brand match with environetinc.com: its logo sets "Environet, Inc." in a flared, calligraphic
// roman, matched here by Marcellus (the wordmark and the big page titles only); its tagline and
// pages use a humanist sans (Arial/Verdana on the old site), matched by Source Sans 3 for
// everything else.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Source Sans 3', {
                    alias: 'sans',
                    variable: '--ff-sans',
                    weights: [400, 600, 700],
                    styles: ['normal'],
                    subsets: ['latin', 'latin-ext'],
                    preload: [{ weight: 400 }, { weight: 700 }],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
                bunny('Marcellus', {
                    alias: 'brand',
                    variable: '--ff-brand',
                    weights: [400],
                    styles: ['normal'],
                    subsets: ['latin', 'latin-ext'],
                    preload: [{ weight: 400 }],
                    fallbacks: ['Georgia', 'serif'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: { watch: { ignored: ['**/storage/framework/views/**'] } },
});
