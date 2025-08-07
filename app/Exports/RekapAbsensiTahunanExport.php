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
        ])->get();

        $toCarbon = function (string $tgl, ?string $time): ?Carbon {
            if (!$time) return null;
            return str_contains($time, ' ')
                ? Carbon::parse($time)
                : Carbon::parse("$tgl " . substr($time, 0, 5));
        };

        foreach ($pegawaiList as $peg) {
            $menitPerBulan = array_fill(1, 12, 0);
            $holidayMap = Holiday::whereYear('tanggal', $this->tahun)->get()->keyBy(fn($h) => $h->tanggal->toDateString());

            $mapIzin = [];
            foreach ($peg->izins as $iz) {
                foreach (CarbonPeriod::create($iz->tanggal_awal, $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
                    $mapIzin[$d->toDateString()] = strtok($iz->jenis_ijin, ' ');
                }
            }

            $mapPres = $peg->absensi->keyBy(fn($p) => $p->tanggal->toDateString());

            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $daysInMonth = Carbon::create($this->tahun, $bulan)->daysInMonth;
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $tglStr = sprintf('%04d-%02d-%02d', $this->tahun, $bulan, $d);
                    $weekday = Carbon::parse($tglStr)->dayOfWeekIso;

                    if ($weekday === 6 || $weekday === 7 || isset($holidayMap[$tglStr])) continue;
                    if (isset($mapIzin[$tglStr])) continue;

                    $row = $mapPres[$tglStr] ?? null;
                    if ($row) {
                        $in = $toCarbon($tglStr, $row->jam_masuk);
                        $out = $toCarbon($tglStr, $row->jam_pulang);

                        if ($in && $out && $out->gt($in)) {
                            $menitPerBulan[$bulan] += $in->diffInMinutes($out);
                        } elseif (($in && !$out) || (!$in && $out)) {
                            $menitPerBulan[$bulan] += 450; // fallback default
                        }
                    } else {
                        $menitPerBulan[$bulan] += 450; // default untuk hari kosong
                    }
                }
            }

            // Format ke HH:MM
            $HHMM = fn(int $m) => sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            $peg->rekap_tahunan = array_map($HHMM, $menitPerBulan);

            $totMenit = array_sum($menitPerBulan);
            $peg->total_menit = $totMenit;

            $hari = floor($totMenit / (60 * 24));
            $sisa = $totMenit % (60 * 24);
            $jam = floor($sisa / 60);
            $menit = $sisa % 60;

            $peg->total_fmt = sprintf('%d hari %02d jam %02d menit', $hari, $jam, $menit);
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

                /* ▸ COLOR GRADIENT BULANAN */
                $maxHours = 180;
                $minHours = 160;
                $steps    = 8;
                $stepSize = ceil(($maxHours - $minHours) / $steps);

                $skyShades = [
                    'FFBAE6FD', // sky-200
                    'FF7DD3FC', // sky-300
                    'FF38BDF8', // sky-400
                    'FF0EA5E9', // sky-500
                    'FF0284C7', // sky-600
                    'FF0369A1', // sky-700
                    'FF075985', // sky-800
                    'FF0C4A6E', // sky-900
                ];

                // Mulai dari baris ke-3 (data) dan kolom ke-3 (C) s/d kolom ke-14 (N)
                for ($row = 3; $row <= $highestRow; $row++) {
                    for ($colIndex = 3; $colIndex <= 14; $colIndex++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                        $cell = $colLetter . $row;
                        $value = $sheet->getCell($cell)->getValue();

                        if (!$value || !str_contains($value, ':')) continue;

                        [$hh, $mm] = explode(':', $value);
                        $hours = intval($hh) + intval($mm) / 60;
                        $idx = max(0, min((int) floor(($hours - $minHours) / $stepSize), $steps - 1));

                        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($skyShades[$idx]);
                    }
                }
            },
        ];
    }

}