@props([
    'label',
    'state',
    'badge' => false,
    'color' => 'gray',  
])

<div>
    {{-- Bagian Label --}}
    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">
        {{ $label }}
    </p>

    {{-- Bagian Value (Nilai) --}}
    <div class="mt-1">
        @if ($badge)
            {{-- Tampilkan sebagai badge jika diminta --}}
            <span @class([
                'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                match ($color) {
                    'primary' => 'bg-primary-50 text-primary-600 ring-primary-600/10 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20',
                    'success' => 'bg-success-50 text-success-600 ring-success-600/10 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-500/20',
                    'warning' => 'bg-warning-50 text-warning-600 ring-warning-600/10 dark:bg-warning-500/10 dark:text-warning-400 dark:ring-warning-500/20',
                    default => 'bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-500/10 dark:text-gray-400 dark:ring-gray-500/20',
                },
            ])>
                {{ $state }}
            </span>
        @else
            {{-- Tampilkan sebagai teks biasa --}}
            <p class="text-base text-gray-900 dark:text-white">
                {{ $state ?? '-' }}
            </p>
        @endif
    </div>
</div>