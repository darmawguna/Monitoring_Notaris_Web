@php
    $subTitle = 'Portal Pengelolaan Berkas Notaris & PPAT';
    $title = 'KOMANG HENDY PRABAWA, S.H., M.Kn';
@endphp

<style>
    .fi-topbar {
        position: relative !important;
        min-height: 56px;
    }

    /* --- GAYA DEFAULT UNTUK DESKTOP --- */
    #nf-topbar-title {
        position: absolute;
        top: 50%;
        left: 35%;
        transform: translate(-50%, -50%);
        z-index: 20;
        pointer-events: none;
        user-select: none;

        /* --- PERUBAHAN 1: Gunakan Flexbox untuk mensejajarkan logo dan teks --- */
        display: flex;
        align-items: center;
        /* Sejajarkan item secara vertikal di tengah */
        gap: 1.5rem;
        /* Beri jarak antara logo dan blok teks */
    }

    /* Blok untuk teks (judul & subjudul) */
    .nf-topbar-text {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    /* --- PERUBAHAN 2: Tambahkan gaya untuk logo --- */
    .nf-topbar-logo {
        height: 40px;
        /* Atur tinggi logo */
        width: 40px;
        /* Atur lebar logo agar sama persis */
        object-fit: contain;
        /* Mencegah gambar menjadi gepeng/terdistorsi */
    }

    /* Gaya untuk teks (tidak berubah) */
    #nf-topbar-title .subtitle {
        font-size: 0.7rem;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.75) !important;
        line-height: 1rem;
        margin-bottom: 2px;
    }

    #nf-topbar-title .title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #000000 !important;
        line-height: 1.25rem;
    }

    /* 🌙 Dark mode (tidak berubah) */
    html.dark #nf-topbar-title .subtitle,
    .dark #nf-topbar-title .subtitle,
    .fi-dark #nf-topbar-title .subtitle {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    html.dark #nf-topbar-title .title,
    .dark #nf-topbar-title .title,
    .fi-dark #nf-topbar-title .title {
        color: #ffffff !important;
    }

    /* --- PERBAIKAN RESPONSIVE UNTUK MOBILE (tidak berubah) --- */
    /* Aturan ini akan menyembunyikan seluruh blok #nf-topbar-title, termasuk logo di dalamnya */
    @media (max-width: 768px) {
        #nf-topbar-title {
            display: none;
        }
    }
</style>

<!-- --- PERUBAHAN 3: Perbarui Struktur HTML --- -->
<div id="nf-topbar-title">
    <!-- Logo Kiri -->
    <img src="{{ asset('images/notaris.png') }}" alt="Logo Kiri" class="nf-topbar-logo">

    <!-- Blok Judul & Subjudul -->
    <div class="nf-topbar-text">
        <div class="subtitle">{{ $subTitle }}</div>
        <div class="title">{{ $title }}</div>
    </div>

    <!-- Logo Kanan -->
    <img src="{{ asset('images/ppat.png') }}" alt="Logo Kanan" class="nf-topbar-logo">
</div>
