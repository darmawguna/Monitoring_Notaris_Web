@php
    $record = $getRecord();
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    {{-- Data Penjual --}}
    <div class="space-y-4 p-4 border rounded-lg">
        <h3 class="font-bold text-lg">Data Penjual</h3>
        @if($record->penjual_data)
            <div>
                <p class="text-sm font-medium text-gray-500">Nama</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->penjual_data['nama'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">NIK</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->penjual_data['nik'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">No. Telp</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->penjual_data['telp'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Alamat</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->penjual_data['alamat'] ?? '-' }}</p>
            </div>
        @else
            <p class="text-gray-500">Tidak ada data.</p>
        @endif
    </div>

    {{-- Data Pembeli --}}
    <div class="space-y-4 p-4 border rounded-lg">
        <h3 class="font-bold text-lg">Data Pembeli</h3>
        @if($record->pembeli_data)
            <div>
                <p class="text-sm font-medium text-gray-500">Nama</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->pembeli_data['nama'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">NIK</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->pembeli_data['nik'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">No. Telp</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->pembeli_data['telp'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Alamat</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->pembeli_data['alamat'] ?? '-' }}</p>
            </div>
        @else
            <p class="text-gray-500">Tidak ada data.</p>
        @endif
    </div>

    {{-- Data Pihak Persetujuan --}}
    <div class="space-y-4 p-4 border rounded-lg">
        <h3 class="font-bold text-lg">Pihak Persetujuan</h3>
        @if($record->pihak_persetujuan_data)
             <div>
                <p class="text-sm font-medium text-gray-500">Nama</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->pihak_persetujuan_data['nama'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">NIK</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->pihak_persetujuan_data['nik'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">No. Telp</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->pihak_persetujuan_data['telp'] ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Alamat</p>
                <p class="text-base text-gray-900 dark:text-white">{{ $record->pihak_persetujuan_data['alamat'] ?? '-' }}</p>
            </div>
        @else
            <p class="text-gray-500">Tidak ada data.</p>
        @endif
    </div>
</div>