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
        Schema::dropIfExists('app_files');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('app_files', function (Blueprint $table) {
            $table->id();
            $table->morphs('fileable');
            $table->string('type')->nullable();
            $table->string('path');
            $table->timestamps();
        });
    }
};
