@php
    $record = $getRecord();
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Kolom Kiri --}}
    <div class="space-y-4">
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Nama Berkas</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->nama_berkas ?? '-' }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Penjual</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->penjual ?? '-' }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Sertifikat Nama</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->sertifikat_nama ?? '-' }}</p>
        </div>
    </div>

    {{-- Kolom Kanan --}}
    <div class="space-y-4">
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Nomor Berkas</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->nomor ?? '-' }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Pembeli</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->pembeli ?? '-' }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Persetujuan</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->persetujuan ?? '-' }}</p>
        </div>
    </div>
</div>