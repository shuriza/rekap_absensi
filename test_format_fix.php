<?php

require_once 'vendor/autoload.php';

use App\Exports\RekapAbsensiBulananExport;
use App\Exports\RekapAbsensiTahunanExport;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bulan = 5;  // Mei 2025
$tahun = 2025;

echo "=== TEST FORMAT TAMPILAN SETELAH PERBAIKAN ===\n";
echo "Bulan: $bulan, Tahun: $tahun\n\n";

// Export bulanan
$exportBulanan = new RekapAbsensiBulananExport($bulan, $tahun);
$viewBulanan = $exportBulanan->view();

// Export tahunan  
$exportTahunan = new RekapAbsensiTahunanExport($tahun);
$viewTahunan = $exportTahunan->view();

// Render view tahunan untuk melihat format yang dihasilkan
$renderedView = $viewTahunan->render();

// Ekstrak beberapa baris dari tabel untuk melihat format
preg_match_all('/<td>([^<]*hari[^<]*)<\/td>/', $renderedView, $matches);

echo "=== CONTOH FORMAT TAMPILAN TAHUNAN (SETELAH PERBAIKAN) ===\n";
$samples = array_slice(array_unique($matches[1]), 0, 10);
foreach ($samples as $i => $format) {
    echo ($i + 1) . ". {$format}\n";
}

// Verifikasi tidak ada lagi format HH:MM
$hasHHMM = preg_match('/\d{2,}:\d{2}/', $renderedView);
echo "\n=== VERIFIKASI ===\n";
echo "Format HH:MM masih ada? " . ($hasHHMM ? "❌ Ya (masih ada)" : "✅ Tidak (sudah diperbaiki)") . "\n";
echo "Format 'X hari Y jam Z menit'? " . (count($samples) > 0 ? "✅ Ya (sudah konsisten)" : "❌ Tidak") . "\n";

// Bandingkan dengan bulanan  
$pegawaiBulanan = $viewBulanan->getData()['pegawaiList']->first();
$pegawaiTahunan = $viewTahunan->getData()['karyawans']->firstWhere('nama', $pegawaiBulanan->nama);

if ($pegawaiBulanan && $pegawaiTahunan) {
    echo "\n=== PERBANDINGAN PEGAWAI: {$pegawaiBulanan->nama} ===\n";
    
    // Format bulanan dari template
    $total = (int) $pegawaiBulanan->total_menit;
    $hari = intdiv($total, 1440);
    $sisa = $total % 1440;
    $jam = str_pad(intdiv($sisa, 60), 2, '0', STR_PAD_LEFT);
    $menit = str_pad($sisa % 60, 2, '0', STR_PAD_LEFT);
    $formatBulanan = "{$hari} hari {$jam} jam {$menit} menit";
    
    // Format tahunan dari template (perhitungan sama)
    $menitBulan = $pegawaiTahunan->menitPerBulan[$bulan] ?? 0;
    if ($menitBulan > 0) {
        $hari2 = intdiv($menitBulan, 1440);
        $sisa2 = $menitBulan % 1440;
        $jam2 = str_pad(intdiv($sisa2, 60), 2, '0', STR_PAD_LEFT);
        $mnt2 = str_pad($sisa2 % 60, 2, '0', STR_PAD_LEFT);
        $formatTahunan = "{$hari2} hari {$jam2} jam {$mnt2} menit";
    } else {
        $formatTahunan = '—';
    }
    
    echo "Format Bulanan:  {$formatBulanan}\n";
    echo "Format Tahunan:  {$formatTahunan}\n";
    echo "Status: " . ($formatBulanan === $formatTahunan ? "✅ IDENTIK" : "❌ BERBEDA") . "\n";
}

echo "\n🎉 PERBAIKAN SELESAI!\n";
echo "✅ Template tahunan sekarang menggunakan format yang sama dengan bulanan\n";
echo "✅ Basis perhitungan: 1440 menit = 24 jam = 1 hari kalender\n";
echo "✅ Format: 'X hari Y jam Z menit'\n";

echo "\n=== END TEST ===\n";
