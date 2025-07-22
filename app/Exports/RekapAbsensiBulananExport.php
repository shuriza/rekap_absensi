<?php

namespace App\Exports;

use App\Models\Karyawan;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class RekapAbsensiBulananExport implements FromView, ShouldAutoSize, WithEvents
{
    /** default 7 jam 30 menit (450 menit) */
    private int $defaultMinutes = 450;

    /* properti input */
    public function __construct(private int $bulan, private int $tahun) {}

    /* dipakai ulang di AfterSheet */
    private array $tanggalList   = [];
    private $pegawaiList;               // Collection

    /* ----------------------------------------------------------
     * Helper string/datetime → Carbon|null
     * -------------------------------------------------------- */
    private function toCarbon(string $tgl, ?string $time): ?Carbon
    {
        if (!$time) return null;
        $time = trim($time);

        return str_contains($time, ' ')
            ? Carbon::parse($time)                 // sudah full datetime
            : Carbon::parse("$tgl ".substr($time, 0, 5)); // HH:mm
    }

    /* =====================  VIEW  ===================== */
    public function view(): View
    {
        /** ➊ daftar tanggal 1..N */
        $daysInMonth  = Carbon::create($this->tahun, $this->bulan)->daysInMonth;
        $this->tanggalList = range(1, $daysInMonth);

        /** ➋ libur bulan ini */
        $holidayMap = Holiday::whereYear('tanggal',  $this->tahun)
                             ->whereMonth('tanggal', $this->bulan)
                             ->get()
                             ->keyBy(fn ($h) => $h->tanggal->toDateString());

        /** ➌ ambil data pegawai-absen-izin */
        $this->pegawaiList = Karyawan::with([
            'absensi' => fn ($q) => $q->whereYear('tanggal', $this->tahun)
                                      ->whereMonth('tanggal', $this->bulan),
            'izins'   => fn ($q) => $q->where(function ($sub) {
                $sub->whereYear('tanggal_awal',  $this->tahun)
                    ->whereMonth('tanggal_awal',  $this->bulan)
                    ->orWhereYear('tanggal_akhir', $this->tahun)
                    ->whereMonth('tanggal_akhir', $this->bulan);
            }),
        ])->get();

        /** ➍ proses tiap pegawai */
        foreach ($this->pegawaiList as $peg) {

            // peta izin per tanggal
            $mapIzin = [];
            foreach ($peg->izins as $iz) {
                foreach (CarbonPeriod::create(
                        $iz->tanggal_awal,
                        $iz->tanggal_akhir ?? $iz->tanggal_awal) as $d) {
                    $mapIzin[$d->toDateString()] = strtok($iz->jenis_ijin, ' ');
                }
            }

            $mapPres   = $peg->absensi->keyBy(fn ($p) => $p->tanggal->toDateString());
            $harian    = [];
            $totalMnt  = 0;

            foreach ($this->tanggalList as $d) {
                $tglStr  = sprintf('%04d-%02d-%02d', $this->tahun, $this->bulan, $d);
                $weekday = Carbon::parse($tglStr)->dayOfWeekIso; // 6=Sabtu 7=Minggu

                // sabtu / minggu
                if ($weekday === 6 || $weekday === 7) {
                    $harian[$d] = ['type'=>'libur','label'=> $weekday===6 ? 'Sabtu':'Minggu'];
                    continue;
                }

                // hari libur
                if ($h = $holidayMap[$tglStr] ?? null) {
                    $harian[$d] = ['type'=>'libur','label'=>$h->keterangan];
                    continue;
                }

                // izin
                if (isset($mapIzin[$tglStr])) {
                    $harian[$d] = ['type'=>'izin','label'=>$mapIzin[$tglStr]];
                    continue;
                }

                // presensi
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
                        'type'  => $in && $out ? ($in->format('H:i') > '07:30' ? 'terlambat':'hadir') : 'kosong',
                        'label' => ($in?->format('H:i') ?? '--:--').' - '.($out?->format('H:i') ?? '--:--'),
                    ];
                } else {
                    $harian[$d] = ['type'=>'kosong','label'=>'-'];
                }
            }

            $peg->absensi_harian = $harian;
            $peg->total_menit    = $totalMnt;
        }

        return view('exports.rekap_bulanan_excel', [
            'pegawaiList' => $this->pegawaiList,
            'tanggalList' => $this->tanggalList,
            'bulan'       => $this->bulan,
            'tahun'       => $this->tahun,
        ]);
    }

    /* =====================  AFTER-SHEET  ===================== */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                /* 1) orientasi & freeze header */
                $sheet->getPageSetup()
                      ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                      ->setPaperSize(PageSetup::PAPERSIZE_A4);

                $sheet->freezePane('A2');

                /* 2) style global */
                $highestRow    = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                      ->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                      ->setVertical(Alignment::VERTICAL_CENTER)
                      ->setWrapText(true);

                // header tebal + abu
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'],
                    ],
                ]);

                // border seluruh tabel
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                      ->getBorders()
                      ->getAllBorders()
                      ->setBorderStyle(Border::BORDER_THIN);

                /* 3) Pewarnaan dinamis kolom tanggal */
                // Kolom: A=No, B=Nama, maka tanggal 1 mulai kolom C (index 3)
                $firstDateColIndex = 3; // 1-based
                $startRowIndex     = 2; // data dimulai baris 2

                foreach ($this->pegawaiList as $rowIdx => $peg) {
                    $rowNum = $startRowIndex + $rowIdx; // baris aktual di sheet

                    foreach ($peg->absensi_harian as $d => $info) {
                        $colIdx  = $firstDateColIndex + $d - 1;           // index numerik
                        $col     = Coordinate::stringFromColumnIndex($colIdx);
                        $cell    = "{$col}{$rowNum}";

                        $rgb = match ($info['type']) {
                            'kosong'    => 'FF5252', // merah
                            'terlambat' => 'FFF59D', // kuning
                            'izin'      => '90CAF9', // biru
                            'libur'     => 'E0E0E0', // abu
                            default     => null,     // hadir → tanpa warna
                        };

                        if ($rgb) {
                            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)
                                  ->getStartColor()->setRGB($rgb);
                        }
                    }
                }
            },
        ];
    }
}
