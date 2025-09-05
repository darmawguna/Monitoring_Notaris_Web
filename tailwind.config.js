/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./app/Filament/**/*.php",
        "./resources/views/filament/**/*.blade.php",
        "./vendor/filament/**/*.blade.php",
        // Tambahkan path ke file error kustom Anda
        "./resources/views/errors/**/*.blade.php",
    ],
    theme: {
        extend: {},
    },
    plugins: [],
};

