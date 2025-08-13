<?php

require_once 'vendor/autoload.php';

use App\Models\Karyawan;
use App\Models\Holiday;
use App\Exports\RekapAbsensiBulananExport;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test dengan pegawai yang punya data absensi
$bulan = 8;  // Agustus
$tahun = 2025;

echo "=== TEST DENGAN DATA RIIL ===\n";
echo "Bulan: $bulan, Tahun: $tahun\n\n";

// Cari pegawai yang ada data absensi
$pegawaiWithData = Karyawan::with([
    'absensi' => fn($q) => $q->whereYear('tanggal', $tahun)
                             ->whereMonth('tanggal', $bulan),
    'izins'   => fn($q) => $q->where(function($sub) use ($tahun, $bulan) {
        $sub->whereYear('tanggal_awal', $tahun)->whereMonth('tanggal_awal', $bulan)
            ->orWhereYear('tanggal_akhir', $tahun)->whereMonth('tanggal_akhir', $bulan);
    }),
    'nonaktif_terbaru',
])->get()
->filter(fn($k) => !$k->nonaktifPadaBulan($tahun, $bulan))
->filter(fn($k) => $k->absensi->count() > 0); // Yang ada data absensi

if ($pegawaiWithData->isEmpty()) {
    echo "Tidak ada pegawai dengan data absensi di bulan $bulan/$tahun\n";
    
    // Test dengan bulan lain
    for ($testBulan = 1; $testBulan <= 12; $testBulan++) {
        $testData = Karyawan::with([
            'absensi' => fn($q) => $q->whereYear('tanggal', $tahun)
                                     ->whereMonth('tanggal', $testBulan)
        ])->get()->filter(fn($k) => $k->absensi->count() > 0);
        
        if ($testData->isNotEmpty()) {
            echo "Ditemukan data di bulan $testBulan: " . $testData->count() . " pegawai\n";
            $bulan = $testBulan;
            $pegawaiWithData = $testData;
            break;
        }
    }
}

if ($pegawaiWithData->isEmpty()) {
    echo "Tidak ada data absensi sama sekali di tahun $tahun\n";
    exit;
}

echo "Ditemukan " . $pegawaiWithData->count() . " pegawai dengan data absensi\n\n";

// === Test 1: Controller Logic ===
$defaultMinutes = 7 * 60 + 30; // 450 menit
$daysInMonth = Carbon::create($tahun, $bulan)->daysInMonth;

$holidayMap = Holiday::whereYear('tanggal', $tahun)
    ->whereMonth('tanggal', $bulan)
    ->get()
    ->keyBy(fn($h) => $h->tanggal->toDateString());

$toCarbon = function(string $tgl, ?string $time): ?Carbon {
    if (!$time) return null;
    $time = trim($time);
    if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $time)) {
        return Carbon::parse($time);
    }
    if (preg_match('/^\d{2}:\d{2}/', $time)) {
        return Carbon::parse("$tgl " . substr($time, 0, 5));
    }
    return null;
};

$firstPegawai = $pegawaiWithData->first();
echo "=== CONTROLLER LOGIC ===\n";
echo "Pegawai: {$firstPegawai->nama}\n";
echo "Jumlah record absensi: " . $firstPegawai->absensi->count() . "\n";

// Map izin
$mapIzin = [];
foreach ($firstPegawai->izins as $iz) {
    foreach (CarbonPeriod::create($iz->tanggal_awal, $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
        $mapIzin[$d->toDateString()] = $iz;
    }
}

$totalMenit = 0;
$mapPres = $firstPegawai->absensi->keyBy(fn($p) => $p->tanggal->toDateString());
$details = [];

for ($d = 1; $d <= $daysInMonth; $d++) {
    $tglStr  = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
    $weekday = Carbon::parse($tglStr)->dayOfWeekIso;

    // Lewati akhir pekan, tanggal merah, izin
    if ($weekday >= 6) {
        $details[] = "$d: SKIP (weekend)";
        continue;
    }
    if (isset($holidayMap[$tglStr])) {
        $details[] = "$d: SKIP (holiday)";
        continue;
    }
    if (isset($mapIzin[$tglStr])) {
        $details[] = "$d: SKIP (izin)";
        continue;
    }

    $row = $mapPres[$tglStr] ?? null;
    $pen = 0;

    if ($row) {
        if ($firstPegawai->is_ob) {
            $in  = $toCarbon($tglStr, $row->jam_masuk);
            $out = $toCarbon($tglStr, $row->jam_pulang);
            $complete = $in && $out && $out->gt($in);
            $pen = $complete ? 0 : $defaultMinutes;
        } else {
            $pen = is_numeric($row->penalty_minutes)
                ? max(0, (int)$row->penalty_minutes)
                : $defaultMinutes;
        }
        $details[] = "$d: penalty=$pen (penalty_minutes: {$row->penalty_minutes}, keterangan: {$row->keterangan})";
    } else {
        $pen = $defaultMinutes;
        $details[] = "$d: penalty=$pen (no record)";
    }

    $totalMenit += $pen;
}

echo "Total menit (Controller): $totalMenit\n";
echo "Sample details:\n";
foreach (array_slice($details, 0, 10) as $detail) {
    echo "  $detail\n";
}
echo "\n";

// === Test 2: Export Logic ===
echo "=== EXPORT LOGIC ===\n";
$export = new RekapAbsensiBulananExport($bulan, $tahun);
$view = $export->view();
$exportPegawaiList = $view->getData()['pegawaiList'];

$exportPegawai = $exportPegawaiList->firstWhere('nama', $firstPegawai->nama);
if (!$exportPegawai) {
    echo "Pegawai tidak ditemukan dalam export!\n";
    exit;
}

echo "Total menit (Export): {$exportPegawai->total_menit}\n";

// === Compare ===
if ($totalMenit == $exportPegawai->total_menit) {
    echo "✅ HASIL SAMA - Tidak ada masalah dalam algoritma\n";
} else {
    echo "❌ HASIL BERBEDA!\n";
    echo "Controller: $totalMenit\n";
    echo "Export: {$exportPegawai->total_menit}\n";
    echo "Selisih: " . abs($totalMenit - $exportPegawai->total_menit) . "\n";
}

echo "\n=== ANALISIS FORMAT TAMPILAN ===\n";

// Controller format (basis 450 menit per hari)
$ctrlHari = intdiv($totalMenit, 450);
$ctrlSisa = $totalMenit % 450;
$ctrlJam = intdiv($ctrlSisa, 60);
$ctrlMenit = $ctrlSisa % 60;
echo "Controller format: $ctrlHari hari $ctrlJam jam $ctrlMenit menit\n";

// Export format (dari template)
$expHari = intdiv($exportPegawai->total_menit, 450);
$expSisa = $exportPegawai->total_menit % 450;
$expJam = intdiv($expSisa, 60);
$expMnt = $expSisa % 60;
echo "Export format: $expHari hari " . sprintf('%02d', $expJam) . " jam " . sprintf('%02d', $expMnt) . " menit\n";

// View format (dari rekap.blade.php)
$viewHari = intdiv($totalMenit, 1440); // 24 jam = 1440 menit
$viewSisa = $totalMenit % 1440;
$viewJam = str_pad(intdiv($viewSisa, 60), 2, '0', STR_PAD_LEFT);
$viewMenit = str_pad($viewSisa % 60, 2, '0', STR_PAD_LEFT);
echo "View format: $viewHari hari $viewJam jam $viewMenit menit\n";

echo "\n❗ KEMUNGKINAN MASALAH:\n";
echo "1. Format tampilan berbeda (basis 450 vs 1440 menit per hari)\n";
echo "2. Padding format berbeda (02d vs tanpa padding)\n";
echo "3. Atau user melihat data dengan filter segment yang berbeda\n";

echo "\n=== END TEST ===\n";
