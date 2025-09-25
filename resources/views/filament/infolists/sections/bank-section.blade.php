@php $record = $getRecord(); @endphp
<div>
    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Bank / Kredit Bank</p>
    <p class="mt-1 text-base text-gray-900 dark:text-white">{{ $record->bank_kredit ?? '-' }}</p>
</div>