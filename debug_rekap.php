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

// Parameter test
$bulan = 8;  // Agustus
$tahun = 2025;

echo "=== DEBUGGING REKAP BULANAN ===\n";
echo "Bulan: $bulan, Tahun: $tahun\n\n";

// Test 1: Logika RekapController
echo "1. LOGIKA REKAP CONTROLLER (Web)\n";
echo "================================\n";

$defaultMinutes = 7 * 60 + 30; // 450 menit

$daysInMonth = Carbon::create($tahun, $bulan)->daysInMonth;
echo "Days in month: $daysInMonth\n";

$holidayMap = Holiday::whereYear('tanggal', $tahun)
    ->whereMonth('tanggal', $bulan)
    ->get()
    ->keyBy(fn($h) => $h->tanggal->toDateString());

echo "Holidays: " . $holidayMap->count() . "\n";

$pegawaiList = Karyawan::with([
    'absensi' => fn($q) => $q->whereYear('tanggal', $tahun)
                             ->whereMonth('tanggal', $bulan),
    'izins'   => fn($q) => $q->where(function($sub) use ($tahun, $bulan) {
        $sub->whereYear('tanggal_awal', $tahun)->whereMonth('tanggal_awal', $bulan)
            ->orWhereYear('tanggal_akhir', $tahun)->whereMonth('tanggal_akhir', $bulan);
    }),
    'nonaktif_terbaru',
])->take(3)->get()->filter(fn($k) => !$k->nonaktifPadaBulan($tahun, $bulan));

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

foreach ($pegawaiList as $idx => $peg) {
    echo "\nPegawai: {$peg->nama} (is_ob: " . ($peg->is_ob ? 'Ya' : 'Tidak') . ")\n";
    
    // Map izin: simpan objek utk kebutuhan modal
    $mapIzin = [];
    foreach ($peg->izins as $iz) {
        foreach (CarbonPeriod::create($iz->tanggal_awal, $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
            $mapIzin[$d->toDateString()] = $iz;
        }
    }
    echo "Days with izin: " . count($mapIzin) . "\n";

    // 5a. TOTAL (kedisiplinan) SE-BULAN
    $totalMenit = 0;
    $mapPres = $peg->absensi->keyBy(fn($p) => $p->tanggal->toDateString());
    echo "Attendance records: " . $mapPres->count() . "\n";

    $workDays = 0;
    $penalties = [];

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $tglStr  = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
        $weekday = Carbon::parse($tglStr)->dayOfWeekIso;   // 6 = Sabtu, 7 = Minggu

        // Lewati akhir pekan, tanggal merah, izin
        if ($weekday >= 6) continue;
        if (isset($holidayMap[$tglStr])) continue;
        if (isset($mapIzin[$tglStr])) continue;

        $workDays++;
        $row = $mapPres[$tglStr] ?? null;

        if ($row) {
            if ($peg->is_ob) {
                $in  = $toCarbon($tglStr, $row->jam_masuk);
                $out = $toCarbon($tglStr, $row->jam_pulang);
                $complete = $in && $out && $out->gt($in);
                $pen = $complete ? 0 : $defaultMinutes;   // OB: lengkap=0, selain itu 450
            } else {
                $pen = is_numeric($row->penalty_minutes)
                    ? max(0, (int)$row->penalty_minutes)        // Non-OB: pakai hasil hitung
                    : $defaultMinutes;                    // fallback 450 kalau null
            }
            $penalties[] = "$d: $pen (ada record - penalty: {$row->penalty_minutes})";
        } else {
            $pen = $defaultMinutes;                       // hari kerja tanpa record
            $penalties[] = "$d: $pen (no record)";
        }   

        $totalMenit += $pen;                                    // ⟵ tambahkan SEKALI
    }

    echo "Work days: $workDays\n";
    echo "Total menit (RekapController): $totalMenit\n";
    echo "Penalties breakdown:\n";
    foreach (array_slice($penalties, 0, 5) as $p) {
        echo "  $p\n";
    }
    if (count($penalties) > 5) {
        echo "  ... dan " . (count($penalties) - 5) . " hari lainnya\n";
    }
    
    if ($idx >= 2) break; // Cukup 3 pegawai pertama
}

echo "\n\n2. LOGIKA EXPORT\n";
echo "================\n";

// Test 2: Logika Export
$export = new RekapAbsensiBulananExport($bulan, $tahun);
$view = $export->view();
$exportPegawaiList = $view->getData()['pegawaiList'];

foreach ($exportPegawaiList->take(3) as $idx => $peg) {
    echo "\nPegawai: {$peg->nama} (is_ob: " . ($peg->is_ob ? 'Ya' : 'Tidak') . ")\n";
    echo "Total menit (Export): {$peg->total_menit}\n";
    
    // Hitung manual untuk verifikasi
    $tanggalList = range(1, Carbon::create($tahun, $bulan)->daysInMonth);
    $workDaysExport = 0;
    $totalMntManual = 0;
    
    foreach ($tanggalList as $d) {
        $info = $peg->absensi_harian[$d] ?? ['type' => 'kosong'];
        
        if (!in_array($info['type'], ['libur', 'izin'])) {
            $workDaysExport++;
            if ($info['type'] === 'kosong' || $info['type'] === 'tidak_valid') {
                $totalMntManual += 450;
            } else {
                // Ada record - cek penalty
                $tglStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                $row = $peg->absensi->keyBy(fn($p) => $p->tanggal->toDateString())[$tglStr] ?? null;
                if ($row) {
                    if ($peg->is_ob) {
                        $totalMntManual += 0; // Simplified - should check complete
                    } else {
                        $totalMntManual += is_numeric($row->penalty_minutes) ? max(0, (int)$row->penalty_minutes) : 450;
                    }
                } else {
                    $totalMntManual += 450;
                }
            }
        }
    }
    
    echo "Work days (Export): $workDaysExport\n";
    echo "Manual calculation: $totalMntManual\n";
    
    if ($peg->total_menit != $totalMntManual) {
        echo "❌ PERBEDAAN DITEMUKAN!\n";
    } else {
        echo "✅ Perhitungan cocok\n";
    }
    
    if ($idx >= 2) break;
}

echo "\n=== END DEBUGGING ===\n";
