<?php

namespace App\Exports;

use App\Models\Karyawan;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Maatwebsite\Excel\Events\AfterSheet;

class RekapAbsensiBulananExport implements FromView, WithEvents
{
    private int $defaultMinutes = 450; // Default menit untuk hari tanpa data lengkap

    public function __construct(private int $bulan, private int $tahun) {}

    private array $tanggalList = [];
    private $pegawaiList;

    private function toCarbon(string $tgl, ?string $time): ?Carbon
    {
        if (!$time) return null;
        $time = trim($time);
        return str_contains($time, ' ')
            ? Carbon::parse($time)
            : Carbon::parse("$tgl ".substr($time, 0, 5));
    }

    public function view(): View
    {
        $daysInMonth = Carbon::create($this->tahun, $this->bulan)->daysInMonth;
        $this->tanggalList = range(1, $daysInMonth);

        $holidayMap = Holiday::whereYear('tanggal', $this->tahun)
            ->whereMonth('tanggal', $this->bulan)
            ->get()
            ->keyBy(fn($h) => $h->tanggal->toDateString());

        $this->pegawaiList = Karyawan::with([
            'absensi' => fn($q) => $q->whereYear('tanggal', $this->tahun)
                                      ->whereMonth('tanggal', $this->bulan),
            'izins' => fn($q) => $q->where(function ($sub) {
                $sub->whereYear('tanggal_awal', $this->tahun)
                    ->whereMonth('tanggal_awal', $this->bulan)
                    ->orWhereYear('tanggal_akhir', $this->tahun)
                    ->whereMonth('tanggal_akhir', $this->bulan);
            }),
        ])->get();

        foreach ($this->pegawaiList as $peg) {
            $mapIzin = [];
            foreach ($peg->izins as $iz) {
                foreach (CarbonPeriod::create($iz->tanggal_awal, $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
                    $mapIzin[$d->toDateString()] = strtok($iz->jenis_ijin, ' ');
                }
            }

            $mapPres = $peg->absensi->keyBy(fn($p) => $p->tanggal->toDateString());
            $harian = [];
            $totalMnt = 0;

            foreach ($this->tanggalList as $d) {
                $tglStr = sprintf('%04d-%02d-%02d', $this->tahun, $this->bulan, $d);
                $weekday = Carbon::parse($tglStr)->dayOfWeekIso;

                if ($weekday === 6 || $weekday === 7) {
                    $harian[$d] = ['type' => 'libur', 'label' => $weekday === 6 ? 'Sabtu' : 'Minggu'];
                    continue; // Tidak menambah total untuk hari libur
                }

                if ($h = $holidayMap[$tglStr] ?? null) {
                    $harian[$d] = ['type' => 'libur', 'label' => $h->keterangan];
                    continue; // Tidak menambah total untuk hari libur
                }

                if (isset($mapIzin[$tglStr])) {
                    $harian[$d] = ['type' => 'izin', 'label' => $mapIzin[$tglStr]];
                    $totalMnt += 0; // Izin tidak menambah total, sesuaikan jika perlu
                    continue;
                }

                $row = $mapPres[$tglStr] ?? null;
                if ($row) {
                    $in  = $this->toCarbon($tglStr, $row->jam_masuk);
                    $out = $this->toCarbon($tglStr, $row->jam_pulang);

                    if ($in && $out && $out->gt($in)) {
                        $totalMnt += $in->diffInMinutes($out);
                    } elseif (($in && !$out) || (!$in && $out)) {
                        $totalMnt += $this->defaultMinutes;
                    }

                    $harian[$d] = [
                        'type' => $in && $out ? ($in->format('H:i') > '07:30' ? 'terlambat' : 'hadir') : 'kosong',
                        'label' => ($in?->format('H:i') ?? '--:--').' - '.($out?->format('H:i') ?? '--:--'),
                    ];
                } else {
                    // Hari tanpa data absensi, tambahkan default jika diinginkan
                    $harian[$d] = ['type' => 'kosong', 'label' => '-'];
                    // Tambahkan defaultMinutes untuk hari tanpa absensi (opsional)
                    $totalMnt += $this->defaultMinutes; // Aktifkan baris ini jika ingin menghitung hari kosong
                }
            }

            $peg->absensi_harian = $harian;
            $peg->total_menit = $totalMnt; // Total akumulasi per karyawan untuk seluruh bulan
        }

        return view('exports.rekap_bulanan_excel', [
            'pegawaiList' => $this->pegawaiList,
            'tanggalList' => $this->tanggalList,
            'bulan'       => $this->bulan,
            'tahun'       => $this->tahun,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(4)
                    ->setFitToHeight(1)
                    ->setColumnsToRepeatAtLeftByStartAndEnd('A', 'B');

                $sheet->freezePane('C2');

                $sheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.2)->setRight(0.2);

                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

                $lastColIndex = 2 + count($this->tanggalList) + 1;
                $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);
                $lastRow = $sheet->getHighestRow();
                $sheet->getPageSetup()->setPrintArea("A1:{$lastCol}{$lastRow}");

                $highestCol = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A1:{$highestCol}{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'],
                    ],
                ]);

                $sheet->getStyle("A1:{$highestCol}{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $firstDateColIndex = 3;
                $startRowIndex = 2;

                foreach ($this->pegawaiList as $rowIdx => $peg) {
                    $rowNum = $startRowIndex + $rowIdx;

                    foreach ($peg->absensi_harian as $d => $info) {
                        $colIdx = $firstDateColIndex + $d - 1;
                        $col = Coordinate::stringFromColumnIndex($colIdx);
                        $cell = "{$col}{$rowNum}";

                        $rgb = match ($info['type']) {
                            'kosong'    => 'FF5252',
                            'terlambat' => 'FFF59D',
                            'izin'      => '90CAF9',
                            'libur'     => 'E0E0E0',
                            default     => null,
                        };

                        if ($rgb) {
                            $sheet->getStyle($cell)->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB($rgb);
                        }
                    }
                }

                $columnWidths = [
                    'A' => 5,
                    'B' => 18,
                ];

                for ($i = 0; $i < count($this->tanggalList); $i++) {
                    $col = Coordinate::stringFromColumnIndex($i + 3);
                    $columnWidths[$col] = 12;
                }

                $lastCol = Coordinate::stringFromColumnIndex(2 + count($this->tanggalList) + 1);
                $columnWidths[$lastCol] = 15;

                foreach ($columnWidths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            },
        ];
    }
}
