<?php
/**
 * Test script untuk memverifikasi logic gradient dinamis 10 step
 */

echo "=== TEST LOGIC GRADIENT DINAMIS 10 STEP ===\n";

// Simulasi data penalty dalam menit
$simulasiData = [
    'Budi' => [
        'Januari' => 100,   // Terlambat 100 menit
        'Februari' => 300,  // 300 menit
        'Maret' => 500,     // 500 menit
        'April' => 800,     // 800 menit
    ],
    'Andi' => [
        'Januari' => 50,    // 50 menit
        'Februari' => 200,  // 200 menit
        'Maret' => 450,     // 450 menit
        'April' => 900,     // 900 menit
    ],
    'Rendi' => [
        'Januari' => 150,   // 150 menit
        'Februari' => 600,  // 600 menit
        'Maret' => 750,     // 750 menit
        'April' => 1000,    // 1000 menit
    ],
];

// === LANGKAH 1: KUMPULKAN SEMUA NILAI ===
$semuaMinit = [];
foreach ($simulasiData as $nama => $bulanData) {
    foreach ($bulanData as $bulan => $menit) {
        if ($menit > 0) {
            $semuaMinit[] = $menit;
        }
    }
}

echo "Data semua penalty: " . implode(', ', $semuaMinit) . " menit\n";

// === LANGKAH 2: HITUNG RANGE DINAMIS ===
$minMinutes = 0; // Selalu 0
$maxMinutes = !empty($semuaMinit) ? max($semuaMinit) : 1000;

echo "Range dinamis: $minMinutes - $maxMinutes menit\n";

// === LANGKAH 3: BUAT 10 STEP ===
$steps = 10;
$stepSize = ($maxMinutes - $minMinutes) / $steps;

echo "Step size: " . round($stepSize, 2) . " menit per step\n\n";

// Definisi warna
$skyShades = [
    'sky-50',  'sky-100', 'sky-200', 'sky-300', 'sky-400',
    'sky-500', 'sky-600', 'sky-700', 'sky-800', 'sky-900'
];

// === LANGKAH 4: HITUNG RANGE UNTUK SETIAP STEP ===
echo "=== RANGE GRADASI (10 STEP) ===\n";
for ($i = 0; $i < $steps; $i++) {
    $rangeMin = $minMinutes + ($i * $stepSize);
    $rangeMax = $minMinutes + (($i + 1) * $stepSize);
    
    if ($i == $steps - 1) {
        $rangeMax = $maxMinutes; // Step terakhir sampai nilai maksimal
    }
    
    echo sprintf("Step %d (%s): %.0f - %.0f menit\n", 
        $i + 1, $skyShades[$i], $rangeMin, $rangeMax);
}

echo "\n=== SIMULASI PENERAPAN WARNA ===\n";

// Test penerapan untuk setiap data
foreach ($simulasiData as $nama => $bulanData) {
    echo "Karyawan: $nama\n";
    
    foreach ($bulanData as $bulan => $totalMinutes) {
        // Hitung index gradasi
        if ($totalMinutes <= $minMinutes) {
            $idx = 0;
        } elseif ($totalMinutes >= $maxMinutes) {
            $idx = $steps - 1;
        } else {
            $position = ($totalMinutes - $minMinutes) / ($maxMinutes - $minMinutes);
            $idx = min((int) floor($position * $steps), $steps - 1);
        }
        
        echo "  $bulan: $totalMinutes menit → {$skyShades[$idx]}\n";
    }
    echo "\n";
}

echo "=== KESIMPULAN ===\n";
echo "✅ Range dihitung dinamis berdasarkan data aktual\n";
echo "✅ Nilai minimum: 0 menit (selalu)\n";
echo "✅ Nilai maksimum: $maxMinutes menit (dari data)\n";
echo "✅ 10 step gradasi: sky-50 (terbaik) sampai sky-900 (terburuk)\n";
echo "✅ Distribusi warna merata berdasarkan range data\n";
?>
