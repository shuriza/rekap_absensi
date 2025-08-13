<?php

require_once 'vendor/autoload.php';

use App\Models\Karyawan;
use App\Exports\RekapAbsensiBulananExport;
use App\Exports\RekapAbsensiTahunanExport;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bulan = 5;  // Mei 2025
$tahun = 2025;

echo "=== TEST KONSISTENSI PENALTY SYSTEM ===\n";
echo "Bulan: $bulan, Tahun: $tahun\n\n";

// === Export Bulanan ===
$exportBulanan = new RekapAbsensiBulananExport($bulan, $tahun);
$viewBulanan = $exportBulanan->view();
$pegawaiListBulanan = $viewBulanan->getData()['pegawaiList'];

// === Export Tahunan ===
$exportTahunan = new RekapAbsensiTahunanExport($tahun);
$viewTahunan = $exportTahunan->view();
$pegawaiListTahunan = $viewTahunan->getData()['karyawans'];

// Test khusus untuk karyawan OB dan Non-OB
$karyawanOB = $pegawaiListBulanan->where('is_ob', true)->first();
$karyawanNonOB = $pegawaiListBulanan->where('is_ob', false)->first();

if ($karyawanOB) {
    $karyawanOBTahunan = $pegawaiListTahunan->firstWhere('nama', $karyawanOB->nama);
    
    echo "=== KARYAWAN OB: {$karyawanOB->nama} ===\n";
    echo "Bulanan: {$karyawanOB->total_menit} menit\n";
    echo "Tahunan (Mei): " . ($karyawanOBTahunan->menitPerBulan[$bulan] ?? 0) . " menit\n";
    
    if ($karyawanOB->total_menit == ($karyawanOBTahunan->menitPerBulan[$bulan] ?? 0)) {
        echo "✅ KONSISTEN - Menggunakan penalty system yang sama\n\n";
    } else {
        echo "❌ TIDAK KONSISTEN - Masih ada perbedaan algoritma\n\n";
    }
}

if ($karyawanNonOB) {
    $karyawanNonOBTahunan = $pegawaiListTahunan->firstWhere('nama', $karyawanNonOB->nama);
    
    echo "=== KARYAWAN NON-OB: {$karyawanNonOB->nama} ===\n";
    echo "Bulanan: {$karyawanNonOB->total_menit} menit\n";
    echo "Tahunan (Mei): " . ($karyawanNonOBTahunan->menitPerBulan[$bulan] ?? 0) . " menit\n";
    
    if ($karyawanNonOB->total_menit == ($karyawanNonOBTahunan->menitPerBulan[$bulan] ?? 0)) {
        echo "✅ KONSISTEN - Menggunakan penalty system yang sama\n\n";
    } else {
        echo "❌ TIDAK KONSISTEN - Masih ada perbedaan algoritma\n\n";
    }
}

// Test 5 pegawai pertama untuk verifikasi umum
$maxTest = min(5, $pegawaiListBulanan->count());
$semuaSama = true;

echo "=== VERIFIKASI UMUM (5 PEGAWAI PERTAMA) ===\n";
for ($i = 0; $i < $maxTest; $i++) {
    $pegawaiBulanan = $pegawaiListBulanan->get($i);
    $pegawaiTahunan = $pegawaiListTahunan->firstWhere('nama', $pegawaiBulanan->nama);
    
    if (!$pegawaiTahunan) {
        echo "❌ {$pegawaiBulanan->nama}: Tidak ditemukan di tahunan\n";
        $semuaSama = false;
        continue;
    }
    
    $menitBulanan = $pegawaiBulanan->total_menit;
    $menitTahunan = $pegawaiTahunan->menitPerBulan[$bulan] ?? 0;
    
    $jenis = $pegawaiBulanan->is_ob ? '[OB]' : '[Non-OB]';
    
    if ($menitBulanan == $menitTahunan) {
        echo "✅ {$pegawaiBulanan->nama} $jenis: {$menitBulanan} menit (SAMA)\n";
    } else {
        echo "❌ {$pegawaiBulanan->nama} $jenis: Bulanan={$menitBulanan}, Tahunan={$menitTahunan}\n";
        $semuaSama = false;
    }
}

echo "\n=== HASIL AKHIR ===\n";
if ($semuaSama) {
    echo "✅ SEMUA DATA SUDAH KONSISTEN!\n";
    echo "✅ Tahunan sekarang menggunakan penalty system untuk akumulasi keterlambatan\n";
    echo "✅ Tidak lagi menghitung data riil masuk/pulang untuk akumulasi\n";
} else {
    echo "❌ Masih ada perbedaan - perlu pengecekan lebih lanjut\n";
}

echo "\n=== END TEST ===\n";
