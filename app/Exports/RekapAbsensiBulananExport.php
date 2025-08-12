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
    /** 7 jam 30 menit untuk hari kerja tanpa record / data tidak lengkap */
    private int $defaultMinutes = 450;

    /** Nama bulan untuk judul */
    private string $namaBulan;

    public function __construct(private int $bulan, private int $tahun)
    {
        $this->namaBulan = [
            1 => 'Januari',  2 => 'Februari', 3 => 'Maret',   4 => 'April',
            5 => 'Mei',      6 => 'Juni',     7 => 'Juli',    8 => 'Agustus',
            9 => 'September',10 => 'Oktober', 11 => 'November',12 => 'Desember'
        ][$this->bulan] ?? 'Tidak Diketahui';
    }

    private array $tanggalList = [];
    private $pegawaiList;

    private function toCarbon(string $tgl, ?string $time): ?Carbon
    {
        if (!$time) return null;
        $time = trim($time);
        return str_contains($time, ' ')
            ? Carbon::parse($time)
            : Carbon::parse("$tgl " . substr($time, 0, 5));
    }

    public function view(): View
    {
        $daysInMonth       = Carbon::create($this->tahun, $this->bulan)->daysInMonth;
        $this->tanggalList = range(1, $daysInMonth);

        $holidayMap = Holiday::whereYear('tanggal', $this->tahun)
            ->whereMonth('tanggal', $this->bulan)
            ->get()
            ->keyBy(fn($h) => $h->tanggal->toDateString());

        $this->pegawaiList = Karyawan::with([
            'absensi' => fn($q) => $q->whereYear('tanggal', $this->tahun)
                                     ->whereMonth('tanggal', $this->bulan),
            'izins'   => fn($q) => $q->where(function ($sub) {
                $sub->whereYear('tanggal_awal', $this->tahun)->whereMonth('tanggal_awal', $this->bulan)
                    ->orWhereYear('tanggal_akhir', $this->tahun)->whereMonth('tanggal_akhir', $this->bulan);
            }),
            'nonaktif_terbaru',
        ])->get()->filter(fn($k) => !$k->nonaktifPadaBulan($this->tahun, $this->bulan));

        foreach ($this->pegawaiList as $peg) {
            // Map izin harian (label singkat utk tampilan sel)
            $mapIzin = [];
            foreach ($peg->izins as $iz) {
                foreach (CarbonPeriod::create($iz->tanggal_awal, $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
                    $mapIzin[$d->toDateString()] = strtok($iz->jenis_ijin, ' ');
                }
            }

            $mapPres  = $peg->absensi->keyBy(fn($p) => $p->tanggal->toDateString());
            $harian   = [];
            $totalMnt = 0; // === kedisiplinan bulan ini ===

            foreach ($this->tanggalList as $d) {
                $tglStr  = sprintf('%04d-%02d-%02d', $this->tahun, $this->bulan, $d);
                $weekday = Carbon::parse($tglStr)->dayOfWeekIso; // 6=Sabtu, 7=Minggu

                // Skip weekend
                if ($weekday >= 6) {
                    $harian[$d] = ['type' => 'libur', 'label' => $weekday === 6 ? 'Sabtu' : 'Minggu'];
                    continue;
                }

                // Skip tanggal merah
                if ($h = ($holidayMap[$tglStr] ?? null)) {
                    $harian[$d] = ['type' => 'libur', 'label' => $h->keterangan];
                    continue;
                }

                // Skip hari izin penuh (tidak menambah total kedisiplinan)
                if (isset($mapIzin[$tglStr])) {
                    $harian[$d] = ['type' => 'izin', 'label' => $mapIzin[$tglStr]];
                    continue;
                }

                $row = $mapPres[$tglStr] ?? null;

                if ($row) {
                    $in   = $this->toCarbon($tglStr, $row->jam_masuk);
                    $out  = $this->toCarbon($tglStr, $row->jam_pulang);
                    $ket  = strtolower(trim($row->keterangan ?? ''));

                    // tipe warna dasar (sinkron dgn UI)
                    $type = match ($ket) {
                        'tidak valid'        => 'tidak_valid',
                        'diluar waktu absen' => 'kosong',
                        'terlambat'          => 'terlambat',
                        'pulang cepat'       => 'terlambat', // warna sama (kuning)
                        'tepat waktu'        => 'hadir',
                        default              => null,
                    };

                    // === OVERRIDE VISUAL untuk OB (total dihitung terpisah) ===
                    if ($peg->is_ob) {
                        if ($in && $out && $out->gt($in)) {
                            $type = 'hadir';
                        } elseif (($in && !$out) || (!$in && $out)) {
                            $type = 'tidak_valid';
                        } else {
                            $type = 'kosong';
                        }
                    }

                    // label tampilan jam
                    $label = ($in || $out)
                        ? sprintf('%s – %s', $in ? $in->format('H:i') : '--:--', $out ? $out->format('H:i') : '--:--')
                        : '-';

                    $harian[$d] = ['type' => $type ?? 'kosong', 'label' => $label];

                    // === AKUMULASI KEDISIPLINAN (MATCH dengan web RekapController::rekap) ===
                    if ($peg->is_ob) {
                        // OB: lengkap = 0, selain itu 450
                        $complete = $in && $out && $out->gt($in);
                        $totalMnt += $complete ? 0 : $this->defaultMinutes;
                    } else {
                        // Non-OB: pakai penalty_minutes tersimpan; fallback 450 bila null
                        $penalty = $row->penalty_minutes;
                        $totalMnt += is_numeric($penalty) ? max(0, (int) $penalty) : $this->defaultMinutes;
                    }
                } else {
                    // Hari kerja tanpa record → 7.5 jam
                    $harian[$d] = ['type' => 'kosong', 'label' => '-'];
                    $totalMnt  += $this->defaultMinutes;
                }
            }

            $peg->absensi_harian = $harian;
            $peg->total_menit    = $totalMnt; // total kedisiplinan bulan ini
        }

        return view('exports.rekap_bulanan_excel', [
            'pegawaiList' => $this->pegawaiList,
            'tanggalList' => $this->tanggalList,
            'bulan'       => $this->bulan,
            'tahun'       => $this->tahun,
            'namaBulan'   => $this->namaBulan,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Judul
                $sheet->setCellValue('A1', "Rekap Absensi Bulan {$this->namaBulan} {$this->tahun}");
                $sheet->mergeCells('A1:' . Coordinate::stringFromColumnIndex(2 + count($this->tanggalList) + 1) . '1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'],
                    ],
                ]);

                // Setup halaman
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(4)
                    ->setFitToHeight(1)
                    ->setColumnsToRepeatAtLeftByStartAndEnd('A', 'B');

                $sheet->freezePane('C3');
                $sheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.2)->setRight(0.2);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 2);

                $lastColIndex = 2 + count($this->tanggalList) + 1;
                $lastCol      = Coordinate::stringFromColumnIndex($lastColIndex);
                $lastRow      = $sheet->getHighestRow();
                $sheet->getPageSetup()->setPrintArea("A1:{$lastCol}{$lastRow}");

                // Heading & border
                $highestCol = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A2:{$highestCol}{$highestRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A2:{$highestCol}2")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'],
                    ],
                ]);

                $sheet->getStyle("A2:{$highestCol}{$highestRow}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Pewarnaan sel harian
                $firstDateColIndex = 3;
                $startRowIndex     = 3;

                foreach ($this->pegawaiList as $rowIdx => $peg) {
                    $rowNum = $startRowIndex + $rowIdx;

                    foreach ($peg->absensi_harian as $d => $info) {
                        $col   = Coordinate::stringFromColumnIndex($firstDateColIndex + $d - 1);
                        $cell  = "{$col}{$rowNum}";

                        $rgb = match ($info['type']) {
                            'kosong'       => 'FF5252', // merah
                            'tidak_valid'  => 'FF5252', // merah
                            'terlambat'    => 'FFF59D', // kuning
                            'izin'         => '90CAF9', // biru
                            'libur'        => 'E0E0E0', // abu
                            default        => null,      // hadir: tanpa warna
                        };

                        if ($rgb) {
                            $sheet->getStyle($cell)->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB($rgb);
                        }
                    }
                }

                // Lebar kolom
                $columnWidths = ['A' => 5, 'B' => 18];
                for ($i = 0; $i < count($this->tanggalList); $i++) {
                    $columnWidths[Coordinate::stringFromColumnIndex($i + 3)] = 12;
                }
                $columnWidths[Coordinate::stringFromColumnIndex(2 + count($this->tanggalList) + 1)] = 18;

                foreach ($columnWidths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            },
        ];
    }
}
