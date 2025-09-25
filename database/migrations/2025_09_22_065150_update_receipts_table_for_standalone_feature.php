<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            // Buat foreign key menjadi nullable agar tidak wajib terikat ke berkas
            $table->foreignId('berkas_id')->nullable()->change();

            // Tambahkan kolom baru
            $table->string('nama_pemohon_kwitansi')->after('receipt_number')->nullable();
            $table->text('notes_kwitansi')->nullable()->after('detail_biaya');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('berkas_id')->nullable(false)->change();
            $table->dropColumn(['nama_pemohon_kwitansi', 'notes_kwitansi']);
        });
    }
};