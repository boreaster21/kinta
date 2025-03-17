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
                'resources/css/verify-email.css',
                'resources/css/admin/attendance-list.css',
                'resources/css/admin/staff-list.css',
                'resources/css/admin/monthly-attendance.css',
                'resources/css/admin/stamp-correction-request.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
}); 