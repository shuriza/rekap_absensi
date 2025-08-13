<?php
/**
 * Test script untuk memverifikasi logic "bulan aktif"
 * Hanya bulan yang ada karyawan lain punya data yang dihitung penalty untuk karyawan tanpa data
 */

require_once 'vendor/autoload.php';

use Carbon\Carbon;

echo "=== TEST LOGIC BULAN AKTIF ===\n";
echo "Skenario: Deteksi bulan mana yang 'aktif' (ada karyawan yang punya data absen)\n\n";

// Simulasi data absen karyawan
$simulasiDataAbsen = [
    'Budi' => [
        '2025-04-15', '2025-04-16', '2025-04-17', // April: ada data
        '2025-05-10', '2025-05-11', '2025-05-12', // Mei: ada data
    ],
    'Andi' => [
        '2025-04-20', '2025-04-21', // April: ada data
        '2025-06-05', '2025-06-06', // Juni: ada data
    ],
    'Rendi' => [
        // Tidak ada data sama sekali (karyawan bermasalah)
    ],
];

// Deteksi bulan aktif
$bulanAktif = [];
foreach ($simulasiDataAbsen as $nama => $tanggalList) {
    foreach ($tanggalList as $tanggal) {
        $bulan = Carbon::parse($tanggal)->month;
        $bulanAktif[$bulan] = true;
    }
}

echo "=== HASIL DETEKSI BULAN AKTIF ===\n";
for ($bln = 1; $bln <= 12; $bln++) {
    $namaBulan = Carbon::create(2025, $bln, 1)->isoFormat('MMMM');
    $status = isset($bulanAktif[$bln]) ? '✅ AKTIF' : '❌ TIDAK AKTIF';
    echo "Bulan $bln ($namaBulan): $status\n";
}

echo "\n=== SIMULASI PENALTY UNTUK RENDI (TIDAK ADA DATA) ===\n";
$defaultMinutes = 450;

for ($bln = 1; $bln <= 12; $bln++) {
    $namaBulan = Carbon::create(2025, $bln, 1)->isoFormat('MMMM');
    
    // Simulasi: Rendi tidak punya data ($hadAny = false)
    $hadAny = false;
    
    if (!$hadAny) {
        if (isset($bulanAktif[$bln])) {
            // Bulan aktif: ada karyawan lain yang punya data
            // Rendi yang tidak masuk → dihitung penalty
            $totalHariKerja = 22; // Simulasi hari kerja (sekitar 22 hari per bulan)
            $penalty = $totalHariKerja * $defaultMinutes;
            
            // Format tampilan
            $hari = intdiv($penalty, 1440);
            $sisaMenit = $penalty % 1440;
            $jam = intdiv($sisaMenit, 60);
            $menit = $sisaMenit % 60;
            
            echo "$namaBulan: 📊 DIHITUNG PENALTY → $hari hari $jam jam $menit menit\n";
        } else {
            // Bulan tidak aktif: belum ada data siapa pun
            echo "$namaBulan: — TIDAK DITAMPILKAN (belum ada data)\n";
        }
    }
}

echo "\n=== KESIMPULAN ===\n";
echo "✅ April & Mei: Dihitung penalty untuk Rendi (ada Budi yang masuk)\n";
echo "✅ Juni: Dihitung penalty untuk Rendi (ada Andi yang masuk)\n";
echo "✅ Bulan lain: Tidak ditampilkan (belum ada data siapa pun)\n";
echo "\nIni memastikan:\n";
echo "- Karyawan bermasalah tetap dapat penalty di bulan yang ada aktivitas\n";
echo "- Bulan yang belum ada aktivitas tidak ditampilkan sama sekali\n";
?>
