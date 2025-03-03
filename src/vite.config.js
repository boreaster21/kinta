import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/auth.css',
                'resources/css/attendance.css',
                'resources/css/attendance-list.css',
                'resources/css/attendance-detail.css',
                'resources/css/stamp_correction_request.css',
                'resources/js/app.js',
                'resources/js/stamp_correction_request.js', 
            ],
            refresh: true,
        }),
    ],
    server: {
        host: true,
    },
});
