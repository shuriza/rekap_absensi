<?php

// File: app/Http/Controllers/AbsensiController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Absensi;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AbsensiController extends Controller
{
    public function index()
    {
        return view('absensi.index');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file_excel.*' => 'required|mimes:xlsx,xls',
        ]);

        $preview = [];

        foreach ($request->file('file_excel') as $file) {
            $path = $file->getRealPath();
            $data = Excel::toArray([], $path);
            $sheet = $data[2] ?? [];
            $barisTanggal = $sheet[3] ?? [];

            for ($i = 4; $i < count($sheet); $i += 2) {
                $infoRow = $sheet[$i];
                $dataRow = $sheet[$i + 1] ?? [];

                $nama = $infoRow[10] ?? null;
                $departemen = $infoRow[20] ?? null;

                if (!$nama || !$departemen) continue;

                for ($col = 1; $col <= 30; $col++) {
                    $tanggalKe = $barisTanggal[$col] ?? null;
                    if (!$tanggalKe) continue;

                    $raw = $dataRow[$col] ?? null;
                    if ($raw && is_string($raw)) {
                        preg_match_all('/\d{2}:\d{2}/', $raw, $matches);
                        $jam = $matches[0];

                        $jamMasuk = null;
                        $jamPulang = null;

                        foreach ($jam as $j) {
                            $jamInt = (int) explode(':', $j)[0];
                            if ($jamInt < 12 && !$jamMasuk) {
                                $jamMasuk = $j;
                            }
                            if ($jamInt >= 12) {
                                $jamPulang = $j;
                            }
                        }

                        $tanggal = '2025-04-' . str_pad((int) $tanggalKe, 2, '0', STR_PAD_LEFT);

                        $preview[] = [
                            'nama' => $nama,
                            'departemen' => $departemen,
                            'tanggal' => $tanggal,
                            'jam_masuk' => $jamMasuk,
                            'jam_pulang' => $jamPulang,
                        ];
                    }
                }
            }
        }

        if (count($preview) === 0) {
            return back()->with('success', 'Tidak ada data absensi yang bisa ditampilkan.');
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 40;
        $collection = collect($preview);
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedPreview = new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('absensi.index', ['preview' => $paginatedPreview]);
    }

    public function store(Request $request)
    {
        $data = $request->input('data');

        foreach ($data as $row) {
            $karyawan = Karyawan::firstOrCreate([
                'nama' => $row['nama'],
                'departemen' => $row['departemen'],
            ]);

            $cek = Absensi::where('karyawan_id', $karyawan->id)
                          ->where('tanggal', $row['tanggal'])
                          ->first();

            if (!$cek) {
                Absensi::create([
                    'karyawan_id' => $karyawan->id,
                    'tanggal'     => $row['tanggal'],
                    'jam_masuk'   => $row['jam_masuk'],
                    'jam_pulang'  => $row['jam_pulang'],
                ]);
            }
        }

        return redirect()->route('absensi.index')->with('success', 'Data absensi berhasil disimpan!');
    }

    public function cetak()
    {
        $data = Absensi::with('karyawan')->orderBy('tanggal')->get();
        $pdf = Pdf::loadView('absensi.cetak', compact('data'));
        return $pdf->download('rekap-absensi.pdf');
    }
}
