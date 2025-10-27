@php
    $progressHistory = $getRecord()->progress()->orderBy('created_at', 'asc')->get();
@endphp

{{-- CSS diletakkan di sini, lengkap dengan styling untuk mode gelap --}}
<style>
    /* Default (Light Mode) Styles */
    .progress-table { width: 100%; font-size: 0.875rem; text-align: left; color: #6b7280; }
    .progress-table thead { font-size: 0.75rem; text-transform: uppercase; background-color: #f9fafb; }
    .progress-table th, .progress-table td { padding: 0.75rem 1.5rem; vertical-align: top; }
    .progress-table tbody tr { background-color: white; border-bottom: 1px solid #e5e7eb; }
    .progress-table tbody tr:hover { background-color: #f9fafb; }
    .progress-table .petugas-name { font-weight: 500; color: #111827; white-space: nowrap; }

    .progress-notes {
        font-size: 0.875rem;
        color: #374151;
        white-space: normal; /* Izinkan catatan untuk wrap */
        line-height: 1.5;
        margin-top: 0.5rem;
        padding: 0.5rem;
        background-color: #fafafa;
        border-radius: 0.25rem;
        border: 1px solid #f3f4f6;
    }
    .progress-notes strong { color: #111827; }

    .progress-mobile { display: none; padding: 0.5rem; }
    .progress-mobile-card { background-color: white; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .progress-mobile .label { font-size: 0.75rem; color: #6b7280; }
    .progress-mobile .value { font-weight: 600; color: #111827; margin-bottom: 0.75rem; }
    .badge-done { background-color: #dcfce7; color: #166534; }
    .badge-pending { background-color: #fef9c3; color: #854d0e; }
    .badge { font-size: 0.75rem; font-weight: 500; margin-left: 0.5rem; padding: 0.125rem 0.625rem; border-radius: 9999px; }

    /* Dark Mode Overrides */
    .dark .progress-table thead { background-color: #374151; color: #9ca3af; }
    .dark .progress-table tbody tr { background-color: #1f2937; border-color: #374151; }
    .dark .progress-table tbody tr:hover { background-color: #374151; }
    .dark .progress-table .petugas-name { color: #ffffff; }

    .dark .progress-notes {
        background-color: #2b3748;
        border-color: #374151;
        color: #d1d5db;
    }
    .dark .progress-notes strong { color: #ffffff; }

    .dark .progress-mobile-card { background-color: #1f2937; border-color: #374151; }
    .dark .progress-mobile .label { color: #9ca3af; }
    .dark .progress-mobile .value { color: #ffffff; }
    .dark .badge-done { background-color: #166534; color: #86efac; }
    .dark .badge-pending { background-color: #854d0e; color: #fde047; }

    /* Responsive Logic */
    @media (max-width: 768px) {
        .progress-desktop { display: none; }
        .progress-mobile { display: block; }
    }
</style>

{{-- 1. TAMPILAN DESKTOP (Tabel yang sudah ada) --}}
<div class="progress-desktop p-2">
    <table class="progress-table">
        <thead>
            <tr>
                <th>Petugas</th>
                <th>Tahapan & Status</th>
                <th>Tanggal Selesai</th>
                <th>Durasi Pengerjaan</th>
                <th>Deadline & Kepatuhan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($progressHistory as $progress)
                <tr class="fi-ta-row">
                    <td class="petugas-name">
                        {{ $progress->assignee->name ?? 'N/A' }}
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; align-items: start;">
                            <div>
                                {{-- --- PERBAIKAN DI SINI --- --}}
                                {{-- Gunakan Str::headline untuk memformat string --}}
                                <span>{{ Str::headline($progress->stage_key ?? '-') }}</span>
                                
                                @if ($progress->status === 'done')
                                    <span class="badge badge-done">Selesai</span>
                                @else
                                    <span class="badge badge-pending">Dalam Proses</span>
                                @endif
                            </div>

                            @if (!empty($progress->notes))
                                <div class="progress-notes">
                                    {{ $progress->notes }}
                                </div>
                            @endif
                        </div>
                    </td>
                    <td>
                        {{ $progress->completed_at ? \Illuminate\Support\Carbon::parse($progress->completed_at)->translatedFormat('d F Y, H:i') : '-' }}
                    </td>
                    <td>
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
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <span>{{ $progress->deadline ? \Illuminate\Support\Carbon::parse($progress->deadline)->translatedFormat('d F Y, H:i') : 'Tidak diatur' }}</span>
                            @if ($progress->completed_at && $progress->deadline)
                                @if (\Illuminate\Support\Carbon::parse($progress->completed_at)->lte(\Illuminate\Support\Carbon::parse($progress->deadline)))
                                    <span style="margin-top: 0.25rem; font-size: 0.75rem; font-weight: 500; color: #16a34a;">✓ Tepat Waktu</span>
                                @else
                                    <span style="margin-top: 0.25rem; font-size: 0.75rem; font-weight: 500; color: #dc2626;">✗ Terlambat</span>
                                @endif
                            @elseif($progress->deadline && \Illuminate\Support\Carbon::now()->gt(\Illuminate\Support\Carbon::parse($progress->deadline)))
                                <span style="margin-top: 0.25rem; font-size: 0.75rem; font-weight: 500; color: #dc2626;">✗ Melewati Deadline</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="padding: 1rem 1.5rem; text-align: center;">
                        Belum ada riwayat pengerjaan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- 2. TAMPILAN MOBILE (Layout Kartu) --}}
<div class="progress-mobile">
    @forelse ($progressHistory as $progress)
        <div class="progress-mobile-card">
            <div class="label">Petugas</div>
            <div class="value">{{ $progress->assignee->name ?? 'N/A' }}</div>

            <div class="label">Tahapan & Status</div>
            <div class="value" style="display: flex; align-items: center;">
                {{-- --- PERBAIKAN DI SINI --- --}}
                <span>{{ Str::headline($progress->stage_key ?? '-') }}</span>
                
                @if ($progress->status === 'done')
                    <span class="badge badge-done">Selesai</span>
                @else
                    <span class="badge badge-pending">Dalam Proses</span>
                @endif
            </div>

            @if (!empty($progress->notes))
                <div class="label" style="margin-top: 0.75rem;">Catatan Petugas</div>
                <div class="value" style="white-space: normal; line-height: 1.5;">
                    {{ $progress->notes }}
                </div>
            @endif

            <div class="label" style="margin-top: 0.75rem;">Tanggal Selesai</div>
            <div class="value">{{ $progress->completed_at ? \Illuminate\Support\Carbon::parse($progress->completed_at)->translatedFormat('d F Y, H:i') : '-' }}</div>

            <div class="label">Durasi Pengerjaan</div>
            <div class="value">
                @php
                    $duration = 'N/A';
                    if ($progress->started_at && $progress->completed_at) {
                        $start = \Illuminate\Support\Carbon::parse($progress->started_at);
                        $end = \Illuminate\Support\Carbon::parse($progress->completed_at);
                        $duration = $start->diffForHumans($end, true);
                    }
                @endphp
                {{ $duration }}
            </div>
            
            <div class="label">Deadline & Kepatuhan</div>
            <div class="value">
                <span>{{ $progress->deadline ? \Illuminate\Support\Carbon::parse($progress->deadline)->translatedFormat('d F Y, H:i') : 'Tidak diatur' }}</span>
                @if ($progress->completed_at && $progress->deadline)
                    @if (\Illuminate\Support\Carbon::parse($progress->completed_at)->lte(\Illuminate\Support\Carbon::parse($progress->deadline)))
                        <span style="display: block; margin-top: 0.25rem; font-size: 0.75rem; font-weight: 500; color: #16a34a;">✓ Tepat Waktu</span>
                    @else
                        <span style="display: block; margin-top: 0.25rem; font-size: 0.75rem; font-weight: 500; color: #dc2626;">✗ Terlambat</span>
                    @endif
                @elseif($progress->deadline && \Illuminate\Support\Carbon::now()->gt(\Illuminate\Support\Carbon::parse($progress->deadline)))
                    <span style="display: block; margin-top: 0.25rem; font-size: 0.75rem; font-weight: 500; color: #dc2626;">✗ Melewati Deadline</span>
                @endif
            </div>
        </div>
    @empty
        <div style="padding: 1rem 1.5rem; text-align: center;" class="label">
            Belum ada riwayat pengerjaan.
        </div>
    @endforelse
</div>