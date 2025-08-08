<?php
namespace App\Exports;


use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use App\Models\IzinPresensi;
use App\Models\Karyawan;


class IzinBulananExport implements WithMultipleSheets
{
    protected int $bulan;
    protected int $tahun;

    public function __construct(int $bulan, int $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function sheets(): array
    {
        return [
            new IzinBulananDetailSheet($this->bulan, $this->tahun),
            new IzinBulananSummarySheet($this->bulan, $this->tahun),
        ];
    }
}

class IzinBulananDetailSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle, \Maatwebsite\Excel\Concerns\WithEvents
{
    protected int $bulan;
    protected int $tahun;
    private int $rowNum = 0;

    public function __construct(int $bulan, int $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function collection(): Collection
    {
        return IzinPresensi::query()
            ->join('karyawans', 'karyawans.id', '=', 'izin_presensi.karyawan_id')
            ->whereYear('tanggal_awal',  $this->tahun)
            ->whereMonth('tanggal_awal', $this->bulan)
            ->orderBy('karyawans.nama')
            ->select('izin_presensi.*')
            ->with('karyawan')
            ->get();
    }

    public function headings(): array
    {
        $bulanNama = Carbon::createFromDate($this->tahun, $this->bulan, 1)->translatedFormat('F');
        $judul = ["Laporan Izin Bulanan: {$bulanNama} {$this->tahun}", '', '', '', '', '', '', '', ''];
        $header = ['No','Departemen','Nama','Tipe Izin','Tanggal Awal','Tanggal Akhir','Durasi (hari)','Jenis','Keterangan'];
        return [$judul, $header];
    }

    public function map($izin): array
    {
        $durasi = 1;
        if ($izin->tanggal_akhir) {
            $durasi = Carbon::parse($izin->tanggal_awal)
                      ->diffInDays(Carbon::parse($izin->tanggal_akhir)) + 1;
        }
        return [
            ++$this->rowNum,
            $izin->karyawan->departemen,
            $izin->karyawan->nama,
            $izin->tipe_ijin,
            Carbon::parse($izin->tanggal_awal)->format('d-m-Y'),
            $izin->tanggal_akhir ? Carbon::parse($izin->tanggal_akhir)->format('d-m-Y') : '-',
            $durasi,
            $izin->jenis_ijin,
            $izin->keterangan,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A2:I2')->getFont()->setBold(true);
        return [];
    }

    public function title(): string
    {
        return 'Detail';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // PAGE SETUP
                $sheet->getPageSetup()
                      ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                      ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                      ->setFitToWidth(1)
                      ->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.3)
                                         ->setBottom(0.3)
                                         ->setLeft(0.25)
                                         ->setRight(0.25);
                $sheet->getHeaderFooter()->setOddFooter('&L&F&RPage &P of &N');

                // TITLE ROW (already merged in styles)
                // HEADER ROW
                $headerRow = 2;
                $highestColumn = $sheet->getHighestDataColumn();
                $highestRow = $sheet->getHighestDataRow();

                // HEADER STYLE
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                      ->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                      ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                   ->getStartColor()->setARGB('FFD9D9D9');
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                      ->getAlignment()->setHorizontal('center');

                // BORDER TO TABLE
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$highestRow}")
                      ->applyFromArray([
                          'borders' => [
                              'allBorders' => [
                                  'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                  'color'       => ['argb' => 'FF000000'],
                              ],
                          ],
                      ]);

                // REPEAT HEADER ON EACH PAGE
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);
            },
        ];
    }
}

class IzinBulananSummarySheet implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle, \Maatwebsite\Excel\Concerns\WithEvents
{
    protected int $bulan;
    protected int $tahun;

    public function __construct(int $bulan, int $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function collection(): Collection
    {
        return Karyawan::whereHas('izins', function ($q) {
                $q->whereYear('tanggal_awal',  $this->tahun)
                  ->whereMonth('tanggal_awal', $this->bulan);
            })
            ->with(['izins' => function ($q) {
                $q->whereYear('tanggal_awal',  $this->tahun)
                  ->whereMonth('tanggal_awal', $this->bulan);
            }])
            ->orderBy('nama')
            ->get()
            ->map(function ($k) {
                $totalHari = $k->izins->sum(function ($izin) {
                    return ($izin->tanggal_akhir)
                        ? Carbon::parse($izin->tanggal_awal)->diffInDays($izin->tanggal_akhir) + 1
                        : 1;
                });

                return [
                    'Departement'    => $k->departemen,
                    'Nama'            => $k->nama,
                    'PENUH'      => $k->izins->where('tipe_ijin', 'PENUH')->count(),
                    'PARSIAL'   => $k->izins->where('tipe_ijin', 'PARSIAL')->count(),
                    'TERLAMBAT'       => $k->izins->where('tipe_ijin', 'TERLAMBAT')->count(),
                    'PULANG CEPAT'    => $k->izins->where('tipe_ijin', 'PULANG CEPAT')->count(),
                    'LAINNYA' => $k->izins->where('tipe_ijin', 'LAINNYA')->count(),
                    'Total Hari Izin' => $totalHari,
                ];
            });
    }

    public function headings(): array
    {
        $bulanNama = Carbon::createFromDate($this->tahun, $this->bulan, 1)->translatedFormat('F');
        $judul = ["Laporan Izin Bulanan: {$bulanNama} {$this->tahun}", '', '', '', '', '', ''];
        $header = ['Departement','Nama','PENUH','PARSIAL','TERLAMBAT','PULANG CEPAT','LAINNYA','Total Hari Izin'];
        return [$judul, $header];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A2:G2')->getFont()->setBold(true);
        return [];
    }

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // PAGE SETUP
                $sheet->getPageSetup()
                      ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                      ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                      ->setFitToWidth(1)
                      ->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.3)
                                         ->setBottom(0.3)
                                         ->setLeft(0.25)
                                         ->setRight(0.25);
                $sheet->getHeaderFooter()->setOddFooter('&L&F&RPage &P of &N');

                // TITLE ROW (already merged in styles)
                // HEADER ROW
                $headerRow = 2;
                $highestColumn = $sheet->getHighestDataColumn();
                $highestRow = $sheet->getHighestDataRow();

                // HEADER STYLE
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                      ->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                      ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                   ->getStartColor()->setARGB('FFD9D9D9');
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                      ->getAlignment()->setHorizontal('center');

                // BORDER TO TABLE
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$highestRow}")
                      ->applyFromArray([
                          'borders' => [
                              'allBorders' => [
                                  'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                  'color'       => ['argb' => 'FF000000'],
                              ],
                          ],
                      ]);

                // REPEAT HEADER ON EACH PAGE
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);
            },
        ];
    }
}