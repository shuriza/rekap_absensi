<?php

namespace App\Exports;

use App\Models\Karyawan;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Events\AfterSheet;

class RekapAbsensiBulananExport implements FromView, WithEvents, ShouldAutoSize
{
    /** jika jam masuk/pulang tidak lengkap → 450 menit */
    private int $defaultMinutes = 7 * 60 + 30;

    public function __construct(private int $bulan, private int $tahun) {}

    /* helper: time|datetime → Carbon|null */
    private function toCarbon(string $tgl, ?string $w): ?Carbon
    {
        return $w
            ? (str_contains($w, ' ') ? Carbon::parse($w)
                                     : Carbon::parse("$tgl $w"))
            : null;
    }

    public function view(): View
    {
        /* 1. Deret tanggal 1..N */
        $jumlahHari  = Carbon::create($this->tahun, $this->bulan)->daysInMonth;
        $tanggalList = range(1, $jumlahHari);

        /* 2. Map libur bulan ini (YYYY-MM-DD ⇒ keterangan) */
        $holidayMap = Holiday::whereYear('tanggal',  $this->tahun)
                             ->whereMonth('tanggal', $this->bulan)
                             ->get()
                             ->keyBy(fn ($h) => $h->tanggal->toDateString());

        /* 3. Ambil karyawan + presensi + izin satu bulan */
        $pegawaiList = Karyawan::with([
            'absensi' => fn ($q) => $q->whereYear('tanggal', $this->tahun)
                                      ->whereMonth('tanggal', $this->bulan),
            'izins'   => fn ($q) => $q->where(function ($sub) {
                $sub->whereYear('tanggal_awal',  $this->tahun)
                    ->whereMonth('tanggal_awal',  $this->bulan)
                    ->orWhereYear('tanggal_akhir', $this->tahun)
                    ->whereMonth('tanggal_akhir', $this->bulan);
            }),
        ])->get();

        /* 4. Proses per-pegawai */
        foreach ($pegawaiList as $peg) {

            /** a) Peta izin: YYYY-MM-DD ⇒ singkatan */
            $mapIzin = [];
            foreach ($peg->izins as $iz) {
                $period = CarbonPeriod::create(
                    $iz->tanggal_awal,
                    $iz->tanggal_akhir ?? $iz->tanggal_awal
                );
                foreach ($period as $d) {
                    $mapIzin[$d->toDateString()] = strtok($iz->jenis_ijin, ' ');
                }
            }

            /** b) Min & label per tanggal */
            $harian = array_fill_keys($tanggalList, '-');
            $total  = 0;

            $mapPres = $peg->absensi->keyBy(fn ($p) => $p->tanggal->toDateString());

            foreach ($tanggalList as $d) {
                $tglStr = sprintf('%04d-%02d-%02d', $this->tahun, $this->bulan, $d);

                /* ───────── libur */
                if ($h = $holidayMap[$tglStr] ?? null) {
                    $harian[$d] = Str::limit($h->keterangan, 15, '…');   // optional singkat
                    continue;
                }

                /* ───────── izin */
                if (isset($mapIzin[$tglStr])) {
                    $harian[$d] = $mapIzin[$tglStr];
                    continue;
                }

                /* ───────── presensi */
                $row = $mapPres[$tglStr] ?? null;
                if ($row) {
                    $in  = $this->toCarbon($tglStr, $row->jam_masuk);
                    $out = $this->toCarbon($tglStr, $row->jam_pulang);

                    if ($in && $out && $out->gt($in)) {
                        $total       += $in->diffInMinutes($out);
                        $harian[$d]   = $in->format('H:i').' - '.$out->format('H:i');
                    } elseif (($in && !$out) || (!$in && $out)) {
                        $total       += $this->defaultMinutes;
                        $harian[$d]   = ($in?->format('H:i') ?? '-').' - '.($out?->format('H:i') ?? '-');
                    }
                }
            }

            /* simpan ke model utk view Excel */
            $peg->absensi_harian = $harian;
            $peg->total_menit    = $total;
        }

        return view('exports.rekap_bulanan_excel', [
            'pegawaiList' => $pegawaiList,
            'tanggalList' => $tanggalList,
            'bulan'       => $this->bulan,
            'tahun'       => $this->tahun,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                /* 1️⃣  Orientasi & ukuran kertas */
                $sheet->getPageSetup()
                      ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                      ->setPaperSize(PageSetup::PAPERSIZE_A4);

                /* 2️⃣  Freeze header baris 1 */
                $sheet->freezePane('A2');

                /* 3️⃣  Ratakan teks + bungkus */
                $highestColumn = $sheet->getHighestColumn();
                $highestRow    = $sheet->getHighestRow();

                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                      ->getAlignment()
                      ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                      ->setVertical(Alignment::VERTICAL_CENTER)
                      ->setWrapText(true);

                /* 4️⃣  Tebal header + warna */
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                /* 5️⃣  Border tipis seluruh tabel */
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                      ->getBorders()
                      ->getAllBorders()
                      ->setBorderStyle(Border::BORDER_THIN);

                /* 6️⃣  Skala agar muat A4 (opsional) */
                $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
            },
        ];
    }
}
