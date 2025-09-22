<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Progres Berkas - NotarisFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <div class="container mx-auto p-4 md:p-8 max-w-2xl">
        
        <header class="text-center mb-8">
            <img src="{{ asset('images/logo.svg') }}" alt="Logo NotarisFlow" class="h-12 mx-auto mb-4">
            <h1 class="text-3xl font-bold text-gray-900">Lacak Progres Berkas Anda</h1>
            <p class="text-gray-600 mt-2">Masukkan nomor berkas Anda di bawah ini untuk melihat status terkini.</p>
        </header>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <form action="{{ route('client.tracking') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                <input type="text" name="nomor_berkas" placeholder="Contoh: 2025-001" value="{{ $searched_nomor ?? '' }}" required class="flex-grow w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-amber-500 focus:border-amber-500 transition">
                <button type="submit" class="bg-amber-600 text-white px-6 py-2 rounded-md hover:bg-amber-700 transition font-semibold">
                    Lacak
                </button>
            </form>
        </div>

        @if ($berkas)
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="border-b pb-4 mb-4">
                    <h2 class="text-xl font-bold">Hasil untuk: {{ $berkas->nomor_berkas }}</h2>
                    <p class="text-gray-600"><strong>Nama Berkas:</strong> {{ $berkas->nama_berkas }}</p>
                </div>

                {{-- --- INI BAGIAN YANG DIPERBARUI SECARA TOTAL --- --}}
                @php
                    // Definisikan label yang lebih ramah untuk klien
                    $stage_labels = [
                        'front_office' => 'Penerimaan Berkas',
                        'petugas_2' => 'Verifikasi Internal',
                        'pajak' => 'Proses Pajak',
                        'petugas_5' => 'Proses Finalisasi',
                        'selesai' => 'Selesai',
                    ];
                @endphp

                @if ($berkas->status_overall->value === 'selesai')
                    {{-- Tampilan jika berkas SUDAH SELESAI --}}
                    <div class="flex items-center p-4 bg-green-50 text-green-800 rounded-lg">
                        <svg class="h-8 w-8 mr-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h3 class="font-bold text-lg">Proses Telah Selesai</h3>
                            <p class="text-sm">Berkas Anda telah menyelesaikan semua tahapan proses.</p>
                        </div>
                    </div>
                @else
                    {{-- Tampilan jika berkas MASIH DALAM PROSES --}}
                    <div class="flex items-center p-4 bg-amber-50 text-amber-800 rounded-lg">
                         {{-- <svg class="h-8 w-8 mr-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg> --}}
                        <div>
                            <h3 class="font-bold text-lg">Sedang Dalam Proses</h3>
                            <p class="text-sm">
                                Status saat ini: <strong>{{ $stage_labels[$berkas->current_stage_key->value] ?? 'Verifikasi' }}</strong>
                            </p>
                        </div>
                    </div>
                @endif
                 {{-- --- AKHIR DARI PERUBAHAN --- --}}
            </div>
        @elseif ($error)
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg shadow-md" role="alert">
                <strong class="font-bold">Gagal!</strong>
                <span class="block sm:inline">{{ $error }}</span>
            </div>
        @endif
        
        <footer class="text-center mt-12 text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} NotarisFlow. Diberdayakan oleh Kantor Notaris Komang Hendy Prabawa.</p>
        </footer>
    </div>

</body>
</html>