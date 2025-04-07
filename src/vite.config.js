import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',      // Main user CSS entry point
                'resources/css/admin.css',     // Main admin CSS entry point
                'resources/js/app.js',         // Main JS entry point
                'resources/js/attendance.js',  // Specific JS for attendance page
                'resources/js/layouts/admin.js', // Specific JS for admin layout
            ],
            refresh: true,
        }),
    ],
    server: {
        host: true,
    },
});
