<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('progress', function (Blueprint $table) {
            // Hapus foreign key lama yang spesifik untuk Berkas
            $table->dropForeign(['berkas_id']);
            $table->dropColumn('berkas_id');

            // Tambahkan kolom polimorfik 'progressable' setelah kolom 'id'
            // Ini akan membuat kolom 'progressable_type' dan 'progressable_id'
            $table->morphs('progressable', 'progressable_index');
        });
    }

    public function down(): void
    {
        Schema::table('progress', function (Blueprint $table) {
            // Logika untuk mengembalikan jika migrasi di-rollback
            $table->dropMorphs('progressable', 'progressable_index');
            $table->foreignId('berkas_id')->constrained('berkas')->cascadeOnDelete();
        });
    }
};