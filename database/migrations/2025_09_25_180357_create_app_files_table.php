<?php
// database/migrations/..._create_app_files_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_files', function (Blueprint $table) {
            $table->id();
            $table->morphs('fileable'); // Membuat kolom fileable_id dan fileable_type
            $table->string('path');
            $table->string('type')->nullable(); // Jenis file (KTP, KK, dll)
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('app_files');
    }
};