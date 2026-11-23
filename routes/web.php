<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImporNasabahController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NasabahController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $route = auth()->user()?->dashboardRouteName();

    return $route
        ? redirect()->route($route)
        : redirect()->route('login');
})->name('beranda');

// Staff accounts are managed by the admin, so self-registration and
// email-based password resets are disabled.
Auth::routes(['register' => false, 'reset' => false]);

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Dashboards
    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::get('/supervisor/dashboard', [DashboardController::class, 'supervisor'])
        ->middleware('role:supervisor')
        ->name('supervisor.dashboard');

    Route::get('/teller/dashboard', [DashboardController::class, 'teller'])
        ->middleware('role:teller')
        ->name('teller.dashboard');

    Route::get('/cs/dashboard', [DashboardController::class, 'cs'])
        ->middleware('role:cs')
        ->name('cs.dashboard');

    Route::get('/akuntansi/dashboard', [DashboardController::class, 'akuntansi'])
        ->middleware('role:akuntansi')
        ->name('akuntansi.dashboard');

    // User management (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('admin/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');

        // Import nasabah and opening balances from Excel.
        Route::get('admin/impor', [ImporNasabahController::class, 'create'])->name('admin.impor.create');
        Route::get('admin/impor/template', [ImporNasabahController::class, 'template'])->name('admin.impor.template');
        Route::post('admin/impor/preview', [ImporNasabahController::class, 'preview'])->name('admin.impor.preview');
        Route::post('admin/impor', [ImporNasabahController::class, 'store'])->name('admin.impor.store');
        Route::post('admin/impor/batal', [ImporNasabahController::class, 'cancel'])->name('admin.impor.cancel');
    });

    // Nasabah and rekening
    Route::get('nasabah', [NasabahController::class, 'index'])
        ->middleware('role:admin,cs,teller')
        ->name('nasabah.index');

    Route::get('nasabah/create', [NasabahController::class, 'create'])
        ->middleware('role:admin,cs')
        ->name('nasabah.create');

    Route::post('nasabah', [NasabahController::class, 'store'])
        ->middleware('role:admin,cs')
        ->name('nasabah.store');

    Route::get('nasabah/{nasabah}', [NasabahController::class, 'show'])
        ->middleware('role:admin,cs,teller')
        ->name('nasabah.show');

    Route::get('nasabah/{nasabah}/edit', [NasabahController::class, 'edit'])
        ->middleware('role:admin,cs')
        ->name('nasabah.edit');

    Route::put('nasabah/{nasabah}', [NasabahController::class, 'update'])
        ->middleware('role:admin,cs')
        ->name('nasabah.update');

    // Transactions: supervisor may review, teller and admin may record.
    Route::get('transaksi', [TransaksiController::class, 'index'])
        ->middleware('role:admin,teller,supervisor')
        ->name('transaksi.index');

    Route::middleware('role:admin,teller')->group(function () {
        Route::get('transaksi/create', [TransaksiController::class, 'create'])->name('transaksi.create');
        Route::post('transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
    });

    // Shift report: admin and supervisor may review every weekly shift.
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('shift', [ShiftController::class, 'index'])->name('shift.index');
        Route::get('shift/{shift}', [ShiftController::class, 'show'])
            ->whereNumber('shift')
            ->name('shift.show');
    });

    // Teller starts a weekly shift by typing their own name.
    Route::middleware('role:teller')->group(function () {
        Route::get('shift-saya', [ShiftController::class, 'saya'])->name('shift.saya');
        Route::post('shift-saya/mulai', [ShiftController::class, 'mulai'])->name('shift.mulai');
        Route::post('shift-saya/selesai', [ShiftController::class, 'selesai'])->name('shift.selesai');
    });

    // Admin may close a forgotten shift or remove a record.
    Route::middleware('role:admin')->group(function () {
        Route::post('shift/{shift}/tutup', [ShiftController::class, 'tutup'])
            ->whereNumber('shift')
            ->name('shift.tutup');
        Route::delete('shift/{shift}', [ShiftController::class, 'destroy'])
            ->whereNumber('shift')
            ->name('shift.destroy');
    });

    // Financial reports (admin, supervisor, accounting)
    Route::middleware('role:admin,supervisor,akuntansi')
        ->prefix('laporan')
        ->name('laporan.')
        ->group(function () {
            Route::get('kas', [LaporanController::class, 'kas'])->name('kas');
            Route::get('simpanan', [LaporanController::class, 'simpanan'])->name('simpanan');
            Route::get('jurnal', [LaporanController::class, 'jurnal'])->name('jurnal');
            Route::get('buku-besar', [LaporanController::class, 'bukuBesar'])->name('buku-besar');
        });
});
