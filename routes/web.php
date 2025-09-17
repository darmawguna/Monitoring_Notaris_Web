<?php

use App\Http\Controllers\BerkasFileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/berkas-files/{berkasFile}/download', [BerkasFileController::class, 'download'])
    ->name('berkas-files.download')
    ->middleware('auth');