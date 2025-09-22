@php
    $record = $getRecord();
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div>
        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Nomor Berkas</p>
        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->nomor_berkas ?? '-' }}</p>
    </div>
    <div>
        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Nama Berkas</p>
        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->nama_berkas ?? '-' }}</p>
    </div>
    <div>
        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Nama Pemohon</p>
        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->nama_pemohon ?? '-' }}</p>
    </div>
    <div><p class="text-sm font-medium text-gray-500 uppercase tracking-wider">SPPT</p><p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->pbb_sppt ?? '-' }}</p></div>
    <div><p class="text-sm font-medium text-gray-500 uppercase tracking-wider">NOP</p><p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->pbb_nop ?? '-' }}</p></div>
</div>