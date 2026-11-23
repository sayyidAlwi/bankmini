<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubah data inti nasabah menjadi:
     * ACCOUNT NUMBER (rekening.no_rekening / NISN), CUSTOMER, GENERATION, STUDY PROGRAM.
     */
    public function up(): void
    {
        Schema::table('nasabah', function (Blueprint $table) {
            $table->dropUnique(['kode_nasabah']);
        });

        Schema::table('nasabah', function (Blueprint $table) {
            $table->dropColumn(['kode_nasabah', 'alamat', 'no_hp']);
        });

        Schema::table('nasabah', function (Blueprint $table) {
            $table->renameColumn('kelas', 'angkatan');
        });

        Schema::table('nasabah', function (Blueprint $table) {
            $table->string('program_studi', 100)->default('')->after('nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nasabah', function (Blueprint $table) {
            $table->dropColumn('program_studi');
        });

        Schema::table('nasabah', function (Blueprint $table) {
            $table->renameColumn('angkatan', 'kelas');
        });

        Schema::table('nasabah', function (Blueprint $table) {
            $table->string('kode_nasabah', 30)->nullable()->unique()->after('id');
            $table->text('alamat')->nullable();
            $table->string('no_hp', 20)->nullable();
        });
    }
};
