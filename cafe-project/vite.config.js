import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
        server: {
        host: '0.0.0.0',
        cors: true,
        hmr: {
            host: '192.168.12.15',
        },
    },
    plugins: [
        laravel({
            input: 'resources/css/app.css',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        
        tailwindcss(),
    ],
});