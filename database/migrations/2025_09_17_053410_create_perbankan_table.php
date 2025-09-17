<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('perbankans', function (Blueprint $table) {
            $table->id();

            // Section Debitur
            $table->string('tipe_pemohon')->nullable();
            $table->string('nik')->nullable();
            $table->string('nama_debitur')->nullable();
            $table->text('alamat_debitur')->nullable();
            $table->string('ttl_tempat')->nullable();
            $table->date('ttl_tanggal')->nullable();
            $table->string('npwp')->nullable();
            $table->string('email')->nullable();
            $table->string('telepon')->nullable();

            // Section Covernote / SKMHT
            $table->string('berkas_bank')->nullable(); // Path untuk file upload
            $table->integer('jangka_waktu')->nullable(); // Dalam bulan (1, 3, 6)
            $table->date('tanggal_covernote')->nullable(); // Tanggal awal covernote

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perbankans');
    }
};