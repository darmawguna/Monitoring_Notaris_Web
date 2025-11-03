import { defineConfig } from 'vite';
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"  ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            input: {
                // Tambahkan theme Filament sebagai entry point terpisah
                "filament/admin/theme":
                    "resources/css/filament/admin/theme.css",
            },
        },
    },
});
