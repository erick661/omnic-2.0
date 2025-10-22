import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import fs from 'fs';

export default defineConfig({
    server: {
        host: 'dev-estadisticas.orpro.cl',
        https: {
            key: fs.readFileSync('/var/www/omnic/.cert/wildcard_orpro_cl.key'),
            cert: fs.readFileSync('/var/www/omnic/.cert/fullchain.crt'),
        },
        hmr: {
            host: 'dev-estadisticas.orpro.cl',
            protocol: 'wss',
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
