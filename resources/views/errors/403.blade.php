<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak</title>

    <!-- Menggunakan Google Fonts agar terlihat profesional -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* CSS Biasa, tidak memerlukan Tailwind atau Vite */
        :root {
            --color-primary: #f59e0b; /* Amber 500 */
            --color-primary-hover: #d97706; /* Amber 600 */
            --color-danger: #ef4444; /* Red 500 */
            --color-text-dark: #1f2937;
            --color-text-light: #6b7280;
            --color-bg-light: #f9fafb;
            --color-card-bg: #ffffff;
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            line-height: 1.5;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--color-bg-light);
        }
        .container {
            width: 100%;
            max-width: 28rem; /* 448px */
            padding: 2rem; /* 32px */
            text-align: center;
        }
        .card {
            background-color: var(--color-card-bg);
            border-radius: 0.5rem; /* 8px */
            padding: 2.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        h1 {
            font-size: 3.75rem; /* 60px */
            font-weight: 800;
            color: var(--color-danger);
            margin: 0;
        }
        h2 {
            margin-top: 1rem;
            font-size: 1.5rem; /* 24px */
            font-weight: 700;
            color: var(--color-text-dark);
        }
        p {
            margin-top: 0.5rem;
            color: var(--color-text-light);
        }
        .button {
            display: inline-flex;
            align-items: center;
            margin-top: 1.5rem;
            padding: 0.5rem 1rem;
            background-color: var(--color-primary);
            color: white;
            border: none;
            border-radius: 0.375rem; /* 6px */
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }
        .button:hover {
            background-color: var(--color-primary-hover);
        }
        .button svg {
            width: 1.25rem; /* 20px */
            height: 1.25rem;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>403</h1>
            <h2>Akses Ditolak</h2>
            <p>Maaf, Anda tidak memiliki izin untuk mengakses halaman yang Anda tuju. Silakan kembali ke dasbor.</p>
            <a href="/admin" class="button">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h7.5" />
                </svg>
                Kembali ke Dasbor
            </a>
        </div>
    </div>
</body>
</html>