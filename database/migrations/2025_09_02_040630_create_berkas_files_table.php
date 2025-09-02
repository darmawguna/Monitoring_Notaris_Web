<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('berkas_files', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel 'berkas', jika berkas dihapus, filenya juga ikut terhapus
            $table->foreignId('berkas_id')->constrained()->onDelete('cascade');

            // Kolom 'type' untuk menandai jenis file secara dinamis
            // Contoh isinya: 'ktp_suami', 'ktp_istri', 'kk', 'sertifikat'
            $table->string('type');

            // Metadata file yang penting
            $table->string('path'); // Path penyimpanan file
            $table->string('mime_type'); // Contoh: 'image/jpeg' atau 'application/pdf'
            $table->unsignedInteger('size'); // Ukuran file dalam bytes

            // Audit trail
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berkas_files');
    }
};
