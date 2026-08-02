<?php

use App\Http\Controllers\LaporanBulananController;
use App\Http\Controllers\LaporanRambuController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\PetaExportController;
use App\Http\Controllers\SuratPengantarController;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\JenisRambu\Index;
use App\Livewire\Admin\Laporan\RambuExport;
use App\Livewire\Admin\Spk\Create;
use App\Livewire\Admin\Spk\Riwayat;
use App\Livewire\Admin\Users\Edit;
use App\Livewire\Notifikasi;
use App\Livewire\Peta;
use App\Livewire\Rambu\Show;
use App\Livewire\User\Kendala;
use App\Livewire\User\Laporan;
use App\Livewire\User\RiwayatSpk;
use App\Livewire\User\SpkDikerjakan;
use App\Livewire\User\Temuan;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('admin/dashboard', Dashboard::class)->name('admin.dashboard')->middleware('role:admin');

    Route::get('spk/{spk}/surat-pengantar', [SuratPengantarController::class, 'show'])->name('spk.surat-pengantar');

    Route::livewire('peta', Peta::class)->name('peta');
    Route::get('peta/data', [PetaController::class, 'data'])->name('peta.data');
    Route::match(['get', 'post'], 'peta/export', [PetaExportController::class, 'export'])->name('peta.export');
    Route::livewire('rambu/{rambu}', Show::class)->name('rambu.show');
    Route::livewire('jenis-rambu', Index::class)->name('jenis-rambu.index');
    Route::livewire('rambu', App\Livewire\Admin\Rambu\Index::class)->name('rambu.index');

    Route::livewire('notifikasi', Notifikasi::class)->name('notifikasi');
    Route::livewire('audit-log', 'audit-log')->name('audit-log');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('spk', App\Livewire\Admin\Spk\Index::class)->name('spk.index');
        Route::livewire('spk/create', Create::class)->name('spk.create');
        Route::livewire('spk/{spk}', App\Livewire\Admin\Spk\Show::class)->name('spk.show');
        Route::livewire('spk/{spk}/edit', App\Livewire\Admin\Spk\Edit::class)->name('spk.edit');
        Route::livewire('spk-riwayat', Riwayat::class)->name('spk.riwayat');
        Route::livewire('validasi', App\Livewire\Admin\Validasi\Index::class)->name('validasi.index');
        Route::livewire('validasi/{spk}', App\Livewire\Admin\Validasi\Show::class)->name('validasi.show');
        Route::livewire('temuan', App\Livewire\Admin\Temuan\Index::class)->name('temuan.index');
        Route::livewire('laporan', App\Livewire\Admin\Laporan\Index::class)->name('laporan.index');
        Route::get('laporan/export', [LaporanBulananController::class, 'export'])->name('laporan.export');
        Route::livewire('laporan/rambu', RambuExport::class)->name('laporan.rambu');
        Route::get('laporan/rambu/export', [LaporanRambuController::class, 'export'])->name('laporan.rambu-export');
        Route::livewire('users', App\Livewire\Admin\Users\Index::class)->name('users.index');
        Route::livewire('users/create', App\Livewire\Admin\Users\Create::class)->name('users.create');
        Route::livewire('users/{user}/edit', Edit::class)->name('users.edit');
        Route::livewire('jenis-rambu', Index::class)->name('jenis-rambu.index');
        Route::livewire('rambu', App\Livewire\Admin\Rambu\Index::class)->name('rambu.index');
        Route::livewire('system-error-log', App\Livewire\Admin\SystemErrorLog\Index::class)->name('system-error-log.index');
    });

    Route::middleware('role:user')->group(function () {
        Route::livewire('dashboard', App\Livewire\User\Dashboard::class)->name('dashboard');
        Route::livewire('spk/{spk}', App\Livewire\User\Spk\Show::class)->name('user.spk.show');
        Route::livewire('spk-dikerjakan', SpkDikerjakan::class)->name('user.spk-dikerjakan');
        Route::livewire('riwayat-spk', RiwayatSpk::class)->name('user.riwayat-spk');
        Route::livewire('kendala', Kendala::class)->name('user.kendala');
        Route::livewire('laporan', Laporan::class)->name('user.laporan');
        Route::livewire('temuan', Temuan::class)->name('user.temuan');
    });
});

require __DIR__.'/settings.php';
