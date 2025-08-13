<?php

require_once 'vendor/autoload.php';

use App\Models\Karyawan;
use App\Models\Holiday;
use App\Exports\RekapAbsensiTahunanExport;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tahun = 2025;

echo "=== DEBUGGING ALGORITMA TAHUNAN ===\n";
echo "Tahun: $tahun\n\n";

$defaultMinutes = 7 * 60 + 30; // 450 menit

// === Simulasi Controller Logic ===
echo "1. ALGORITMA CONTROLLER (Web)\n";
echo "==============================\n";

$pegawaiQuery = Karyawan::with([
    'absensi' => fn($q) => $q->whereYear('tanggal', $tahun),
    'izins'   => fn($q) => $q->where(function ($sub) use ($tahun) {
        $sub->whereYear('tanggal_awal', $tahun)
            ->orWhereYear('tanggal_akhir', $tahun);
    }),
])->take(1);

$pegawaiList = $pegawaiQuery->get();
$holidayMap = Holiday::whereYear('tanggal', $tahun)->get()
    ->keyBy(fn($h) => $h->tanggal->toDateString());

$toCarbon = function (string $tgl, ?string $time): ?Carbon {
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

$testPegawai = $pegawaiList->first();
if (!$testPegawai) {
    echo "Tidak ada pegawai untuk test\n";
    exit;
}

echo "Pegawai test: {$testPegawai->nama}\n";

// peta izin setahun
$mapIzin = [];
foreach ($testPegawai->izins as $iz) {
    foreach (CarbonPeriod::create($iz->tanggal_awal, $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
        $mapIzin[$d->toDateString()] = true;
    }
}

// peta presensi setahun
$mapPres = $testPegawai->absensi->keyBy(fn($p) => $p->tanggal->toDateString());
$menitPerBulanController = array_fill(1, 12, 0);

for ($bln = 1; $bln <= 12; $bln++) {
    $daysInMonth   = Carbon::create($tahun, $bln)->daysInMonth;
    $hadAny        = false;   // ada minimal 1 record di bulan ini?
    $noRecordCount = 0;       // jumlah hari kerja tanpa record

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $tglStr  = sprintf('%04d-%02d-%02d', $tahun, $bln, $d);
        $weekday = Carbon::parse($tglStr)->dayOfWeekIso; // 6=Sabtu 7=Minggu

        // lewati Sabtu/Minggu, tanggal merah, izin penuh
        if ($weekday >= 6)               continue;
        if (isset($holidayMap[$tglStr])) continue;
        if (isset($mapIzin[$tglStr]))    continue;

        $row = $mapPres[$tglStr] ?? null;

        if ($row) {
            $hadAny = true;

            if ($testPegawai->is_ob) {
                // OB: in–out lengkap = 0; tidak lengkap = 450
                $in  = $toCarbon($tglStr, $row->jam_masuk);
                $out = $toCarbon($tglStr, $row->jam_pulang);
                $complete = $in && $out && $out->gt($in);
                $pen = $complete ? 0 : $defaultMinutes;
            } else {
                // non-OB: pakai penalty_minutes; fallback 450 bila null
                $pen = $row->penalty_minutes;
                $pen = is_numeric($pen) ? max(0, (int)$pen) : $defaultMinutes;
            }

            $menitPerBulanController[$bln] += $pen;
        } else {
            // JANGAN langsung tambah 450 di sini
            $noRecordCount++;
        }
    }

    if ($hadAny) {
        // bulan ini ada data → hari kerja tanpa record dihitung 7:30
        $menitPerBulanController[$bln] += $noRecordCount * $defaultMinutes;
    } else {
        // benar-benar tanpa data sebulan penuh → 0
        $menitPerBulanController[$bln] = 0;
    }
}

$totalController = array_sum($menitPerBulanController);
echo "Total menit (Controller): $totalController\n";

// === Test Export Logic ===
echo "\n2. ALGORITMA EXPORT\n";
echo "===================\n";

$export = new RekapAbsensiTahunanExport($tahun);
$view = $export->view();
$exportPegawaiList = $view->getData()['karyawans'];

$exportPegawai = $exportPegawaiList->firstWhere('nama', $testPegawai->nama);
if (!$exportPegawai) {
    echo "Pegawai tidak ditemukan dalam export!\n";
    exit;
}

echo "Total menit (Export): {$exportPegawai->total_menit}\n";

// === Compare ===
echo "\n=== PERBANDINGAN ALGORITMA ===\n";
if ($totalController == $exportPegawai->total_menit) {
    echo "✅ ALGORITMA SAMA - Perhitungan konsisten\n";
} else {
    echo "❌ ALGORITMA BERBEDA!\n";
    echo "Controller: $totalController\n";
    echo "Export: {$exportPegawai->total_menit}\n";
    echo "Selisih: " . abs($totalController - $exportPegawai->total_menit) . "\n";
    
    // Detail per bulan
    echo "\nDetail per bulan:\n";
    for ($bln = 1; $bln <= 12; $bln++) {
        $exportMenit = $exportPegawai->menitPerBulan[$bln] ?? 0;
        $controllerMenit = $menitPerBulanController[$bln];
        $status = $exportMenit == $controllerMenit ? '✅' : '❌';
        echo "  Bulan $bln: Export=$exportMenit, Controller=$controllerMenit $status\n";
    }
}

echo "\n=== RINGKASAN ===\n";
echo "📋 Algoritma perhitungan: " . ($totalController == $exportPegawai->total_menit ? "Konsisten" : "Berbeda") . "\n";
echo "🎨 Format tampilan: Sudah diperbaiki (tanpa padding)\n";
echo "📊 Basis perhitungan: 450 menit per hari kerja (sama)\n";

echo "\n=== END TEST ===\n";
