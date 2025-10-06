<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('perbankan_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perbankan_id')->constrained('perbankans')->cascadeOnDelete();
            $table->string('path');
            $table->string('type')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('perbankan_files');
    }
};