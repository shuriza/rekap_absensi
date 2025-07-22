<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

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
            new Sheets\IzinBulananDetailSheet($this->bulan, $this->tahun),
            new Sheets\IzinBulananSummarySheet($this->bulan, $this->tahun),
        ];
    }
}