<?php

require_once 'vendor/autoload.php';

use App\Models\Karyawan;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tahun = 2025;
$defaultMinutes = 450;

echo "=== DEBUG TOTAL AKUMULASI TAHUNAN ===\n";
echo "Tahun: $tahun\n\n";

// Ambil satu karyawan untuk debug
$karyawan = Karyawan::with([
    'absensi' => fn($q) => $q->whereYear('tanggal', $tahun),
    'izins' => fn($q) => $q->where(function ($sub) use ($tahun) {
        $sub->whereYear('tanggal_awal', $tahun)
            ->orWhereYear('tanggal_akhir', $tahun);
    }),
])->first();

if (!$karyawan) {
    echo "Tidak ada karyawan ditemukan\n";
    exit;
}

echo "=== KARYAWAN: {$karyawan->nama} ===\n\n";

// Simulasi logic controller
$holidayMap = Holiday::whereYear('tanggal', $tahun)->get()
                    ->keyBy(fn($h) => $h->tanggal->toDateString());

$mapIzin = [];
foreach ($karyawan->izins as $iz) {
    foreach (CarbonPeriod::create($iz->tanggal_awal, $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
        $mapIzin[$d->toDateString()] = true;
    }
}

$mapPres = $karyawan->absensi->keyBy(fn($p) => $p->tanggal->toDateString());

$menitPerBulan = array_fill(1, 12, 0);
$bulanDenganData = [];

for ($bln = 1; $bln <= 12; $bln++) {
    $daysInMonth = Carbon::create($tahun, $bln)->daysInMonth;
    $hadAny = false;
    $noRecordCount = 0;
    $totalPenalty = 0;

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $tglStr = sprintf('%04d-%02d-%02d', $tahun, $bln, $d);
        $weekday = Carbon::parse($tglStr)->dayOfWeekIso;

        if ($weekday >= 6) continue;
        if (isset($holidayMap[$tglStr])) continue;
        if (isset($mapIzin[$tglStr])) continue;

        $row = $mapPres[$tglStr] ?? null;
        if (!$row) {
            $noRecordCount++;
            continue;
        }

        $hadAny = true;
        $penalty = $row->penalty_minutes;
        $penaltyActual = is_numeric($penalty) ? max(0, (int) $penalty) : $defaultMinutes;
        $totalPenalty += $penaltyActual;
    }

    if ($hadAny) {
        $menitPerBulan[$bln] = $totalPenalty + ($noRecordCount * $defaultMinutes);
        $bulanDenganData[] = $bln;
        
        echo "Bulan $bln: {$menitPerBulan[$bln]} menit (ada data absen)\n";
        echo "  - Penalty dari absen: {$totalPenalty} menit\n";
        echo "  - Hari tanpa record: {$noRecordCount} x {$defaultMinutes} = " . ($noRecordCount * $defaultMinutes) . " menit\n";
    } else {
        $menitPerBulan[$bln] = 0;
        echo "Bulan $bln: {$menitPerBulan[$bln]} menit (tidak ada data)\n";
    }
}

echo "\n=== TOTAL AKUMULASI ===\n";
$totalSemuaBulan = array_sum($menitPerBulan);
$totalHanyaBulanDenganData = array_sum(array_intersect_key($menitPerBulan, array_flip($bulanDenganData)));

echo "Total SEMUA bulan: {$totalSemuaBulan} menit\n";
echo "Total HANYA bulan dengan data: {$totalHanyaBulanDenganData} menit\n";
echo "Bulan dengan data: " . implode(', ', $bulanDenganData) . "\n";

$fmtHariJamMenit = function($menit) {
    $hari = intdiv($menit, 450);
    $sisa = $menit % 450;
    $jam = intdiv($sisa, 60);
    $mnt = $sisa % 60;
    return sprintf('%d hari %d jam %d menit', $hari, $jam, $mnt);
};

echo "\nFormat total semua: " . $fmtHariJamMenit($totalSemuaBulan) . "\n";
echo "Format hanya dengan data: " . $fmtHariJamMenit($totalHanyaBulanDenganData) . "\n";

echo "\n=== REKOMENDASI ===\n";
if ($totalSemuaBulan != $totalHanyaBulanDenganData) {
    echo "❌ Saat ini menghitung SEMUA bulan (termasuk bulan tanpa data)\n";
    echo "✅ Sebaiknya hanya menghitung bulan yang ada datanya saja\n";
    echo "💡 Selisih: " . ($totalSemuaBulan - $totalHanyaBulanDenganData) . " menit\n";
} else {
    echo "✅ Total sama, tidak ada bulan kosong yang terhitung\n";
}

echo "\n=== END DEBUG ===\n";
