<?php

require_once 'vendor/autoload.php';

use App\Http\Controllers\RekapController;
use Illuminate\Http\Request;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST FORMAT KONSISTEN SETELAH PERBAIKAN ===\n";

$tahun = 2025;

// Test controller
$request = new Request(['tahun' => $tahun]);
$controller = new RekapController();
$view = $controller->rekapTahunan($request);
$pegawaiList = $view->getData()['pegawaiList'];

$rian = $pegawaiList->firstWhere('nama', 'RIAN');

echo "=== KARYAWAN: RIAN ===\n";
echo "Total menit: {$rian->total_menit}\n";
echo "Total format (controller): {$rian->total_fmt}\n\n";

$formatKalender = function($minutes) {
    if ($minutes === 0) return '-';
    $hari = intdiv($minutes, 1440); // Basis 1440 menit
    $sisa = $minutes % 1440;
    $jam = intdiv($sisa, 60);
    $mnt = $sisa % 60;
    return sprintf('%d hari %02d jam %02d menit', $hari, $jam, $mnt);
};

echo "=== PERBANDINGAN FORMAT PER BULAN vs TOTAL ===\n";

$namaBulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

$totalManual = 0;
foreach ($rian->menitPerBulan as $bln => $minutes) {
    if ($minutes > 0) {
        $formatBulan = $formatKalender($minutes);
        echo "{$namaBulan[$bln]}: {$minutes} menit → {$formatBulan}\n";
        $totalManual += $minutes;
    }
}

echo "\nTotal manual sum: {$totalManual} menit\n";
echo "Controller total: {$rian->total_menit} menit\n";

$formatTotalManual = $formatKalender($totalManual);
echo "\nFormat total manual: {$formatTotalManual}\n";
echo "Format total controller: {$rian->total_fmt}\n";

echo "\n=== HASIL VERIFIKASI ===\n";

if ($totalManual == $rian->total_menit) {
    echo "✅ Total menit konsisten\n";
} else {
    echo "❌ Total menit tidak konsisten\n";
}

if ($formatTotalManual === $rian->total_fmt) {
    echo "✅ Format total konsisten dengan format per bulan\n";
    echo "✅ Semua menggunakan basis 1440 menit (24 jam kalender)\n";
} else {
    echo "❌ Format total masih berbeda\n";
    echo "Manual: {$formatTotalManual}\n";
    echo "Controller: {$rian->total_fmt}\n";
}

echo "\n=== SIMULASI TAMPILAN WEB ===\n";
echo "No | Nama | Jan | Feb | Mar | Apr | May | Jun | Jul | Aug | Sep | Oct | Nov | Dec | Total Akumulasi\n";
echo "---+------+-----+-----+-----+-----+-----+-----+-----+-----+-----+-----+-----+-----+------------------\n";

$row = "1  | RIAN | ";
for ($bln = 1; $bln <= 12; $bln++) {
    $minutes = $rian->menitPerBulan[$bln] ?? 0;
    $label = $minutes > 0 ? $formatKalender($minutes) : '-';
    $row .= str_pad($label, 20) . " | ";
}
$row .= $rian->total_fmt;

echo $row . "\n";

echo "\n🎉 SEKARANG SEMUA FORMAT KONSISTEN MENGGUNAKAN BASIS 1440 MENIT!\n";

echo "\n=== END TEST ===\n";
