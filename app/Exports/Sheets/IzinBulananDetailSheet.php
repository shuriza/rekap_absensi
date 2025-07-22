<?php
namespace App\Exports\Sheets;

use App\Models\IzinPresensi;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, RegistersEventListeners, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class IzinBulananDetailSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    use RegistersEventListeners;

    protected int $bulan;
    protected int $tahun;
    private  int $rowNum = 0; // untuk kolom No

    public function __construct(int $bulan, int $tahun)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    /** Data mentah */
    public function collection(): Collection
    {
        return IzinPresensi::query()
            // gabung tabel karyawans supaya bisa order by nama
            ->join('karyawans', 'karyawans.id', '=', 'izin_presensi.karyawan_id')
            ->whereYear('tanggal_awal',  $this->tahun)
            ->whereMonth('tanggal_awal', $this->bulan)
            ->orderBy('karyawans.nama')
            ->select('izin_presensi.*')   // hindari kolom bentrok
            ->with('karyawan')
            ->get();
    }

    /** Headings */
    public function headings(): array
    {
        return ['No','Departemen','Nama','Tipe Izin','Tanggal Awal','Tanggal Akhir','Durasi (hari)','Jenis','Keterangan'];
    }

    /** Mapping tiap baris */
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

    /** Style header */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /** Freeze & filter */
    public static function afterSheet(AfterSheet $event): void
    {
        $event->sheet->freezePane('A2');   // bekukan header
        $event->sheet->getDelegate()->setAutoFilter($event->sheet->getDelegate()->calculateWorksheetDimension());
    }

    public function title(): string
    {
        return 'Detail';
    }
}