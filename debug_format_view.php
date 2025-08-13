<?php

require_once 'vendor/autoload.php';

use App\Http\Controllers\RekapController;
use Illuminate\Http\Request;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG PERBEDAAN FORMAT VIEW WEB ===\n";

$tahun = 2025;

// Test controller
$request = new Request(['tahun' => $tahun]);
$controller = new RekapController();
$view = $controller->rekapTahunan($request);
$pegawaiList = $view->getData()['pegawaiList'];

$rian = $pegawaiList->firstWhere('nama', 'RIAN');

if (!$rian) {
    echo "RIAN tidak ditemukan\n";
    exit;
}

echo "=== KARYAWAN: RIAN ===\n";
echo "Total controller: {$rian->total_menit} menit\n";
echo "Total format: {$rian->total_fmt}\n\n";

echo "=== DETAIL PER BULAN ===\n";

$formatBasis450 = function($minutes) {
    if ($minutes === 0) return '-';
    $hari = intdiv($minutes, 450);  // Basis 450 menit
    $sisa = $minutes % 450;
    $jam = intdiv($sisa, 60);
    $mnt = $sisa % 60;
    return sprintf('%d hari %d jam %02d menit', $hari, $jam, $mnt);
};

$formatBasis1440 = function($minutes) {
    if ($minutes === 0) return '-';
    $hari = intdiv($minutes, 1440); // Basis 1440 menit
    $sisa = $minutes % 1440;
    $jam = intdiv($sisa, 60);
    $mnt = $sisa % 60;
    return sprintf('%d hari %02d jam %02d menit', $hari, $jam, $mnt);
};

$namaBulan = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags',
    9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
];

$totalManualBasis450 = 0;
$totalManualBasis1440 = 0;

foreach ($rian->menitPerBulan as $bln => $minutes) {
    if ($minutes > 0) {
        $format450 = $formatBasis450($minutes);
        $format1440 = $formatBasis1440($minutes);
        
        echo "{$namaBulan[$bln]}: {$minutes} menit\n";
        echo "  Basis 450:  {$format450}\n";
        echo "  Basis 1440: {$format1440}\n";
        echo "  View web saat ini: {$format1440} (basis 1440)\n\n";
        
        $totalManualBasis450 += $minutes;
        $totalManualBasis1440 += $minutes;
    }
}

echo "=== VERIFIKASI TOTAL ===\n";
echo "Sum manual: {$totalManualBasis450} menit\n";
echo "Controller: {$rian->total_menit} menit\n";

$totalFormat450 = $formatBasis450($totalManualBasis450);
$totalFormat1440 = $formatBasis1440($totalManualBasis1440);

echo "\nFormat total basis 450:  {$totalFormat450}\n";
echo "Format total basis 1440: {$totalFormat1440}\n";
echo "Controller format:       {$rian->total_fmt}\n";

echo "\n=== ANALISIS PERBEDAAN ===\n";

// Hitung berapa yang ditampilkan user vs controller
$aprilMenit = $rian->menitPerBulan[4] ?? 0;
$meiMenit = $rian->menitPerBulan[5] ?? 0;

echo "April: {$aprilMenit} menit → " . $formatBasis1440($aprilMenit) . "\n";
echo "Mei:   {$meiMenit} menit → " . $formatBasis1440($meiMenit) . "\n";
echo "Total: " . ($aprilMenit + $meiMenit) . " menit → " . $formatBasis1440($aprilMenit + $meiMenit) . "\n";

if (($aprilMenit + $meiMenit) != $rian->total_menit) {
    echo "\n❌ MASALAH DITEMUKAN:\n";
    echo "Sum April+Mei: " . ($aprilMenit + $meiMenit) . " menit\n";
    echo "Total controller: {$rian->total_menit} menit\n";
    echo "Selisih: " . abs(($aprilMenit + $meiMenit) - $rian->total_menit) . " menit\n";
} else {
    echo "\n✅ Total sudah konsisten\n";
}

echo "\n=== END DEBUG ===\n";
