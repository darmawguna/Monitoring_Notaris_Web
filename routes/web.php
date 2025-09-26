<?php

use App\Http\Controllers\BerkasFileController;
use App\Http\Controllers\KwitansiController;
use App\Http\Controllers\TandaTerimaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/files/{appFile}/download', [FileController::class, 'download'])
    ->name('files.download')
    ->middleware('auth');

Route::get('/berkas-files/{berkasFile}/download', [BerkasFileController::class, 'download'])
    ->name('berkas-files.download')
    ->middleware('auth');

Route::get('/kwitansi/{receipt}/download', [KwitansiController::class, 'download'])
    ->name('kwitansi.download')
    ->middleware('auth');
Route::get('/SerahTerima/{tandaTerimaSertifikat}/download', [TandaTerimaController::class, 'download'])
    ->name('tandaSerahTerima.download')
    ->middleware('auth');


use App\Http\Controllers\ClientTrackingController;
// ... (use statements lain)

// ... (rute lain yang sudah ada)

// Rute untuk Halaman Pelacakan Klien
Route::get('/lacak', [ClientTrackingController::class, 'index'])->name('client.tracking');