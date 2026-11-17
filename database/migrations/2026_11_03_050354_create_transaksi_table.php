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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekening_id')->constrained('rekening');
            $table->foreignId('user_id')->constrained('users');
            $table->string('kode_transaksi', 40)->unique();
            $table->enum('jenis', ['setoran', 'penarikan']);
            $table->decimal('nominal', 15, 2);
            $table->dateTime('tanggal');
            $table->enum('status', ['berhasil', 'batal']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
