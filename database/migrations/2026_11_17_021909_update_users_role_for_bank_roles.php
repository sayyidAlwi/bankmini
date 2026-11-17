<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Widen the enum first so legacy values can be migrated safely.
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'guru', 'teller', 'petugas', 'siswa', 'supervisor', 'cs', 'akuntansi'])
                ->default('admin')
                ->change();
        });

        DB::table('users')->where('role', 'guru')->update(['role' => 'supervisor']);
        DB::table('users')->where('role', 'petugas')->update(['role' => 'teller']);
        DB::table('users')->where('role', 'siswa')->update(['role' => 'cs']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'supervisor', 'teller', 'cs', 'akuntansi'])
                ->default('cs')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'guru', 'teller', 'petugas', 'siswa', 'supervisor', 'cs', 'akuntansi'])
                ->default('admin')
                ->change();
        });

        DB::table('users')->whereIn('role', ['supervisor', 'teller', 'akuntansi'])->update(['role' => 'petugas']);
        DB::table('users')->where('role', 'cs')->update(['role' => 'siswa']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'petugas', 'siswa'])
                ->default('petugas')
                ->change();
        });
    }
};
