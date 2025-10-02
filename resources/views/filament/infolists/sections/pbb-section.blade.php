@php $record = $getRecord(); @endphp
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div>
        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Validasi PBB</p>
        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->pbb_validasi ?? '-' }}</p>
    </div>
    <div>
        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Akta BPJB</p>
        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->pbb_akta_bpjb ?? '-' }}</p>
    </div>
    <div>
        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">NOP</p>
        <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->pbb_nop ?? '-' }}</p>
    </div>

</div>
