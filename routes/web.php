<?php

use App\Http\Controllers\BerkasFileController;
use App\Http\Controllers\KwitansiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/berkas-files/{berkasFile}/download', [BerkasFileController::class, 'download'])
    ->name('berkas-files.download')
    ->middleware('auth');

Route::get('/kwitansi/{receipt}/download', [KwitansiController::class, 'download'])
    ->name('kwitansi.download')
    ->middleware('auth');


use App\Http\Controllers\ClientTrackingController;
// ... (use statements lain)

// ... (rute lain yang sudah ada)

// Rute untuk Halaman Pelacakan Klien
Route::get('/lacak', [ClientTrackingController::class, 'index'])->name('client.tracking');