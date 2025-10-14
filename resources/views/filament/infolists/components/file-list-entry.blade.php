@php
    // Ambil record utama (misalnya, Berkas) dari state komponen
    $mainRecord = $getRecord();
    // Ambil koleksi file dari relasi 'files' pada record utama
    $files = $mainRecord->files;
@endphp

{{--
File ini adalah komponen ViewEntry kustom untuk menampilkan daftar file lampiran.
Ia meniru tampilan tabel Filament untuk konsistensi UI.
--}}

@if ($files->isNotEmpty())
    {{-- Gunakan kelas CSS bawaan Filament untuk styling yang konsisten --}}
    <div
        class="fi-ta-ctn overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="fi-ta-header-cell px-4 py-3.5">
                        <span class="text-sm font-semibold text-gray-950 dark:text-white">Jenis Dokumen</span>
                    </th>
                    <th class="fi-ta-header-cell px-4 py-3.5">
                        <span class="text-sm font-semibold text-gray-950 dark:text-white">Nama File</span>
                    </th>
                    <th class="fi-ta-header-cell px-4 py-3.5 text-center">
                        <span class="text-sm font-semibold text-gray-950 dark:text-white">Aksi</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                {{-- Lakukan perulangan manual untuk setiap file --}}
                @foreach ($files as $file)
                    <tr class="fi-ta-row">
                        {{-- Kolom 1: Jenis Dokumen --}}
                        <td class="fi-ta-cell px-4 py-3">
                            <span class="text-sm text-gray-950 dark:text-white">
                                {{ Str::headline($file->type ?? 'Lainnya') }}
                            </span>
                        </td>
                        <td class="fi-ta-cell px-4 py-3">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ basename($file->path) }}
                            </span>
                        </td>
                        <td class="fi-ta-cell px-4 py-3 text-center">
                            <a href="{{ route('berkas-files.download', ['berkasFile' => $file]) }}" target="_blank"
                                class="fi-btn fi-btn-color-success" title="Unduh file">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                    class="h-5 w-5">
                                    <path
                                        d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" />
                                    <path
                                        d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada lampiran berkas.</p>
@endif
