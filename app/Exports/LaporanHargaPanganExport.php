<?php

namespace App\Exports;

use App\Models\HargaPangan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LaporanHargaPanganExport implements FromArray, WithHeadings
{
    protected string $from;
    protected string $to;

    /**
     * Terima rentang tanggal sebagai string 'YYYY-MM-DD'.
     */
    public function __construct(string $from, string $to)
    {
        $this->from = $from;
        $this->to   = $to;
    }

    /**
     * Bangun array dua dimensi untuk setiap baris Excel.
     *
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        // Ambil data dan relasi pangan
        $data = HargaPangan::with('pangan')
            ->whereBetween('tanggal', [$this->from, $this->to])
            ->orderBy('tanggal')
            ->get();

        // Buat daftar tanggal unik sebagai string 'YYYY-MM-DD'
        $dates = $data
            ->pluck('tanggal')
            ->map(fn($d) => substr((string)$d, 0, 10)) // atau $d->format('Y-m-d') jika sudah Carbon
            ->unique()
            ->sort()
            ->values();

        // Buat daftar nama pangan unik
        $panganNames = $data
            ->pluck('pangan.nama_pangan')
            ->unique()
            ->sort()
            ->values();

        $rows = [];
        foreach ($dates as $date) {
            // Mulai baris dengan kolom tanggal
            $row = [ $date ];

            // Untuk tiap pangan, cari harga di tanggal itu
            foreach ($panganNames as $nama) {
                $rec = $data->firstWhere(function($item) use ($date, $nama) {
                    // Bandingkan tanggal sebagai string 'YYYY-MM-DD'
                    $tgl = substr((string)$item->tanggal, 0, 10);
                    return $tgl === $date
                        && $item->pangan->nama_pangan === $nama;
                });

                $row[] = $rec
                    ? $rec->harga_pangan
                    : null;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Heading baris pertama: 'Tanggal' diikuti nama-nama pangan.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        // Ambil kembali data (atau simpan $panganNames sebagai properti)
        $data = HargaPangan::with('pangan')
            ->whereBetween('tanggal', [$this->from, $this->to])
            ->get();

        $panganNames = $data
            ->pluck('pangan.nama_pangan')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return array_merge(['Tanggal'], $panganNames);
    }
}
