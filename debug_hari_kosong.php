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
$bulan = 5; // Mei 2025
$defaultMinutes = 450;

echo "=== DEBUG DETAIL PERHITUNGAN MEI 2025 ===\n";

// Ambil karyawan dengan data absen di Mei
$karyawan = Karyawan::with([
    'absensi' => fn($q) => $q->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan),
])->first();

echo "Karyawan: {$karyawan->nama}\n";
echo "Total record absen di Mei: {$karyawan->absensi->count()}\n\n";

$holidayMap = Holiday::whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)->get()
                    ->keyBy(fn($h) => $h->tanggal->toDateString());

$mapPres = $karyawan->absensi->keyBy(fn($p) => $p->tanggal->toDateString());

$daysInMonth = Carbon::create($tahun, $bulan)->daysInMonth;

echo "=== ANALISIS HARI KERJA MEI 2025 ===\n";

$hariKerja = 0;
$hariDenganRecord = 0;
$hariTanpaRecord = 0;
$totalPenaltyRiil = 0;
$totalDefault = 0;

for ($d = 1; $d <= $daysInMonth; $d++) {
    $tglStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
    $weekday = Carbon::parse($tglStr)->dayOfWeekIso;
    $carbon = Carbon::parse($tglStr);
    
    // Skip weekend dan libur
    if ($weekday >= 6) {
        echo "  {$carbon->format('d M')}: Weekend\n";
        continue;
    }
    if (isset($holidayMap[$tglStr])) {
        echo "  {$carbon->format('d M')}: Libur ({$holidayMap[$tglStr]->keterangan})\n";
        continue;
    }
    
    $hariKerja++;
    $row = $mapPres[$tglStr] ?? null;
    
    if ($row) {
        $hariDenganRecord++;
        $penalty = $row->penalty_minutes;
        $penaltyActual = is_numeric($penalty) ? max(0, (int) $penalty) : $defaultMinutes;
        $totalPenaltyRiil += $penaltyActual;
        
        echo "  {$carbon->format('d M')}: Ada record, penalty = {$penaltyActual} menit\n";
    } else {
        $hariTanpaRecord++;
        $totalDefault += $defaultMinutes;
        
        echo "  {$carbon->format('d M')}: TANPA RECORD, default = {$defaultMinutes} menit\n";
    }
}

echo "\n=== RINGKASAN ===\n";
echo "Hari kerja total: {$hariKerja}\n";
echo "Hari dengan record: {$hariDenganRecord}\n";
echo "Hari tanpa record: {$hariTanpaRecord}\n";
echo "Total penalty riil: {$totalPenaltyRiil} menit\n";
echo "Total default (hari kosong): {$totalDefault} menit\n";

$totalSaatIni = $totalPenaltyRiil + $totalDefault;
$totalHanyaData = $totalPenaltyRiil; // Hanya dari data yang benar-benar ada

echo "\n=== PERBANDINGAN METODE ===\n";
echo "Metode SAAT INI (penalty + default kosong): {$totalSaatIni} menit\n";
echo "Metode HANYA DATA (penalty saja): {$totalHanyaData} menit\n";
echo "Selisih: " . ($totalSaatIni - $totalHanyaData) . " menit\n";

$fmtHariJamMenit = function($menit) {
    $hari = intdiv($menit, 450);
    $sisa = $menit % 450;
    $jam = intdiv($sisa, 60);
    $mnt = $sisa % 60;
    return sprintf('%d hari %d jam %d menit', $hari, $jam, $mnt);
};

echo "\nFormat saat ini: " . $fmtHariJamMenit($totalSaatIni) . "\n";
echo "Format hanya data: " . $fmtHariJamMenit($totalHanyaData) . "\n";

echo "\n=== KESIMPULAN ===\n";
echo "❓ User ingin menghitung total akumulasi yang hanya dari data yang benar-benar ada?\n";
echo "💡 Ini berarti tidak menambahkan 450 menit untuk hari kerja tanpa record\n";
echo "🔧 Perlu ubah logic di controller untuk tidak menambah default pada hari kosong\n";

echo "\n=== END DEBUG ===\n";
