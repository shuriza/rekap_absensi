<?php

require_once 'vendor/autoload.php';

use App\Http\Controllers\RekapController;
use App\Exports\RekapAbsensiTahunanExport;
use Illuminate\Http\Request;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST KONSISTENSI VIEW WEB vs EXPORT SETELAH PERBAIKAN ===\n";

$tahun = 2025;

// Test controller (view web)
$request = new Request(['tahun' => $tahun]);
$controller = new RekapController();
$viewWeb = $controller->rekapTahunan($request);
$pegawaiListWeb = $viewWeb->getData()['pegawaiList'];

// Test export
$export = new RekapAbsensiTahunanExport($tahun);
$viewExport = $export->view();
$pegawaiListExport = $viewExport->getData()['karyawans'];

$rianWeb = $pegawaiListWeb->firstWhere('nama', 'RIAN');
$rianExport = $pegawaiListExport->firstWhere('nama', 'RIAN');

echo "=== PERBANDINGAN RIAN ===\n";
echo "VIEW WEB:\n";
echo "  Total menit: {$rianWeb->total_menit}\n";
echo "  Total format: {$rianWeb->total_fmt}\n";

echo "\nEXPORT:\n";
echo "  Total menit: {$rianExport->total_menit}\n";
echo "  Total format: {$rianExport->total_fmt}\n";

echo "\n=== VERIFIKASI ===\n";

if ($rianWeb->total_menit == $rianExport->total_menit) {
    echo "✅ Total menit konsisten: {$rianWeb->total_menit}\n";
} else {
    echo "❌ Total menit berbeda:\n";
    echo "  Web: {$rianWeb->total_menit}\n";
    echo "  Export: {$rianExport->total_menit}\n";
}

if ($rianWeb->total_fmt === $rianExport->total_fmt) {
    echo "✅ Format total konsisten: {$rianWeb->total_fmt}\n";
} else {
    echo "❌ Format total berbeda:\n";
    echo "  Web: {$rianWeb->total_fmt}\n";
    echo "  Export: {$rianExport->total_fmt}\n";
}

// Verifikasi per bulan juga sama
echo "\n=== VERIFIKASI PER BULAN ===\n";
$semuaSama = true;

foreach ($rianWeb->menitPerBulan as $bln => $menitWeb) {
    $menitExport = $rianExport->menitPerBulan[$bln] ?? 0;
    
    if ($menitWeb != $menitExport) {
        echo "❌ Bulan $bln: Web={$menitWeb}, Export={$menitExport}\n";
        $semuaSama = false;
    }
}

if ($semuaSama) {
    echo "✅ Semua bulan konsisten\n";
}

echo "\n=== HASIL AKHIR ===\n";

if ($rianWeb->total_menit == $rianExport->total_menit && 
    $rianWeb->total_fmt === $rianExport->total_fmt && 
    $semuaSama) {
    echo "🎉 EXPORT SUDAH KONSISTEN DENGAN VIEW WEB!\n";
    echo "✅ Total akumulasi: Hanya bulan berdata\n";
    echo "✅ Format: Basis 1440 menit (24 jam kalender)\n";
    echo "✅ Data per bulan: Identik\n";
} else {
    echo "❌ Masih ada perbedaan yang perlu diperbaiki\n";
}

echo "\n=== CONTOH OUTPUT EXPORT ===\n";
echo "RIAN: {$rianExport->total_fmt}\n";

echo "\n=== END TEST ===\n";
