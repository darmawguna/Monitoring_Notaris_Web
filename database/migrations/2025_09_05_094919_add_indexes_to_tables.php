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
        // Indeks untuk tabel 'berkas'
        Schema::table('berkas', function (Blueprint $table) {
            $table->index('nama_berkas');
            $table->index('penjual');
            $table->index('pembeli');
            $table->index('status_overall');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('berkas', function (Blueprint $table) {
            $table->dropIndex(['nama_berkas']);
            $table->dropIndex(['penjual']);
            $table->dropIndex(['pembeli']);
            $table->dropIndex(['status_overall']);
        });
    }
};