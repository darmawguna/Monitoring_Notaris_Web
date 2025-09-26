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
        Schema::create('tanda_terima_sertifikats', function (Blueprint $table) {
            $table->id();
            $table->string('penyerah'); // Yang menyerahkan sertifikat
            $table->string('penerima'); // Yang menerima sertifikat
            $table->date('tanggal_terima');
            $table->date('tanggal_menyerahkan');
            $table->string('sertifikat_info'); // Info sertifikat hak milik
            $table->string("informasi_tambahan");
            $table->string('dokumen_akhir_path')->nullable(); // Path untuk file upload
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tanda_terima_sertifikats');
    }
};