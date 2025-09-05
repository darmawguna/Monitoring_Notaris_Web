@php
    // Ambil semua data progres dari record utama
    $progressHistory = $getRecord()->progress()->orderBy('created_at', 'asc')->get();
@endphp

{{-- Wadah untuk tabel, dengan sedikit padding --}}
<div class="p-2">
    {{-- Tabel dengan styling dasar --}}
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-6 py-3">Petugas</th>
                <th scope="col" class="px-6 py-3">Tahapan & Status</th>
                <th scope="col" class="px-6 py-3">Tanggal Selesai</th>
                <th scope="col" class="px-6 py-3">Durasi Pengerjaan</th>
            </tr>
        </thead>
        <tbody>
            {{-- Lakukan perulangan untuk setiap entri progres --}}
            @forelse ($progressHistory as $progress)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    {{-- Kolom Petugas --}}
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $progress->assignee->name ?? 'N/A' }}
                    </th>
                    
                    {{-- --- INI BAGIAN YANG DIPERBARUI --- --}}
                    {{-- Kolom Tahapan & Status --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2">
                            {{-- Nama Tahapan --}}
                            <span>{{ $progress->stage_key?->value ?? '-' }}</span>

                            {{-- Badge Status --}}
                            @if ($progress->status === 'done')
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">
                                    Selesai
                                </span>
                            @else
                                <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-yellow-900 dark:text-yellow-300">
                                    Dalam Proses
                                </span>
                            @endif
                        </div>
                    </td>
                    {{-- --- AKHIR DARI PERUBAHAN --- --}}

                    {{-- Kolom Tanggal Selesai --}}
                    <td class="px-6 py-4">
                        {{ $progress->completed_at ? \Illuminate\Support\Carbon::parse($progress->completed_at)->translatedFormat('d F Y, H:i') : '-' }}
                    </td>
                    {{-- Kolom Durasi --}}
                    <td class="px-6 py-4">
                        @php
                            $duration = 'N/A';
                            if ($progress->started_at && $progress->completed_at) {
                                $start = \Illuminate\Support\Carbon::parse($progress->started_at);
                                $end = \Illuminate\Support\Carbon::parse($progress->completed_at);
                                $duration = $start->diffForHumans($end, true);
                            }
                        @endphp
                        {{ $duration }}
                    </td>
                </tr>
            @empty
                {{-- Tampilkan ini jika tidak ada riwayat progres --}}
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                        Belum ada riwayat pengerjaan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
