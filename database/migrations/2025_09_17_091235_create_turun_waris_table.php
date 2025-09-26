<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('turun_waris', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kasus');
            $table->foreignId('created_by')->constrained('users');
            $table->string('status_overall')->nullable();
            $table->string('current_stage_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turun_waris');
    }
};