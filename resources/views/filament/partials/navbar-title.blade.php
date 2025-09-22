@php
    $subTitle = 'Portal Pengelolaan Berkas Notaris & PPAT';
    $title    = 'KOMANG HENDY PRABAWA, S.H., M.Kn';
@endphp

<style>
    .fi-topbar {
        position: relative !important;
        min-height: 56px;
    }

    /* Blok judul absolut: geser sedikit ke kanan (35%),
       lalu center relatif terhadap titik itu */
    #nf-topbar-title {
        position: absolute;
        top: 50%;
        left: 35%;
        transform: translate(-50%, -50%);
        z-index: 20;
        pointer-events: none;
        user-select: none;
        display: flex;
        flex-direction: column;
        align-items: center;   /* subtitle center terhadap title */
        text-align: center;
    }

    /* Light mode (default) */
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

    /* 🌙 Dark mode: cover beberapa kemungkinan class/atribut Filament */
    html.dark #nf-topbar-title .subtitle,
    .dark #nf-topbar-title .subtitle,
    [data-theme="dark"] #nf-topbar-title .subtitle,
    .fi-theme-dark #nf-topbar-title .subtitle,
    .fi-dark #nf-topbar-title .subtitle {
        color: rgba(255, 255, 255, 0.8) !important;
    }
    html.dark #nf-topbar-title .title,
    .dark #nf-topbar-title .title,
    [data-theme="dark"] #nf-topbar-title .title,
    .fi-theme-dark #nf-topbar-title .title,
    .fi-dark #nf-topbar-title .title {
        color: #ffffff !important;
    }
</style>

<div id="nf-topbar-title">
    <div class="subtitle">{{ $subTitle }}</div>
    <div class="title">{{ $title }}</div>
</div>
