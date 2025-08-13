<?php
/**
 * Test script untuk memverifikasi logic bulan kosong (tanpa data absen sama sekali)
 * Skenario: Karyawan Rendi tidak masuk sama sekali di bulan Mei
 */

require_once 'vendor/autoload.php';

use Carbon\Carbon;

// Simulasi data
$tahun = 2024;
$bulan = 5; // Mei
$defaultMinutes = 450; // 7.5 jam default penalty

echo "=== TEST LOGIC BULAN KOSONG (TANPA DATA ABSEN) ===\n";
echo "Skenario: Rendi tidak masuk sama sekali di bulan Mei $tahun\n\n";

// Data simulasi
$hadAny = false; // Tidak ada data absen sama sekali di bulan ini
$holidayMap = [
    '2024-05-01' => 'Hari Buruh', // Libur nasional
    '2024-05-09' => 'Kenaikan Isa Almasih',
];
$mapIzin = []; // Tidak ada izin

$daysInMonth = Carbon::create($tahun, $bulan)->daysInMonth;
echo "Jumlah hari di bulan $bulan/$tahun: $daysInMonth hari\n";

// Hitung hari kerja di bulan ini (exclude weekend dan libur)
$totalHariKerja = 0;
$detailHari = [];

for ($d = 1; $d <= $daysInMonth; $d++) {
    $tglStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
    $carbon = Carbon::parse($tglStr);
    $weekday = $carbon->dayOfWeekIso; // 6=Sabtu, 7=Minggu
    $dayName = $carbon->isoFormat('dddd');
    
    $status = '';
    
    // Skip weekend
    if ($weekday >= 6) {
        $status = "Weekend";
        $detailHari[] = "$tglStr ($dayName) - $status";
        continue;
    }
    
    // Skip libur nasional
    if (isset($holidayMap[$tglStr])) {
        $status = "Libur: " . $holidayMap[$tglStr];
        $detailHari[] = "$tglStr ($dayName) - $status";
        continue;
    }
    
    // Skip izin (dalam kasus ini tidak ada)
    if (isset($mapIzin[$tglStr])) {
        $status = "Izin";
        $detailHari[] = "$tglStr ($dayName) - $status";
        continue;
    }
    
    // Ini hari kerja
    $totalHariKerja++;
    $status = "HARI KERJA (tidak masuk → penalty)";
    $detailHari[] = "$tglStr ($dayName) - $status";
}

echo "\nDetail per hari:\n";
foreach ($detailHari as $detail) {
    echo "  $detail\n";
}

echo "\nTotal hari kerja: $totalHariKerja hari\n";
echo "Default penalty per hari: $defaultMinutes menit (7.5 jam)\n";

// Logic baru: jika tidak ada data sama sekali ($hadAny = false)
if (!$hadAny) {
    $penaltyBulan = $totalHariKerja * $defaultMinutes;
    echo "\n=== HASIL LOGIC BARU ===\n";
    echo "Karena tidak ada data absen sama sekali ($hadAny = false)\n";
    echo "Penalty untuk bulan ini = $totalHariKerja hari × $defaultMinutes menit = $penaltyBulan menit\n";
    
    // Konversi ke format hari-jam-menit (basis 1440 menit = 24 jam)
    $hari = intdiv($penaltyBulan, 1440);
    $sisaMenit = $penaltyBulan % 1440;
    $jam = intdiv($sisaMenit, 60);
    $menit = $sisaMenit % 60;
    
    echo "Format tampilan: $hari hari $jam jam $menit menit\n";
    
    // Konversi ke format HH:MM juga untuk referensi
    $totalJam = intdiv($penaltyBulan, 60);
    $menitSisa = $penaltyBulan % 60;
    echo "Format HH:MM: " . sprintf('%02d:%02d', $totalJam, $menitSisa) . "\n";
} else {
    echo "\n=== LOGIC LAMA (SEHARUSNYA TIDAK TERJADI) ===\n";
    echo "Ada data absen di bulan ini, tidak relevan untuk test ini\n";
}

echo "\n=== KESIMPULAN ===\n";
echo "Dengan logic baru, karyawan yang tidak masuk sama sekali dalam 1 bulan\n";
echo "akan tetap mendapat penalty sesuai jumlah hari kerja di bulan tersebut.\n";
echo "Ini memastikan tidak ada karyawan yang 'terlihat bagus' karena tidak ada data.\n";
?>
