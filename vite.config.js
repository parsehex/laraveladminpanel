import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/inventory-testing.ts',
                'resources/js/pages/testing-flow-editor.ts',
            ],
            refresh: true,
        }),
    ],
});
