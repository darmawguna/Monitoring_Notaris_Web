@php
    $record = $getRecord();
    // 1. Definisikan "kamus" atau pemetaan untuk tipe sertifikat
    $tipeSertifikatMap = [
        'hm' => 'Hak Milik (HM)',
        'hgb' => 'Hak Guna Bangunan (HGB)',
        'hp' => 'Hak Pakai (HP)',
        'hu' => 'Hak Guna Usaha (HU)',
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div>
        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Nomor Sertifikat</p>
        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->sertifikat_nomor ?? '-' }}</p>
    </div>
    <div>
        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Luas</p>
        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->sertifikat_luas ? $record->sertifikat_luas . ' m²' : '-' }}</p>
    </div>
    <div>
        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Jenis</p>
        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ Str::ucfirst($record->sertifikat_jenis) ?? '-' }}</p>
    </div>
    
    {{-- --- INI BAGIAN YANG DIPERBARUI --- --}}
    <div>
        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Tipe</p>
        {{-- 2. Gunakan pemetaan untuk menampilkan label lengkap --}}
        <p class="mt-1 text-base text-gray-900 dark:text-white">
            {{ $tipeSertifikatMap[$record->sertifikat_tipe] ?? Str::ucfirst($record->sertifikat_tipe) ?? '-' }}
        </p>
    </div>
    {{-- --- AKHIR DARI PERUBAHAN --- --}}
    
    <div>
        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Nilai Transaksi</p>
        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->nilai_transaksi ? 'Rp ' . number_format($record->nilai_transaksi, 0, ',', '.') : '-' }}</p>
    </div>
</div>
