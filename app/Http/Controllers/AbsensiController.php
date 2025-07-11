<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Absensi;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;

class AbsensiController extends Controller
{
    public function index()
    {
        return view('absensi.index');
    }

    public function preview(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'file_excel.*' => 'required|mimes:xlsx,xls',
                'jam_masuk_min' => 'required|date_format:H:i',
                'jam_masuk_max' => 'required|date_format:H:i',
                'jam_pulang_min' => 'required|date_format:H:i',
                'jam_pulang_max' => 'required|date_format:H:i',
            ]);
        }

        $preview = [];

        // Ambil jam batas dari user
        $jamMasukMin = $request->input('jam_masuk_min', '07:00');
        $jamMasukMax = $request->input('jam_masuk_max', '07:30');
        $jamPulangMin = $request->input('jam_pulang_min', '15:30');
        $jamPulangMax = $request->input('jam_pulang_max', '17:00');

        // Proses hanya jika ada file upload
        if ($request->hasFile('file_excel')) {
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

                            // Validasi jam
                            $isValid = true;
                            if ($jamMasuk && ($jamMasuk < $jamMasukMin || $jamMasuk > $jamMasukMax)) {
                                $isValid = false;
                            }
                            if ($jamPulang && ($jamPulang < $jamPulangMin || $jamPulang > $jamPulangMax)) {
                                $isValid = false;
                            }

                            if (!$isValid) continue;

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

            // Simpan ke session
            session(['preview_data' => $preview]);
        } else {
            // Ambil dari session saat GET (misalnya pagination)
            $preview = session('preview_data', []);
        }

        if (count($preview) === 0) {
            return back()->with('success', 'Tidak ada data absensi yang bisa ditampilkan.');
        }


        $collection = collect($preview);

        // Pencarian nama
        if ($search = $request->input('search')) {
            $collection = $collection->filter(function ($item) use ($search) {
                return stripos($item['nama'], $search) !== false;
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by');
        if ($sortBy === 'nama_asc') {
            $collection = $collection->sortBy('nama');
        } elseif ($sortBy === 'nama_desc') {
            $collection = $collection->sortByDesc('nama');
        } elseif ($sortBy === 'tanggal_asc') {
            $collection = $collection->sortBy('tanggal');
        } elseif ($sortBy === 'tanggal_desc') {
            $collection = $collection->sortByDesc('tanggal');
        }

        // Pagination
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 40;
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginatedPreview = new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('absensi.index', [
            'preview' => $paginatedPreview,
        ]);
    }

public function store(Request $request)
{
    $data = session('preview_data');

    if (!$data || !is_array($data)) {
        return back()->with('error', 'Tidak ada data yang bisa disimpan.');
    }

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

    // Hapus session preview setelah disimpan
    session()->forget('preview_data');

    return redirect()->route('absensi.index')->with('success', 'Semua data absensi berhasil disimpan!');
}
}
