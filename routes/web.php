<?php

use App\Exports\RekapAbsensiExport as ExportsRekapAbsensiExport;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\IzinPresensiController;
use App\Http\Controllers\ExportRekapController;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\KaryawanController;

// Route login dan register (tanpa middleware auth)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::middleware('auth')->group(function () {

 Route::get('/', function () {
     return view('absensi.index');
 });

 Route::post('/ganti-password', [ChangePasswordController::class, 'update'])->name('password.change');


Route::get('/karyawan', [KaryawanController::class, 'index'])->name('absensi.karyawan');
Route::post('/karyawan/{id}/nonaktif', [KaryawanController::class, 'nonaktifkan'])->name('karyawan.nonaktif');
Route::post('/karyawan/{id}/aktifkan', [KaryawanController::class, 'aktifkan'])->name('karyawan.aktifkan');

Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');
Route::get('/absensi/upload', [AbsensiController::class, 'formUpload'])->name('absensi.upload');
Route::post('/absensi/import', [AbsensiController::class, 'import'])->name('absensi.import');
Route::post('/absensi/preview', [AbsensiController::class, 'preview'])->name('absensi.preview');
Route::get('/absensi/preview', [AbsensiController::class, 'preview'])->name('absensi.preview');
Route::post('/absensi/store', [AbsensiController::class, 'store'])->name('absensi.store');

Route::get('/absensi/cetak', [AbsensiController::class, 'cetak'])->name('absensi.cetak');

Route::get('/absensi/rekap', [RekapController::class, 'rekap'])->name('absensi.rekap');
Route::get('/absensi/rekap-tahunan', [RekapController::class, 'rekapTahunan'])->name('absensi.rekap.tahunan');

Route::get('izin-presensi', [IzinPresensiController::class,'index'])->name('izin_presensi.index');
Route::get('izin-presensi/new', [IzinPresensiController::class,'create'])->name('izin_presensi.create');
Route::post('izin-presensi', [IzinPresensiController::class,'store'])->name('izin_presensi.store');

// AJAX search karyawan
Route::get('/rekap/export-bulanan', [ExportRekapController::class, 'exportBulanan'])->name('rekap.export.bulanan');
Route::get('/rekap/export-tahunan', [ExportRekapController::class, 'exportTahunan'])->name('rekap.export.tahunan');

Route::resource('izin_presensi', IzinPresensiController::class);
Route::get('/izin/{karyawan}/{tgl}', [IzinPresensiController::class, 'byDate'])
     ->where('tgl', '\\d{4}-\\d{2}-\\d{2}')
     ->name('izin.byDate');
// AJAX untuk Tom Select
Route::get('/ajax/karyawan', [IzinPresensiController::class, 'searchKaryawan'])
     ->name('karyawan.search');

Route::get('/izin-presensi/{izin}/lampiran',
    [IzinPresensiController::class, 'lampiran'])->name('izin_presensi.lampiran');

Route::post('/rekap/holiday', [RekapController::class, 'storeHoliday'])
     ->name('rekap.holiday.add');
Route::delete('/rekap/holiday/{id}',   [RekapController::class, 'destroyHoliday'])
     ->name('rekap.holiday.del');

Route::get('/export/izin-bulanan', [ExportRekapController::class, 'exportIzinBulanan'])
     ->name('export.izin.bulanan');
      });