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
            $table->index('current_stage_key');
            $table->index('current_assignee_id');
        });

        // Indeks untuk tabel 'progress'
        Schema::table('progress', function (Blueprint $table) {
            $table->index('berkas_id');
            $table->index('assignee_id');
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
            $table->dropIndex(['current_stage_key']);
            $table->dropIndex(['current_assignee_id']);
        });

        Schema::table('progress', function (Blueprint $table) {
            $table->dropIndex(['berkas_id']);
            $table->dropIndex(['assignee_id']);
        });
    }
};