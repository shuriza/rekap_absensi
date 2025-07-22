<?php
namespace App\Exports\Sheets;

use App\Models\Karyawan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class IzinBulananSummarySheet implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
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
                    'Ijin Penuh'      => $k->izins->where('tipe_ijin', 'Ijin Penuh')->count(),
                    'Ijin Setengah'   => $k->izins->where('tipe_ijin', 'Ijin Setengah')->count(),
                    'Terlambat'       => $k->izins->where('tipe_ijin', 'Terlambat')->count(),
                    'Pulang Cepat'    => $k->izins->where('tipe_ijin', 'Pulang Cepat')->count(),
                    'Total Hari Izin' => $totalHari,
                ];
            });
    }

    public function headings(): array
    {
        return ['Departement','Nama','Ijin Penuh','Ijin Setengah','Terlambat','Pulang Cepat','Total Hari Izin'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Ringkasan';
    }
}