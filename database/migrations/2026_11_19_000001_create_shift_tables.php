<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shift mingguan teller: nama diketik sendiri saat mulai, ditutup saat selesai.
     */
    public function up(): void
    {
        Schema::create('shift', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama', 100);
            $table->date('tanggal_mulai')->unique();
            $table->date('tanggal_selesai');
            $table->dateTime('mulai_at');
            $table->dateTime('selesai_at')->nullable();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index('tanggal_selesai');
            $table->index('selesai_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift');
    }
};
