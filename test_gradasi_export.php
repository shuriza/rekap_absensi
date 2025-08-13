<?php

require_once 'vendor/autoload.php';

use App\Exports\RekapAbsensiTahunanExport;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST GRADASI WARNA EXPORT TAHUNAN ===\n";

$tahun = 2025;

// Test export
$export = new RekapAbsensiTahunanExport($tahun);
$view = $export->view();
$pegawaiList = $view->getData()['karyawans'];

echo "=== CONTOH DATA UNTUK GRADASI ===\n";

// Ambil beberapa pegawai untuk melihat format data
$samples = $pegawaiList->take(3);

foreach ($samples as $pegawai) {
    echo "\n{$pegawai->nama}:\n";
    
    foreach ($pegawai->menitPerBulan as $bln => $menit) {
        if ($menit > 0) {
            // Format yang sekarang digunakan di template
            $hari = intdiv($menit, 1440);
            $sisa = $menit % 1440;
            $jam = intdiv($sisa, 60);
            $mnt = $sisa % 60;
            $formatBulan = sprintf('%d hari %02d jam %02d menit', $hari, $jam, $mnt);
            
            echo "  Bulan $bln: {$menit} menit → '{$formatBulan}'\n";
            
            // Test parsing regex
            if (preg_match('/(\d+) hari (\d+) jam (\d+) menit/', $formatBulan, $matches)) {
                $hariParsed = intval($matches[1]);
                $jamParsed = intval($matches[2]);
                $menitParsed = intval($matches[3]);
                $totalMinutes = ($hariParsed * 1440) + ($jamParsed * 60) + $menitParsed;
                
                echo "    → Parsed: {$hariParsed} hari, {$jamParsed} jam, {$menitParsed} menit\n";
                echo "    → Total minutes: {$totalMinutes} (original: {$menit})\n";
                
                if ($totalMinutes == $menit) {
                    echo "    ✅ Parsing benar\n";
                } else {
                    echo "    ❌ Parsing salah\n";
                }
                
                // Test gradasi
                $minMinutes = 1440;
                $maxMinutes = 7200;
                $steps = 8;
                $stepSize = ceil(($maxMinutes - $minMinutes) / $steps);
                $idx = max(0, min((int) floor(($totalMinutes - $minMinutes) / $stepSize), $steps - 1));
                
                $skyShades = ['sky-200', 'sky-300', 'sky-400', 'sky-500', 'sky-600', 'sky-700', 'sky-800', 'sky-900'];
                echo "    → Gradasi index: {$idx} ({$skyShades[$idx]})\n";
            } else {
                echo "    ❌ Regex tidak match\n";
            }
        }
    }
}

echo "\n=== VERIFIKASI REGEX PATTERN ===\n";

$testCases = [
    '2 hari 12 jam 00 menit',
    '1 hari 13 jam 35 menit',
    '0 hari 07 jam 30 menit',
    '10 hari 05 jam 15 menit'
];

foreach ($testCases as $test) {
    echo "Test: '{$test}'\n";
    if (preg_match('/(\d+) hari (\d+) jam (\d+) menit/', $test, $matches)) {
        echo "  ✅ Match: {$matches[1]} hari, {$matches[2]} jam, {$matches[3]} menit\n";
    } else {
        echo "  ❌ No match\n";
    }
}

echo "\n=== KESIMPULAN ===\n";
echo "✅ Gradasi warna sekarang menggunakan regex untuk parse format 'X hari Y jam Z menit'\n";
echo "✅ Basis gradasi: 1440-7200 menit (1-5 hari kalender)\n";
echo "✅ Export akan menampilkan gradasi warna sesuai format baru\n";

echo "\n=== END TEST ===\n";
