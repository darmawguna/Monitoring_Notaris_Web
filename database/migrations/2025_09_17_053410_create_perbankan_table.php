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
            $table->string('tipe_pemohon')->nullable();
            $table->string('nik')->nullable();
            $table->string('nama_debitur')->nullable();
            $table->text('alamat_debitur')->nullable();
            $table->string('ttl_tempat')->nullable();
            $table->date('ttl_tanggal')->nullable();
            $table->string('npwp')->nullable();
            $table->string('email')->nullable();
            $table->string('telepon')->nullable();
            $table->string('nomor_pk')->nullable();
            $table->string('nama_kreditur')->nullable();
            $table->integer('jangka_waktu')->nullable(); // Dalam bulan (1, 3, 6)
            $table->date('tanggal_covernote')->nullable(); // Tanggal awal covernote
            $table->string('status_overall')->nullable();
            $table->string('current_stage_key')->nullable();
            // Tambahkan kolom created_by sebagai foreign key ke tabel users
            $table->foreignId('created_by')->nullable()->constrained('users');
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