<?php

require_once 'vendor/autoload.php';

use App\Models\Karyawan;
use App\Exports\RekapAbsensiTahunanExport;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tahun = 2025;

echo "=== DEBUGGING REKAP TAHUNAN ===\n";
echo "Tahun: $tahun\n\n";

// Test 1: Logika Controller (dari RekapController::rekapTahunan)
echo "1. LOGIKA CONTROLLER (Web)\n";
echo "===========================\n";

$defaultMinutes = 7 * 60 + 30; // 450 menit

// Fungsi format dari controller
$fmtHariJamMenit = function(int $menit) use ($defaultMinutes): string {
    // basis 1 hari kerja = 7 jam 30 menit
    $menitPerHariKerja = $defaultMinutes;   // 450
    $hari = intdiv($menit, $menitPerHariKerja);
    $sisa = $menit % $menitPerHariKerja;
    $jam  = intdiv($sisa, 60);
    $mnt  = $sisa % 60;
    return sprintf('%d hari %d jam %d menit', $hari, $jam, $mnt);
};

echo "Format Controller menggunakan basis: $defaultMinutes menit per hari (7.5 jam kerja)\n\n";

// Test 2: Logika Export
echo "2. LOGIKA EXPORT\n";
echo "================\n";

$export = new RekapAbsensiTahunanExport($tahun);
$view = $export->view();
$exportPegawaiList = $view->getData()['karyawans'];

// Ambil pegawai pertama yang ada data
$testPegawai = $exportPegawaiList->filter(fn($p) => $p->total_menit > 0)->first();

if (!$testPegawai) {
    echo "Tidak ada pegawai dengan data di tahun $tahun\n";
    exit;
}

echo "Pegawai test: {$testPegawai->nama}\n";
echo "Total menit: {$testPegawai->total_menit}\n";

// Format dari Export (langsung dari objek)
echo "Format Export: {$testPegawai->total_fmt}\n";

// Format dari Controller (simulasi)
$formatController = $fmtHariJamMenit($testPegawai->total_menit);
echo "Format Controller (simulasi): $formatController\n";

// Format Export dari kode (basis 450)
$exportDefaultMinutes = 450;
$hari = intdiv($testPegawai->total_menit, $exportDefaultMinutes);
$sisa = $testPegawai->total_menit % $exportDefaultMinutes;
$jam  = intdiv($sisa, 60);
$mnt  = $sisa % 60;
$formatExportKode = sprintf('%d hari %02d jam %02d menit', $hari, $jam, $mnt);
echo "Format Export (dari kode): $formatExportKode\n";

echo "\n=== PERBANDINGAN ===\n";
if ($testPegawai->total_fmt === $formatController) {
    echo "✅ Format sama antara web dan export\n";
} else {
    echo "❌ Format berbeda!\n";
    echo "Export: {$testPegawai->total_fmt}\n";
    echo "Web  : $formatController\n";
    
    // Analisis perbedaan
    if (str_contains($testPegawai->total_fmt, '02d') || preg_match('/\d+ hari \d{2} jam \d{2} menit/', $testPegawai->total_fmt)) {
        echo "\n❗ MASALAH DITEMUKAN:\n";
        echo "Export menggunakan format padding dengan %02d (contoh: '05 jam')\n";
        echo "Web menggunakan format tanpa padding dengan %d (contoh: '5 jam')\n";
    }
}

echo "\n=== CONTOH NILAI UNTUK BERBAGAI TOTAL ===\n";
$testValues = [450, 900, 1350, 2255, 5000];

foreach ($testValues as $totalMenit) {
    echo "\nTotal: $totalMenit menit\n";
    
    // Format web (basis 450, tanpa padding)
    $hari = intdiv($totalMenit, 450);
    $sisa = $totalMenit % 450;
    $jam  = intdiv($sisa, 60);
    $mnt  = $sisa % 60;
    $formatWeb = sprintf('%d hari %d jam %d menit', $hari, $jam, $mnt);
    
    // Format export (basis 450, dengan padding)
    $formatExport = sprintf('%d hari %02d jam %02d menit', $hari, $jam, $mnt);
    
    echo "  Web   : $formatWeb\n";
    echo "  Export: $formatExport\n";
}

echo "\n=== END DEBUGGING ===\n";
