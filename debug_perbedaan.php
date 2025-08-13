<?php

require_once 'vendor/autoload.php';

use App\Models\Karyawan;
use App\Models\Holiday;
use App\Exports\RekapAbsensiBulananExport;
use App\Exports\RekapAbsensiTahunanExport;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

// Simulasi environment Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bulan = 5;  // Mei 2025
$tahun = 2025;

echo "=== INVESTIGASI PERBEDAAN DATA ===\n";
echo "Bulan: $bulan, Tahun: $tahun\n\n";

// Test dengan pegawai yang memiliki data absensi
$pegawaiTest = Karyawan::with([
    'absensi' => fn($q) => $q->whereYear('tanggal', $tahun)
                             ->whereMonth('tanggal', $bulan),
    'izins'   => fn($q) => $q->where(function($sub) use ($tahun, $bulan) {
        $sub->whereYear('tanggal_awal', $tahun)->whereMonth('tanggal_awal', $bulan)
            ->orWhereYear('tanggal_akhir', $tahun)->whereMonth('tanggal_akhir', $bulan);
    }),
    'nonaktif_terbaru',
])->get()->filter(fn($k) => !$k->nonaktifPadaBulan($tahun, $bulan))
  ->filter(fn($k) => $k->absensi->count() > 0)
  ->first();

if (!$pegawaiTest) {
    echo "Tidak ada pegawai dengan data absensi\n";
    exit;
}

echo "Pegawai test: {$pegawaiTest->nama}\n";
echo "Jumlah record absensi bulan $bulan: " . $pegawaiTest->absensi->count() . "\n";
echo "Jumlah izin bulan $bulan: " . $pegawaiTest->izins->count() . "\n\n";

// === 1. Export Bulanan ===
echo "=== 1. EXPORT BULANAN ===\n";
$exportBulanan = new RekapAbsensiBulananExport($bulan, $tahun);
$viewBulanan = $exportBulanan->view();
$pegawaiListBulanan = $viewBulanan->getData()['pegawaiList'];
$pegawaiBulanan = $pegawaiListBulanan->firstWhere('nama', $pegawaiTest->nama);
echo "Total menit: {$pegawaiBulanan->total_menit}\n\n";

// === 2. Export Tahunan ===
echo "=== 2. EXPORT TAHUNAN ===\n";
$exportTahunan = new RekapAbsensiTahunanExport($tahun);
$viewTahunan = $exportTahunan->view();
$pegawaiListTahunan = $viewTahunan->getData()['karyawans'];
$pegawaiTahunan = $pegawaiListTahunan->firstWhere('nama', $pegawaiTest->nama);
$menitBulanIni = $pegawaiTahunan->menitPerBulan[$bulan] ?? 0;
echo "Total menit bulan $bulan: $menitBulanIni\n\n";

if ($pegawaiBulanan->total_menit != $menitBulanIni) {
    echo "❌ PERBEDAAN DITEMUKAN!\n";
    echo "Bulanan: {$pegawaiBulanan->total_menit}\n";
    echo "Tahunan: $menitBulanIni\n";
    echo "Selisih: " . abs($pegawaiBulanan->total_menit - $menitBulanIni) . "\n\n";
    
    // === 3. DEBUG DETAIL ===
    echo "=== 3. DEBUG DETAIL PER HARI ===\n";
    
    $defaultMinutes = 450;
    $daysInMonth = Carbon::create($tahun, $bulan)->daysInMonth;
    
    $holidayMap = Holiday::whereYear('tanggal', $tahun)
        ->whereMonth('tanggal', $bulan)
        ->get()
        ->keyBy(fn($h) => $h->tanggal->toDateString());
    
    $toCarbon = function (string $tgl, ?string $time): ?Carbon {
        if (!$time) return null;
        $time = trim($time);
        return str_contains($time, ' ')
            ? Carbon::parse($time)
            : Carbon::parse("$tgl " . substr($time, 0, 5));
    };
    
    // Map izin
    $mapIzin = [];
    foreach ($pegawaiTest->izins as $iz) {
        foreach (CarbonPeriod::create($iz->tanggal_awal, $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
            $mapIzin[$d->toDateString()] = strtok($iz->jenis_ijin, ' ');
        }
    }
    
    $mapPres = $pegawaiTest->absensi->keyBy(fn($p) => $p->tanggal->toDateString());
    
    echo "Data per hari untuk bulan $bulan:\n";
    $totalManual = 0;
    
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $tglStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
        $weekday = Carbon::parse($tglStr)->dayOfWeekIso;
        
        if ($weekday >= 6) {
            echo "  $d: SKIP (weekend)\n";
            continue;
        }
        
        if (isset($holidayMap[$tglStr])) {
            echo "  $d: SKIP (holiday)\n";
            continue;
        }
        
        if (isset($mapIzin[$tglStr])) {
            echo "  $d: SKIP (izin: {$mapIzin[$tglStr]})\n";
            continue;
        }
        
        $row = $mapPres[$tglStr] ?? null;
        $penalty = 0;
        
        if ($row) {
            if ($pegawaiTest->is_ob) {
                $in = $toCarbon($tglStr, $row->jam_masuk);
                $out = $toCarbon($tglStr, $row->jam_pulang);
                $complete = $in && $out && $out->gt($in);
                $penalty = $complete ? 0 : $defaultMinutes;
                echo "  $d: OB penalty=$penalty (in=" . ($in ? $in->format('H:i') : 'null') . 
                     ", out=" . ($out ? $out->format('H:i') : 'null') . ", complete=" . ($complete ? 'yes' : 'no') . ")\n";
            } else {
                $penalty = is_numeric($row->penalty_minutes) ? max(0, (int)$row->penalty_minutes) : $defaultMinutes;
                echo "  $d: Non-OB penalty=$penalty (penalty_minutes: {$row->penalty_minutes}, ket: {$row->keterangan})\n";
            }
        } else {
            $penalty = $defaultMinutes;
            echo "  $d: No record penalty=$penalty\n";
        }
        
        $totalManual += $penalty;
    }
    
    echo "\nTotal manual: $totalManual\n";
    echo "Bulanan export: {$pegawaiBulanan->total_menit}\n";
    echo "Tahunan export: $menitBulanIni\n";
    
    if ($totalManual == $pegawaiBulanan->total_menit && $totalManual == $menitBulanIni) {
        echo "✅ Semua perhitungan manual sama\n";
    } else {
        echo "❌ Ada inkonsistensi dalam perhitungan\n";
    }
    
} else {
    echo "✅ DATA SAMA! Tidak ada masalah.\n";
}

echo "\n=== END INVESTIGATION ===\n";
