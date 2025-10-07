@php
    // Ambil record utama (TurunWaris) dari state
    $mainRecord = $getRecord();
    // Ambil koleksi file dari relasi 'files'
    $files = $mainRecord->files;
@endphp

@if ($files->isNotEmpty())
    <div class="fi-ta-ctn overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:px-6">
                        <span class="text-sm font-semibold text-gray-950 dark:text-white">Jenis Dokumen</span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:px-6">
                        <span class="text-sm font-semibold text-gray-950 dark:text-white">Nama File</span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:px-6">
                        <span class="text-sm font-semibold text-gray-950 dark:text-white">Pratinjau</span>
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 sm:px-6 text-center">
                        <span class="text-sm font-semibold text-gray-950 dark:text-white">Aksi</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                @foreach ($files as $file)
                    <tr class="fi-ta-row">
                        {{-- Kolom 1: Jenis Dokumen --}}
                        <td class="fi-ta-cell p-0 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            <div class="px-3 py-4">
                                <span class="text-sm text-gray-950 dark:text-white">
                                    {{ Str::headline($file->type ?? 'Lainnya') }}
                                </span>
                            </div>
                        </td>

                        {{-- Kolom 2: Nama File --}}
                        <td class="fi-ta-cell p-0 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            <div class="px-3 py-4">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ basename($file->path) }}
                                </span>
                            </div>
                        </td>

                        {{-- Kolom 3: Pratinjau (jika gambar) --}}
                        <td class="fi-ta-cell p-0 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            <div class="px-3 py-4">
                                @if (Str::is(['*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp'], strtolower($file->path)))
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($file->path) }}" alt="Pratinjau" class="h-10 w-10 rounded-md object-cover">
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6 text-gray-400 dark:text-gray-500">
                                        <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 11.378 2H4.5Zm2.25 8.5a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5h-6.5Zm0 3a.75.75 0 0 0 0 1.5h3.5a.75.75 0 0 0 0-1.5h-3.5Z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                        </td>

                        {{-- Kolom 4: Tombol Aksi dengan URL yang Benar untuk Turun Waris --}}
                        <td class="fi-ta-cell p-0 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                            <div class="px-3 py-4 text-center">
                                <a
                                    href="{{ route('turun-waris-files.download', ['turunWarisFile' => $file]) }}"
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
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada lampiran berkas.</p>
@endif