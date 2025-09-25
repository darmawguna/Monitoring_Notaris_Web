<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('turun_waris_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turun_waris_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turun_waris_files');
    }
};