import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/location-picker.js', 'resources/js/barcode-scanner.js'],
            refresh: true,
        }),
    ],
});
