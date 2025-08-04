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

class RekapAbsensiTahunanExport implements FromView, WithEvents, ShouldAutoSize
{
    protected int $tahun;

    public function __construct(int $tahun)
    {
        $this->tahun = $tahun;
    }

    public function view(): View
    {
        // Ambil data absensi karyawan khusus tahun diminta
        $karyawans = Karyawan::with(['absensi' => function ($q) {
            $q->whereYear('tanggal', $this->tahun);
        }])->get();

        // Hitung akumulasi menit per-bulan & total
        foreach ($karyawans as $pegawai) {
            $rekapBulanan = array_fill(1, 12, 0);
            $totalMenit   = 0;

            foreach ($pegawai->absensi as $absen) {
                $bulan      = (int) Carbon::parse($absen->tanggal)->month;
                $selisih    = 0;
                $hasMasuk   = !empty($absen->jam_masuk);
                $hasPulang  = !empty($absen->jam_pulang);

                if ($hasMasuk && $hasPulang) {
                    $masuk  = Carbon::parse($absen->jam_masuk);
                    $pulang = Carbon::parse($absen->jam_pulang);
                    if ($pulang->lessThanOrEqualTo($masuk)) {
                        $pulang->addDay();      // shift malam
                    }
                    $selisih = $masuk->diffInMinutes($pulang, false);
                } elseif ($hasMasuk || $hasPulang) {
                    $selisih = 450;              // fallback 7 j 30 m
                }

                if ($selisih > 0 && $selisih <= 1440) {
                    $rekapBulanan[$bulan] += $selisih;
                    $totalMenit           += $selisih;
                }
            }

            $pegawai->rekap_tahunan = $rekapBulanan;
            $pegawai->total_menit   = $totalMenit;
        }

        return view('exports.rekap_tahunan', [
            'karyawans' => $karyawans,
            'tahun'     => $this->tahun,
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
                      ->setFitToWidth(1)   // muat 1 halaman lebar
                      ->setFitToHeight(0);

                $sheet->getPageMargins()->setTop(0.3)
                                         ->setBottom(0.3)
                                         ->setLeft(0.25)
                                         ->setRight(0.25);

                $sheet->getHeaderFooter()
                      ->setOddFooter('&L&F&RPage &P of &N');

                /* ▸ INSERT TITLE ROW */
                $highestColumn = $sheet->getHighestDataColumn();
                $sheet->insertNewRowBefore(1, 1);                      // geser 1 baris ke bawah
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->setCellValue('A1', 'REKAP ABSENSI TAHUN '.$this->tahun);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                /* ▸ IDENTIFIKASI BARIS HEADER */
                $headerRow   = 2;                                       // setelah sisipan baris judul
                $highestRow  = $sheet->getHighestDataRow();

                /* ▸ STYLE HEADER */
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                      ->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                      ->getFill()->setFillType(Fill::FILL_SOLID)
                                   ->getStartColor()->setARGB('FFD9D9D9');
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$headerRow}")
                      ->getAlignment()->setHorizontal('center');

                /* ▸ BORDER KE SELURUH TABEL */
                $sheet->getStyle("A{$headerRow}:{$highestColumn}{$highestRow}")
                      ->applyFromArray([
                          'borders' => [
                              'allBorders' => [
                                  'borderStyle' => Border::BORDER_THIN,
                                  'color'       => ['argb' => 'FF000000'],
                              ],
                          ],
                      ]);

                /* ▸ ULANG HEADER DI SETIAP HALAMAN */
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);
            },
        ];
    }
}