<?php

require_once 'vendor/autoload.php';

use App\Models\Karyawan;
use App\Models\Absensi;
use App\Exports\RekapAbsensiBulananExport;
use App\Exports\RekapAbsensiTahunanExport;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bulan = 5;  // Mei 2025
$tahun = 2025;

echo "=== DEBUG TAMPILAN VIEW TAHUNAN ===\n";
echo "Bulan: $bulan, Tahun: $tahun\n\n";

// Ambil satu karyawan untuk debug detail
$karyawan = Karyawan::with([
    'absensi' => fn($q) => $q->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan),
])->first();

if (!$karyawan) {
    echo "Tidak ada karyawan ditemukan\n";
    exit;
}

echo "=== KARYAWAN: {$karyawan->nama} ===\n";
echo "Jenis: " . ($karyawan->is_ob ? 'OB' : 'Non-OB') . "\n\n";

// Lihat beberapa record absensi
echo "=== SAMPEL DATA ABSENSI MEI 2025 ===\n";
$absensiSample = $karyawan->absensi->take(5);

foreach ($absensiSample as $abs) {
    echo "Tanggal: {$abs->tanggal->format('Y-m-d')}\n";
    echo "Jam Masuk: {$abs->jam_masuk}\n";
    echo "Jam Pulang: {$abs->jam_pulang}\n";
    echo "Penalty Minutes: {$abs->penalty_minutes}\n";
    echo "Keterangan: {$abs->keterangan}\n";
    echo "---\n";
}

// === Test Export Bulanan ===
echo "\n=== EXPORT BULANAN ===\n";
$exportBulanan = new RekapAbsensiBulananExport($bulan, $tahun);
$viewBulanan = $exportBulanan->view();
$pegawaiBulanan = $viewBulanan->getData()['pegawaiList']->firstWhere('nama', $karyawan->nama);

if ($pegawaiBulanan) {
    echo "Total Menit: {$pegawaiBulanan->total_menit}\n";
    echo "Format: " . gmdate('H:i', $pegawaiBulanan->total_menit * 60) . "\n";
}

// === Test Export Tahunan ===
echo "\n=== EXPORT TAHUNAN ===\n";
$exportTahunan = new RekapAbsensiTahunanExport($tahun);
$viewTahunan = $exportTahunan->view();
$pegawaiTahunan = $viewTahunan->getData()['karyawans']->firstWhere('nama', $karyawan->nama);

if ($pegawaiTahunan) {
    echo "Menit Per Bulan (Mei): " . ($pegawaiTahunan->menitPerBulan[$bulan] ?? 0) . "\n";
    echo "Rekap Tahunan (Mei): " . ($pegawaiTahunan->rekap_tahunan[$bulan] ?? '—') . "\n";
    echo "Total Format: {$pegawaiTahunan->total_fmt}\n";
    
    // Debug perhitungan format
    $menitMei = $pegawaiTahunan->menitPerBulan[$bulan] ?? 0;
    $formatManual = $menitMei > 0 ? sprintf('%02d:%02d', intdiv($menitMei,60), $menitMei%60) : '—';
    echo "Format Manual: {$formatManual}\n";
}

echo "\n=== PERBANDINGAN ===\n";
if ($pegawaiBulanan && $pegawaiTahunan) {
    $bulananMenit = $pegawaiBulanan->total_menit;
    $tahunanMenit = $pegawaiTahunan->menitPerBulan[$bulan] ?? 0;
    
    echo "Bulanan: {$bulananMenit} menit\n";
    echo "Tahunan: {$tahunanMenit} menit\n";
    
    if ($bulananMenit == $tahunanMenit) {
        echo "✅ Data penalty sama\n";
        
        $bulananFormat = sprintf('%02d:%02d', intdiv($bulananMenit,60), $bulananMenit%60);
        $tahunanFormat = $pegawaiTahunan->rekap_tahunan[$bulan] ?? '—';
        
        echo "Format Bulanan: {$bulananFormat}\n";
        echo "Format Tahunan: {$tahunanFormat}\n";
        
        if ($bulananFormat == $tahunanFormat) {
            echo "✅ Format tampilan sama\n";
        } else {
            echo "❌ Format tampilan berbeda\n";
        }
    } else {
        echo "❌ Data penalty berbeda\n";
    }
}

echo "\n=== END DEBUG ===\n";
