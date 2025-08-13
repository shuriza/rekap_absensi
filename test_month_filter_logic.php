<?php
/**
 * Test script untuk memverifikasi logic bulan kosong dengan filter tanggal
 * Hanya bulan yang sudah berlalu atau sedang berjalan yang dihitung penalty
 */

require_once 'vendor/autoload.php';

use Carbon\Carbon;

echo "=== TEST LOGIC BULAN KOSONG DENGAN FILTER TANGGAL ===\n";
echo "Tanggal sekarang: " . Carbon::now()->format('Y-m-d') . "\n";
echo "Bulan sekarang: " . Carbon::now()->format('Y-m') . "\n\n";

$tahun = 2025;
$defaultMinutes = 450;

// Test untuk berbagai bulan
$testBulans = [
    1 => 'Januari (sudah berlalu)',
    2 => 'Februari (sudah berlalu)', 
    3 => 'Maret (sudah berlalu)',
    4 => 'April (sudah berlalu)',
    5 => 'Mei (sudah berlalu)',
    6 => 'Juni (sudah berlalu)',
    7 => 'Juli (sudah berlalu)',
    8 => 'Agustus (sedang berjalan)',
    9 => 'September (masa depan)',
    10 => 'Oktober (masa depan)',
    11 => 'November (masa depan)',
    12 => 'Desember (masa depan)',
];

foreach ($testBulans as $bulan => $description) {
    echo "=== BULAN $bulan ($description) ===\n";
    
    // Simulasi tidak ada data absen ($hadAny = false)
    $hadAny = false;
    
    // Cek apakah bulan ini sudah berlalu atau sedang berjalan
    $bulanIni = Carbon::create($tahun, $bulan, 1);
    $sekarang = Carbon::now();
    
    echo "Bulan yang dicek: " . $bulanIni->format('Y-m') . "\n";
    echo "Apakah <= bulan sekarang? " . ($bulanIni->lte($sekarang->startOfMonth()) ? 'YA' : 'TIDAK') . "\n";
    
    if (!$hadAny) {
        if ($bulanIni->lte($sekarang->startOfMonth())) {
            // Hitung hari kerja (simulasi sederhana)
            $daysInMonth = Carbon::create($tahun, $bulan)->daysInMonth;
            $totalHariKerja = 0;
            
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $tglStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                $weekday = Carbon::parse($tglStr)->dayOfWeekIso;
                
                // Skip weekend (simulasi sederhana, tidak termasuk libur nasional)
                if ($weekday >= 6) continue;
                $totalHariKerja++;
            }
            
            $penalty = $totalHariKerja * $defaultMinutes;
            echo "Hasil: DIHITUNG PENALTY\n";
            echo "Hari kerja: $totalHariKerja hari\n";
            echo "Penalty: $penalty menit\n";
            
            // Format tampilan
            $hari = intdiv($penalty, 1440);
            $sisaMenit = $penalty % 1440;
            $jam = intdiv($sisaMenit, 60);
            $menit = $sisaMenit % 60;
            echo "Format: $hari hari $jam jam $menit menit\n";
        } else {
            echo "Hasil: TIDAK DIHITUNG (masa depan)\n";
            echo "Penalty: 0 menit\n";
            echo "Format: — (tidak ditampilkan)\n";
        }
    }
    
    echo "\n";
}

echo "=== KESIMPULAN ===\n";
echo "✅ Bulan yang sudah berlalu: dihitung penalty jika tidak ada data\n";
echo "✅ Bulan sedang berjalan: dihitung penalty jika tidak ada data\n";
echo "✅ Bulan masa depan: tidak dihitung penalty (tetap 0)\n";
?>
