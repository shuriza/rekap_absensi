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

echo "=== CEK MULTIPLE PEGAWAI ===\n";
echo "Bulan: $bulan, Tahun: $tahun\n\n";

// === Export Bulanan ===
$exportBulanan = new RekapAbsensiBulananExport($bulan, $tahun);
$viewBulanan = $exportBulanan->view();
$pegawaiListBulanan = $viewBulanan->getData()['pegawaiList'];

// === Export Tahunan ===
$exportTahunan = new RekapAbsensiTahunanExport($tahun);
$viewTahunan = $exportTahunan->view();
$pegawaiListTahunan = $viewTahunan->getData()['karyawans'];

echo "Jumlah pegawai di export bulanan: " . $pegawaiListBulanan->count() . "\n";
echo "Jumlah pegawai di export tahunan: " . $pegawaiListTahunan->count() . "\n\n";

// Bandingkan 5 pegawai pertama
$maxTest = min(5, $pegawaiListBulanan->count());
$perbedaanDitemukan = false;

echo "=== PERBANDINGAN $maxTest PEGAWAI PERTAMA ===\n";
for ($i = 0; $i < $maxTest; $i++) {
    $pegawaiBulanan = $pegawaiListBulanan->get($i);
    $pegawaiTahunan = $pegawaiListTahunan->firstWhere('nama', $pegawaiBulanan->nama);
    
    if (!$pegawaiTahunan) {
        echo "❌ Pegawai {$pegawaiBulanan->nama} tidak ditemukan di export tahunan\n";
        $perbedaanDitemukan = true;
        continue;
    }
    
    $menitBulanan = $pegawaiBulanan->total_menit;
    $menitTahunan = $pegawaiTahunan->menitPerBulan[$bulan] ?? 0;
    
    $status = $menitBulanan == $menitTahunan ? '✅' : '❌';
    echo "$status {$pegawaiBulanan->nama}: Bulanan=$menitBulanan, Tahunan=$menitTahunan\n";
    
    if ($menitBulanan != $menitTahunan) {
        $perbedaanDitemukan = true;
        echo "   Selisih: " . abs($menitBulanan - $menitTahunan) . "\n";
    }
}

if (!$perbedaanDitemukan) {
    echo "\n✅ Semua data konsisten!\n";
    echo "\n=== CEK KEMUNGKINAN PENYEBAB LAIN ===\n";
    
    // Cek apakah ada perbedaan dalam filter pegawai
    echo "Filter pegawai aktif:\n";
    echo "- Bulanan: Menggunakan filter nonaktifPadaBulan\n";
    echo "- Tahunan: Tidak ada filter khusus (semua pegawai)\n\n";
    
    // Cek apakah ada pegawai yang tidak aktif di bulan ini
    $semuaPegawai = Karyawan::with(['nonaktif_terbaru'])->get();
    $pegawaiNonaktif = $semuaPegawai->filter(fn($k) => $k->nonaktifPadaBulan($tahun, $bulan));
    
    echo "Pegawai nonaktif di bulan $bulan/$tahun: " . $pegawaiNonaktif->count() . "\n";
    if ($pegawaiNonaktif->count() > 0) {
        foreach ($pegawaiNonaktif->take(3) as $p) {
            echo "  - {$p->nama}\n";
        }
        if ($pegawaiNonaktif->count() > 3) {
            echo "  ... dan " . ($pegawaiNonaktif->count() - 3) . " lainnya\n";
        }
    }
    
    echo "\n❗ KEMUNGKINAN PENYEBAB:\n";
    echo "1. User melihat data di bulan/tahun yang berbeda\n";
    echo "2. Ada perubahan data setelah export terakhir\n";
    echo "3. User menggunakan filter pencarian yang berbeda\n";
    echo "4. Browser cache menampilkan data lama\n";
    echo "5. Ada perbedaan dalam segment tampilan (1-10, 11-20, 21-31) vs total\n";
    
} else {
    echo "\n❌ Ditemukan perbedaan data!\n";
}

echo "\n=== SARAN SOLUSI ===\n";
echo "1. Pastikan user melihat bulan/tahun yang sama\n";
echo "2. Refresh browser untuk menghindari cache\n";
echo "3. Periksa apakah ada perubahan data terbaru\n";
echo "4. Pastikan tidak ada filter pencarian yang aktif\n";

echo "\n=== END CHECK ===\n";
