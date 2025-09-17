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
        Schema::create('berkas', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->string('nama_berkas');

            // Data penting untuk identifikasi & pencarian
            $table->string('penjual');
            $table->string('pembeli');
            $table->string('sertifikat_nama')->nullable();
            $table->text('persetujuan')->nullable();

            // Data finansial & status
            // TODO perbarui skema kwitansi apakah nantinya akan menggunakan field total_cost atau menggunakan nilai transaksi
            $table->decimal('total_cost', 14, 2)->nullable();
            $table->decimal('total_paid', 14, 2)->default(0.00);
            $table->string('status_overall');
            $table->string('current_stage_key');

            // Foreign keys & tracking
            $table->foreignId('current_assignee_id')->nullable()->constrained('users');
            $table->dateTime('deadline_at')->nullable();
            // $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berkas');
    }
};
