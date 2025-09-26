<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Hapus kolom usang dari tabel berkas
        if (Schema::hasColumn('berkas', 'current_assignee_id')) {
            Schema::table('berkas', function (Blueprint $table) {
                $table->dropForeign(['current_assignee_id']); // Hapus constraint dulu
                $table->dropColumn('current_assignee_id');
            });
        }
        if (Schema::hasColumn('berkas', 'deadline_at')) {
            Schema::table('berkas', function (Blueprint $table) {
                $table->dropColumn('deadline_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('berkas', function (Blueprint $table) {
            $table->foreignId('current_assignee_id')->nullable()->constrained('users');
            $table->timestamp('deadline_at')->nullable();
        });
    }
};