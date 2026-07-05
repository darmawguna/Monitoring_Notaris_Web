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
        Schema::table('berkas', function (Blueprint $table) {
            $table->dropColumn(['total_cost', 'total_paid', 'deadline_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('berkas', function (Blueprint $table) {
            $table->decimal('total_cost', 14, 2)->nullable();
            $table->decimal('total_paid', 14, 2)->default(0.00);
            $table->dateTime('deadline_at')->nullable();
        });
    }
};
