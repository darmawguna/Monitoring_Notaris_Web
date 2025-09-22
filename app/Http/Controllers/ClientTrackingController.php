<?php

namespace App\Http\Controllers;

use App\Models\Berkas;
use Illuminate\Http\Request;

class ClientTrackingController extends Controller
{
    public function index(Request $request)
    {
        $nomorBerkas = $request->input('nomor_berkas');
        $berkas = null;
        $error = null;

        if ($nomorBerkas) {
            // Lakukan pencarian dengan eager loading untuk efisiensi
            $berkas = Berkas::where('nomor_berkas', $nomorBerkas)
                ->with([
                    'progress' => function ($query) {
                        $query->orderBy('created_at', 'asc');
                    },
                    'progress.assignee'
                ])
                ->first();

            if (!$berkas) {
                $error = 'Nomor berkas tidak ditemukan. Pastikan Anda memasukkan nomor yang benar.';
            }
        }

        return view('client-tracking', [
            'berkas' => $berkas,
            'error' => $error,
            'searched_nomor' => $nomorBerkas,
        ]);
    }
}