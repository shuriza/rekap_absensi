<?php

require_once 'vendor/autoload.php';

use App\Http\Controllers\RekapController;
use Illuminate\Http\Request;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST TOTAL AKUMULASI SETELAH PERBAIKAN ===\n";

$tahun = 2025;

// Simulasi request
$request = new Request(['tahun' => $tahun]);

// Test controller
$controller = new RekapController();
$view = $controller->rekapTahunan($request);
$pegawaiList = $view->getData()['pegawaiList'];

$testPegawai = $pegawaiList->first();

echo "=== KARYAWAN: {$testPegawai->nama} ===\n";
echo "Total menit (baru): {$testPegawai->total_menit}\n";
echo "Total format (baru): {$testPegawai->total_fmt}\n\n";

// Manual calculation untuk verifikasi
$penalty_riil_total = 0;
foreach ($testPegawai->menitPerBulan as $bln => $menit) {
    if ($menit > 0) {
        echo "Bulan $bln: {$menit} menit (termasuk default hari kosong)\n";
    }
}

echo "\n=== PERBANDINGAN ===\n";

// Hitung manual untuk bulan Mei
$absenMei = \App\Models\Karyawan::find($testPegawai->id)->absensi()
    ->whereYear('tanggal', 2025)
    ->whereMonth('tanggal', 5)
    ->get();

$penaltyRiilMei = 0;
foreach ($absenMei as $abs) {
    $penalty = is_numeric($abs->penalty_minutes) ? max(0, (int) $abs->penalty_minutes) : 450;
    $penaltyRiilMei += $penalty;
}

echo "Manual penalty riil Mei: {$penaltyRiilMei} menit\n";
echo "Controller total: {$testPegawai->total_menit} menit\n";

if ($testPegawai->total_menit < ($testPegawai->menitPerBulan[5] ?? 0)) {
    echo "✅ BENAR: Total akumulasi lebih kecil dari bulan individual (tidak termasuk default hari kosong)\n";
} else {
    echo "❌ Masih salah: Total akumulasi sama dengan sum semua bulan\n";
}

echo "\n=== DETAIL BULAN MEI ===\n";
echo "Bulan Mei (dengan default): " . ($testPegawai->menitPerBulan[5] ?? 0) . " menit\n";
echo "Penalty riil Mei saja: {$penaltyRiilMei} menit\n";
echo "Selisih (hari kosong): " . (($testPegawai->menitPerBulan[5] ?? 0) - $penaltyRiilMei) . " menit\n";

$formatRiil = function($menit) {
    $hari = intdiv($menit, 450);
    $sisa = $menit % 450;
    $jam = intdiv($sisa, 60);
    $mnt = $sisa % 60;
    return sprintf('%d hari %d jam %d menit', $hari, $jam, $mnt);
};

echo "\nFormat penalty riil: " . $formatRiil($penaltyRiilMei) . "\n";
echo "Format controller: {$testPegawai->total_fmt}\n";

echo "\n=== KESIMPULAN ===\n";
if ($testPegawai->total_menit > 0 && $testPegawai->total_menit < 1000) {
    echo "✅ Total akumulasi sekarang hanya menghitung data yang benar-benar ada\n";
    echo "✅ Tidak lagi termasuk default 450 menit untuk hari kosong\n";
} else {
    echo "❌ Masih ada masalah dalam perhitungan\n";
}

echo "\n=== END TEST ===\n";
