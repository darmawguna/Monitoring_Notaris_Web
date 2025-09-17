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
        Schema::table('berkas', function (Blueprint $table) {
            // Hapus kolom lama yang tidak relevan lagi
            $table->dropColumn(['penjual', 'pembeli', 'sertifikat_nama', 'persetujuan']);

            // Ganti nama kolom 'nomor' menjadi 'nomor_berkas'
            $table->renameColumn('nomor', 'nomor_berkas');

            // Tambahkan kolom baru setelah 'nama_berkas'
            $table->string('nama_pemohon')->after('nama_berkas')->nullable();

            // Tambahkan kolom JSON untuk data jual beli yang kompleks
            $table->json('penjual_data')->after('nama_pemohon')->nullable();
            $table->json('pembeli_data')->after('penjual_data')->nullable();
            $table->json('pihak_persetujuan_data')->after('pembeli_data')->nullable();

            // Tambahkan kolom untuk Section Sertifikat
            $table->string('sertifikat_nomor')->after('pihak_persetujuan_data')->nullable();
            $table->string('sertifikat_luas')->after('sertifikat_nomor')->nullable();
            $table->string('sertifikat_jenis')->after('sertifikat_luas')->nullable();  // buat jadi dropdown elektronik/analog
            $table->string('sertifikat_tipe')->after('sertifikat_jenis')->nullable(); // buat jadi dropdown Sertifikat Hak Milik (SHM), Sertifikat Hak Guna Bangunan (SHGB), Sertifikat Hak Guna Usaha (SHGU), dan Sertifikat Hak Pakai (SHP).
            $table->decimal('nilai_transaksi', 15, 2)->after('sertifikat_tipe')->nullable();
           

            // Tambahkan kolom untuk Section PBB
            $table->string('pbb_sppt')->after('nilai_transaksi')->nullable();
            $table->string('pbb_nop')->after('pbb_sppt')->nullable();
            $table->string('pbb_validasi')->after('pbb_nop')->nullable();
            $table->string('pbb_akta_bpjb')->after('pbb_validasi')->nullable();
            $table->string('pbb_nomor')->after('pbb_akta_bpjb')->nullable();

            // Tambahkan kolom untuk Section Bank
            $table->string('bank_kredit')->after('pbb_nomor')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('berkas', function (Blueprint $table) {
            // Kembalikan perubahan jika migrasi di-rollback
            $table->string('penjual');
            $table->string('pembeli');
            $table->string('sertifikat_nama');
            $table->text('persetujuan');

            $table->renameColumn('nomor_berkas', 'nomor');

            $table->dropColumn([
                'nama_pemohon',
                'penjual_data',
                'pembeli_data',
                'pihak_persetujuan_data',
                'sertifikat_nomor',
                'sertifikat_luas',
                'sertifikat_jenis',
                'sertifikat_tipe',
                'nilai_transaksi',
                'pbb_sppt',
                'pbb_nop',
                'pbb_validasi',
                'pbb_akta_bpjb',
                'pbb_nomor',
                'bank_kredit',
            ]);
        });
    }
};