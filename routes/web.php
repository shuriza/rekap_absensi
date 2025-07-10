<?php

use App\Exports\RekapAbsensiExport as ExportsRekapAbsensiExport;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\IzinPresensiController;
use App\Http\Controllers\ExportRekapController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');
Route::get('/absensi/upload', [AbsensiController::class, 'formUpload'])->name('absensi.upload');
Route::post('/absensi/import', [AbsensiController::class, 'import'])->name('absensi.import');
Route::post('/absensi/preview', [AbsensiController::class, 'preview'])->name('absensi.preview');
Route::get('/absensi/preview', [AbsensiController::class, 'preview'])->name('absensi.preview');
Route::post('/absensi/store', [AbsensiController::class, 'store'])->name('absensi.store');

Route::get('/absensi/cetak', [AbsensiController::class, 'cetak'])->name('absensi.cetak');

Route::get('/absensi/rekap', [RekapController::class, 'rekap'])->name('absensi.rekap');

Route::get('izin-presensi', [IzinPresensiController::class,'index'])->name('izin_presensi.index');
Route::get('izin-presensi/new', [IzinPresensiController::class,'create'])->name('izin_presensi.create');
Route::post('izin-presensi', [IzinPresensiController::class,'store'])->name('izin_presensi.store');

// AJAX search karyawan
Route::get('/rekap/export-bulanan', [ExportRekapController::class, 'exportBulanan'])->name('rekap.export.bulanan');
Route::get('/rekap/export-tahunan', [ExportRekapController::class, 'exportTahunan'])->name('rekap.export.tahunan');

Route::resource('izin_presensi', IzinPresensiController::class);
// AJAX untuk Tom Select
Route::get('/ajax/karyawan', [IzinPresensiController::class, 'searchKaryawan'])
     ->name('karyawan.search');
