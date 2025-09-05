@php
    $record = $getRecord();
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Kolom Kiri --}}
    <div class="space-y-4">
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Status Overall</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">
                <span class="inline-flex items-center rounded-md bg-warning-50 px-2 py-1 text-xs font-medium text-warning-600 ring-1 ring-inset ring-warning-600/10 dark:bg-warning-500/10 dark:text-warning-400 dark:ring-warning-500/20">
                    {{ $record->status_overall?->value ?? '-' }}
                </span>
            </p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Ditugaskan Ke</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">
                {{ $record->status_overall?->value === 'selesai' ? 'Selesai (Tidak ada)' : $record->currentAssignee?->name ?? 'Belum ditugaskan' }}
            </p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Paid</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">
                {{ 'Rp ' . number_format($record->total_paid, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Kolom Kanan --}}
    <div class="space-y-4">
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Tahap Saat Ini</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">
                 <span class="inline-flex items-center rounded-md bg-warning-50 px-2 py-1 text-xs font-medium text-warning-600 ring-1 ring-inset ring-warning-600/10 dark:bg-warning-500/10 dark:text-warning-400 dark:ring-warning-500/20">
                    {{ $record->current_stage_key?->value ?? '-' }}
                </span>
            </p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Cost</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">
                {{ 'Rp ' . number_format($record->total_cost, 0, ',', '.') }}
            </p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Deadline At</p>
            <p class="mt-1 text-base text-gray-900 dark:text-white">
                {{ $record->deadline_at ? \Illuminate\Support\Carbon::parse($record->deadline_at)->translatedFormat('d F Y H:i') : '-' }}
            </p>
        </div>
    </div>
</div>