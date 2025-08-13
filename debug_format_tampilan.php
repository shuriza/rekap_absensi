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

echo "=== PERBANDINGAN FORMAT TAMPILAN ===\n";
echo "Bulan: $bulan, Tahun: $tahun\n\n";

// Test export
$exportBulanan = new RekapAbsensiBulananExport($bulan, $tahun);
$viewBulanan = $exportBulanan->view();
$pegawaiListBulanan = $viewBulanan->getData()['pegawaiList'];

$exportTahunan = new RekapAbsensiTahunanExport($tahun);
$viewTahunan = $exportTahunan->view();
$pegawaiListTahunan = $viewTahunan->getData()['karyawans'];

// Test dengan 3 pegawai pertama
for ($i = 0; $i < min(3, $pegawaiListBulanan->count()); $i++) {
    $pegawaiBulanan = $pegawaiListBulanan->get($i);
    $pegawaiTahunan = $pegawaiListTahunan->firstWhere('nama', $pegawaiBulanan->nama);
    
    if (!$pegawaiTahunan) continue;
    
    $menitBulanan = $pegawaiBulanan->total_menit;
    $menitTahunan = $pegawaiTahunan->menitPerBulan[$bulan] ?? 0;
    
    echo "=== {$pegawaiBulanan->nama} ===\n";
    echo "Menit penalty sama: " . ($menitBulanan == $menitTahunan ? "✅ Ya ({$menitBulanan})" : "❌ Tidak") . "\n";
    
    // Format bulanan (dari template)
    $total = (int) $menitBulanan;
    $hari = intdiv($total, 1440);  // 1440 menit = 24 jam = 1 hari kalender
    $sisa = $total % 1440;
    $jam = str_pad(intdiv($sisa, 60), 2, '0', STR_PAD_LEFT);
    $menit = str_pad($sisa % 60, 2, '0', STR_PAD_LEFT);
    $formatBulanan = "{$hari} hari {$jam} jam {$menit} menit";
    
    // Format tahunan (dari export class)
    $formatTahunan = $pegawaiTahunan->rekap_tahunan[$bulan] ?? '—';
    
    echo "Format Bulanan: {$formatBulanan}\n";
    echo "Format Tahunan: {$formatTahunan}\n";
    
    if ($formatTahunan === '—') {
        echo "Status: ✅ Konsisten (tidak ada data)\n";
    } else {
        // Convert HH:MM ke format hari-jam-menit
        [$hh, $mm] = explode(':', $formatTahunan);
        $totalMinutTahunan = (int)$hh * 60 + (int)$mm;
        $hariTahunan = intdiv($totalMinutTahunan, 1440);
        $sisaTahunan = $totalMinutTahunan % 1440;
        $jamTahunan = str_pad(intdiv($sisaTahunan, 60), 2, '0', STR_PAD_LEFT);
        $menitTahunan = str_pad($sisaTahunan % 60, 2, '0', STR_PAD_LEFT);
        $formatTahunanConverted = "{$hariTahunan} hari {$jamTahunan} jam {$menitTahunan} menit";
        
        echo "Format Tahunan (converted): {$formatTahunanConverted}\n";
        echo "Status: " . ($formatBulanan === $formatTahunanConverted ? "✅ Konsisten" : "❌ Berbeda format") . "\n";
    }
    echo "\n";
}

echo "=== KESIMPULAN ===\n";
echo "❌ Template tahunan masih menggunakan format HH:MM\n";
echo "✅ Template bulanan menggunakan format 'X hari Y jam Z menit'\n";
echo "🔧 Perlu menyamakan format tampilan template tahunan\n";

echo "\n=== END DEBUG ===\n";
