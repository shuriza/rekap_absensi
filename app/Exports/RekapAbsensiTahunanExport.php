<?php
namespace App\Exports;

use App\Models\Karyawan;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Holiday;


class RekapAbsensiTahunanExport implements FromView, WithEvents, ShouldAutoSize
{
    private int $defaultMinutes = 450;   // 7 jam 30 menit
    protected int $tahun;


    public function __construct(int $tahun)
    {
        $this->tahun = $tahun;
    }

    public function view(): View
    {
        $pegawaiList = Karyawan::with([
            'absensi' => fn($q) => $q->whereYear('tanggal', $this->tahun),
            'izins' => fn($q) => $q->where(function ($sub) {
                $sub->whereYear('tanggal_awal', $this->tahun)
                    ->orWhereYear('tanggal_akhir', $this->tahun);
            }),
            'nonaktif_terbaru',
        ])->get();

        // === DETEKSI BULAN AKTIF (BULAN YANG ADA KARYAWAN PUNYA DATA ABSEN) ===
        $bulanAktif = [];
        foreach ($pegawaiList as $pegTemp) {
            foreach ($pegTemp->absensi as $abs) {
                $bulanData = $abs->tanggal->month;
                $bulanAktif[$bulanData] = true;
            }
        }

        $toCarbon = function (string $tgl, ?string $time): ?Carbon {
            if (!$time) return null;
            return str_contains($time, ' ')
                ? Carbon::parse($time)
                : Carbon::parse("$tgl " . substr($time, 0, 5));
        };

        foreach ($pegawaiList as $peg) {
            $menitPerBulan = array_fill(1, 12, 0);
            $holidayMap = Holiday::whereYear('tanggal', $this->tahun)->get()
                                ->keyBy(fn($h) => $h->tanggal->toDateString());

            // map izin (skip hari yang di-izin-kan penuh)
            $mapIzin = [];
            foreach ($peg->izins as $iz) {
                foreach (CarbonPeriod::create($iz->tanggal_awal, $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
                    $mapIzin[$d->toDateString()] = true;
                }
            }

            $mapPres = $peg->absensi->keyBy(fn($p) => $p->tanggal->toDateString());

            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $daysInMonth = Carbon::create($this->tahun, $bulan)->daysInMonth;
                $hadAny = false;      // ada minimal satu record presensi di bulan ini?
                $noRecordCount = 0;   // jumlah hari kerja tanpa record

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $tglStr  = sprintf('%04d-%02d-%02d', $this->tahun, $bulan, $d);
                    $weekday = Carbon::parse($tglStr)->dayOfWeekIso; // 6=Sabtu 7=Minggu

                    // Lewati weekend, tanggal merah & izin penuh
                    if ($weekday >= 6) continue;
                    if (isset($holidayMap[$tglStr])) continue;
                    if (isset($mapIzin[$tglStr])) continue;

                    $row = $mapPres[$tglStr] ?? null;
                    if (!$row) {            // tidak ada record sama sekali
                        $noRecordCount++;   // ditambahkan 450 nanti HANYA jika hadAny=true
                        continue;
                    }

                    $hadAny = true;

                    // === MENGGUNAKAN ALGORITMA KEDISIPLINAN YANG SAMA DENGAN CONTROLLER & BULANAN ===
                    // SEMUA KARYAWAN (OB DAN NON-OB) MENGGUNAKAN PENALTY MINUTES UNTUK KEDISIPLINAN
                    $penalty = $row->penalty_minutes;
                    $menitPerBulan[$bulan] += is_numeric($penalty) ? max(0, (int) $penalty) : $this->defaultMinutes;
                }

                // Jika ada minimal satu record di bulan ini, hari kerja "tanpa record" dihitung 7,5 jam
                if ($hadAny) {
                    $menitPerBulan[$bulan] += $noRecordCount * $this->defaultMinutes;
                } else {
                    // === CEK APAKAH BULAN INI "AKTIF" (ADA KARYAWAN LAIN YANG PUNYA DATA) ===
                    if (isset($bulanAktif[$bulan])) {
                        // Bulan aktif: ada karyawan lain yang punya data di bulan ini
                        // Karyawan yang tidak masuk sama sekali → dihitung penalty full
                        $totalHariKerja = 0;
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $tglStr = sprintf('%04d-%02d-%02d', $this->tahun, $bulan, $d);
                            $weekday = Carbon::parse($tglStr)->dayOfWeekIso;
                            
                            // Hitung hari kerja (skip weekend, libur, izin)
                            if ($weekday >= 6) continue;
                            if (isset($holidayMap[$tglStr])) continue;
                            if (isset($mapIzin[$tglStr])) continue;
                            
                            $totalHariKerja++;
                        }
                        
                        // Penalty default untuk seluruh hari kerja di bulan ini
                        $menitPerBulan[$bulan] = $totalHariKerja * $this->defaultMinutes;
                    } else {
                        // Bulan tidak aktif: belum ada data siapa pun → tidak ditampilkan
                        $menitPerBulan[$bulan] = 0;
                    }
                }
            }

            // simpan menit untuk view
            $peg->menitPerBulan = $menitPerBulan;

            // tampilkan '—' jika 0; selain itu HH:MM
            $toHHMM = function (int $m) { return $m > 0 ? sprintf('%02d:%02d', intdiv($m,60), $m%60) : '—'; };
            $peg->rekap_tahunan = array_map($toHHMM, $menitPerBulan);

            // === TOTAL AKUMULASI DARI BULAN BERDATA DAN BULAN KOSONG DENGAN PENALTY (KONSISTEN DENGAN CONTROLLER) ===
            $totalAkumulasiHanyaBulanBerdata = 0;
            foreach ($menitPerBulan as $bln => $menit) {
                if ($menit > 0) {
                    // Bulan ini ada data ATAU bulan kosong dengan penalty default
                    $totalAkumulasiHanyaBulanBerdata += $menit;
                }
                // Hanya bulan yang benar-benar 0 yang tidak dimasukkan
            }

            $peg->total_menit = $totalAkumulasiHanyaBulanBerdata;
            
            // Format dengan basis 1440 menit (24 jam kalender) - konsisten dengan view web
            $hari = intdiv($totalAkumulasiHanyaBulanBerdata, 1440);
            $sisa = $totalAkumulasiHanyaBulanBerdata % 1440;
            $jam  = intdiv($sisa, 60);
            $mnt  = $sisa % 60;
            $peg->total_fmt = sprintf('%d hari %02d jam %02d menit', $hari, $jam, $mnt);
        }


        return view('exports.rekap_tahunan', [
            'karyawans' => $pegawaiList,
            'tahun' => $this->tahun,
        ]);
    }



    /**
     * Styling & page‑setup agar langsung pas di kertas A4 Landscape.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                /* ▸ PAGE SETUP */
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $sheet->getPageMargins()->setTop(0.3)
                                        ->setBottom(0.3)
                                        ->setLeft(0.25)
                                        ->setRight(0.25);

                $sheet->getHeaderFooter()
                    ->setOddFooter('&L&F&RPage &P of &N');

                /* ▸ INSERT TITLE ROW */
                $highestColumn = $sheet->getHighestDataColumn();
                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue('A1', 'REKAP ABSENSI TAHUN ' . $this->tahun);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                $headerRow  = 2;
                $highestRow = $sheet->getHighestDataRow();

                /* ▸ STYLE HEADER */
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setARGB('FFD9D9D9');
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                    ->getAlignment()->setHorizontal('center');

                /* ▸ BORDER */
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$highestRow}")
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FF000000'],
                            ],
                        ],
                    ]);

                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);

                /* ▸ COLOR GRADIENT BULANAN DINAMIS (10 STEP BERDASARKAN DATA AKTUAL) */
                
                // === LANGKAH 1: KUMPULKAN SEMUA NILAI MENIT DARI CELL YANG ADA ===
                $semuaMinit = [];
                
                // Scan semua cell data untuk mendapatkan nilai menit
                for ($row = 3; $row <= $highestRow; $row++) {
                    for ($colIndex = 3; $colIndex <= 14; $colIndex++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                        $cell = $colLetter . $row;
                        $value = $sheet->getCell($cell)->getValue();

                        // Skip jika nilai kosong atau '-'
                        if (!$value || $value === '—' || $value === '-') continue;

                        // Parse format "X hari Y jam Z menit" untuk mendapatkan total menit
                        if (preg_match('/(\d+) hari (\d+) jam (\d+) menit/', $value, $matches)) {
                            $hari = intval($matches[1]);
                            $jam = intval($matches[2]);
                            $menit = intval($matches[3]);
                            
                            // Konversi ke total menit (basis 1440 menit per hari)
                            $totalMinutes = ($hari * 1440) + ($jam * 60) + $menit;
                            
                            if ($totalMinutes > 0) {
                                $semuaMinit[] = $totalMinutes;
                            }
                        }
                    }
                }
                
                // === LANGKAH 2: HITUNG RANGE DINAMIS ===
                $minMinutes = 0; // Nilai minimum selalu 0
                $maxMinutes = !empty($semuaMinit) ? max($semuaMinit) : 1000; // Jika tidak ada data, gunakan 1000 sebagai default
                
                // === LANGKAH 3: BUAT 10 STEP GRADASI ===
                $steps = 10;
                $stepSize = ($maxMinutes - $minMinutes) / $steps;
                
                // 10 warna sky dari terang ke gelap
                $skyShades = [
                    'FFF0F9FF', // sky-50
                    'FFE0F2FE', // sky-100
                    'FFBAE6FD', // sky-200
                    'FF7DD3FC', // sky-300
                    'FF38BDF8', // sky-400
                    'FF0EA5E9', // sky-500
                    'FF0284C7', // sky-600
                    'FF0369A1', // sky-700
                    'FF075985', // sky-800
                    'FF0C4A6E', // sky-900
                ];

                // === LANGKAH 4: TERAPKAN WARNA BERDASARKAN RANGE DINAMIS ===
                for ($row = 3; $row <= $highestRow; $row++) {
                    for ($colIndex = 3; $colIndex <= 14; $colIndex++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                        $cell = $colLetter . $row;
                        $value = $sheet->getCell($cell)->getValue();

                        // Skip jika nilai kosong atau '-'
                        if (!$value || $value === '—' || $value === '-') continue;

                        // Parse format "X hari Y jam Z menit" untuk mendapatkan total menit
                        if (preg_match('/(\d+) hari (\d+) jam (\d+) menit/', $value, $matches)) {
                            $hari = intval($matches[1]);
                            $jam = intval($matches[2]);
                            $menit = intval($matches[3]);
                            
                            // Konversi ke total menit (basis 1440 menit per hari)
                            $totalMinutes = ($hari * 1440) + ($jam * 60) + $menit;
                            
                            // === HITUNG INDEX GRADASI BERDASARKAN RANGE DINAMIS ===
                            if ($totalMinutes <= $minMinutes) {
                                $idx = 0; // Warna paling terang
                            } elseif ($totalMinutes >= $maxMinutes) {
                                $idx = $steps - 1; // Warna paling gelap
                            } else {
                                // Hitung posisi dalam range (0-1)
                                $position = ($totalMinutes - $minMinutes) / ($maxMinutes - $minMinutes);
                                $idx = min((int) floor($position * $steps), $steps - 1);
                            }

                            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setARGB($skyShades[$idx]);
                        }
                    }
                }
            },
        ];
    }

}
