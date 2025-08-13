<?php

require_once 'vendor/autoload.php';

use App\Http\Controllers\RekapController;
use Illuminate\Http\Request;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST TOTAL AKUMULASI HANYA BULAN BERDATA ===\n";

$tahun = 2025;

// Test controller
$request = new Request(['tahun' => $tahun]);
$controller = new RekapController();
$view = $controller->rekapTahunan($request);
$pegawaiList = $view->getData()['pegawaiList'];

$testPegawai = $pegawaiList->first();

echo "=== KARYAWAN: {$testPegawai->nama} ===\n";
echo "Total menit (setelah perbaikan): {$testPegawai->total_menit}\n";
echo "Total format: {$testPegawai->total_fmt}\n\n";

echo "=== DETAIL PER BULAN ===\n";
$totalManual = 0;
$bulanBerdata = [];

foreach ($testPegawai->menitPerBulan as $bln => $menit) {
    $namaBulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ][$bln];
    
    if ($menit > 0) {
        echo "✅ {$namaBulan}: {$menit} menit (ADA DATA)\n";
        $totalManual += $menit;
        $bulanBerdata[] = $namaBulan;
    } else {
        echo "⭕ {$namaBulan}: {$menit} menit (KOSONG - tidak dihitung)\n";
    }
}

echo "\n=== VERIFIKASI ===\n";
echo "Total manual (sum bulan berdata): {$totalManual} menit\n";
echo "Total controller: {$testPegawai->total_menit} menit\n";
echo "Bulan yang dihitung: " . implode(', ', $bulanBerdata) . "\n";

if ($totalManual == $testPegawai->total_menit) {
    echo "✅ BENAR: Total akumulasi = sum hanya bulan yang ada datanya\n";
} else {
    echo "❌ SALAH: Total tidak sesuai\n";
    echo "Selisih: " . abs($totalManual - $testPegawai->total_menit) . " menit\n";
}

// Format verifikasi
$fmtHariJamMenit = function($menit) {
    $hari = intdiv($menit, 450);
    $sisa = $menit % 450;
    $jam = intdiv($sisa, 60);
    $mnt = $sisa % 60;
    return sprintf('%d hari %d jam %d menit', $hari, $jam, $mnt);
};

echo "\nFormat manual: " . $fmtHariJamMenit($totalManual) . "\n";
echo "Format controller: {$testPegawai->total_fmt}\n";

echo "\n=== KESIMPULAN ===\n";
if ($totalManual == $testPegawai->total_menit) {
    echo "🎉 PERBAIKAN BERHASIL!\n";
    echo "✅ Total akumulasi hanya menjumlahkan bulan yang ada datanya\n";
    echo "✅ Bulan kosong (0 menit) tidak dimasukkan ke total\n";
    echo "✅ Tidak ada lagi perhitungan 7,5 jam default untuk bulan kosong\n";
} else {
    echo "❌ Masih ada masalah dalam perhitungan\n";
}

echo "\n=== END TEST ===\n";
