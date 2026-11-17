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
            $table->enum('role', ['admin', 'guru', 'teller', 'petugas', 'siswa'])
                ->default('admin')
                ->change();
        });

        DB::table('users')->whereIn('role', ['guru', 'teller'])->update(['role' => 'petugas']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'petugas', 'siswa'])
                ->default('siswa')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'guru', 'teller', 'petugas', 'siswa'])
                ->default('admin')
                ->change();
        });

        DB::table('users')->where('role', 'petugas')->update(['role' => 'teller']);
        DB::table('users')->where('role', 'siswa')->update(['role' => 'guru']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'guru', 'teller'])
                ->default('teller')
                ->change();
        });
    }
};
