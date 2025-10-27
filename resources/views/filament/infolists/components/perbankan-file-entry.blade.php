@php
    // Ambil file pertama dari relasi 'files'
    $file = $getRecord()->files->first();
@endphp

@if ($file)
    <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
        {{-- Bagian Kiri: Pratinjau & Nama File --}}
        <div class="flex flex-1 items-center gap-3">
            {{-- Pratinjau (jika gambar) --}}
            <div class="flex-shrink-0">
                @if (Str::is(['*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp'], strtolower($file->path)))
                    <img src="{{ Storage::url($file->path) }}" alt="Pratinjau" class="h-10 w-10 rounded-md object-cover">
                @else
                    {{-- Ikon file generik jika bukan gambar --}}
                    <div class="flex h-10 w-10  rounded-md bg-gray-100 dark:bg-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6 text-gray-400 dark:text-gray-500">
                            <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm2.25 8.5a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5h-6.5Zm0 3a.75.75 0 0 0 0 1.5h3.5a.75.75 0 0 0 0-1.5h-3.5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                @endif
            </div>
            {{-- Nama File --}}
            <div>
                <p class="text-sm font-medium text-gray-950 dark:text-white">
                    Berkas Bank Terlampir
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ basename($file->path) }}
                </p>
            </div>
        </div>

        {{-- Bagian Kanan: Tombol Aksi --}}
        <div class="flex-shrink-0">
            <a
                href="{{ route('perbankan-files.download', ['perbankanFile' => $file]) }}"
                target="_blank"
                class="fi-btn fi-btn-color-success"
                title="Unduh file"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                    <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" />
                    <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                </svg>
            </a>
        </div>
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada berkas bank yang dilampirkan.</p>
@endif