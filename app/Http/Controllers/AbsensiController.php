<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Absensi;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
                'file_excel.*'                  => 'required|mimes:xlsx,xls',
                'jam_masuk_min_senin'           => 'required|date_format:H:i',
                'jam_masuk_max_senin'           => 'required|date_format:H:i',
                'jam_pulang_min_senin'          => 'required|date_format:H:i',
                'jam_pulang_max_senin'          => 'required|date_format:H:i',
                'jam_masuk_min_jumat'           => 'required|date_format:H:i',
                'jam_masuk_max_jumat'           => 'required|date_format:H:i',
                'jam_pulang_min_jumat'          => 'required|date_format:H:i',
                'jam_pulang_max_jumat'          => 'required|date_format:H:i',
            ]);
        }

        $preview = [];
        $bulanTahunSet = [];

        $seninKamis = [
            'masuk_min'  => $request->input('jam_masuk_min_senin',  '07:00'),
            'masuk_max'  => $request->input('jam_masuk_max_senin',  '07:30'),
            'pulang_min' => $request->input('jam_pulang_min_senin', '15:30'),
            'pulang_max' => $request->input('jam_pulang_max_senin', '17:00'),
        ];

        $jumat = [
            'masuk_min'  => $request->input('jam_masuk_min_jumat',  '07:00'),
            'masuk_max'  => $request->input('jam_masuk_max_jumat',  '07:30'),
            'pulang_min' => $request->input('jam_pulang_min_jumat', '15:00'),
            'pulang_max' => $request->input('jam_pulang_max_jumat', '17:00'),
        ];

        if ($request->hasFile('file_excel')) {
            foreach ($request->file('file_excel') as $file) {
                $path  = $file->getRealPath();
                $data  = Excel::toArray([], $path);
                $sheet = $data[2] ?? [];
                $barisTanggal = $sheet[3] ?? [];

                // Ambil tahun & bulan dari C3
                $cellC3 = $sheet[2][2] ?? null;
                if (!$cellC3) {
                    return back()->with('error', 'Cell C3 tidak ditemukan.');
                }

                if (is_string($cellC3) && str_contains($cellC3, '~')) {
                    $tanggalAwal = explode('~', $cellC3)[0];
                    $baseDate = Carbon::parse(trim($tanggalAwal));
                } elseif (is_numeric($cellC3)) {
                    $baseDate = Date::excelToDateTimeObject($cellC3);
                } else {
                    $baseDate = Carbon::parse($cellC3);
                }

                $tahun = $baseDate->format('Y');
                $bulan = $baseDate->format('m');
                $bulanTahunSet[] = "$tahun-$bulan";

                for ($i = 4; $i < count($sheet); $i += 2) {
                    $infoRow = $sheet[$i];
                    $dataRow = $sheet[$i + 1] ?? [];

                    $nama       = $infoRow[10] ?? null;
                    $departemen = $infoRow[20] ?? null;
                    if (!$nama || !$departemen) {
                        continue;
                    }

                    for ($col = 1; $col <= 30; $col++) {
                        $tanggalKe = $barisTanggal[$col] ?? null;
                        if (!$tanggalKe) {
                            continue;
                        }

                        $raw = $dataRow[$col] ?? null;
                        if (!$raw) {
                            continue;
                        }

                        $jamList = [];
                        if (is_numeric($raw)) {
                            $jamList[] = Date::excelToDateTimeObject($raw)->format('H:i');
                        } elseif (is_string($raw)) {
                            preg_match_all('/\d{1,2}:\d{2}/', $raw, $m);
                            $jamList = $m[0] ?? [];
                        } else {
                            continue;
                        }

                        $tanggal = Carbon::createFromDate($tahun, $bulan, (int) $tanggalKe)->format('Y-m-d');
                        $dow     = Carbon::parse($tanggal)->dayOfWeekIso;

                        if ($dow >= 1 && $dow <= 4) {
                            $range = $seninKamis;
                        } elseif ($dow === 5) {
                            $range = $jumat;
                        } else {
                            continue;
                        }

                        $masukMin  = Carbon::createFromFormat('H:i', $range['masuk_min']);
                        $masukMax  = Carbon::createFromFormat('H:i', $range['masuk_max']);
                        $pulangMin = Carbon::createFromFormat('H:i', $range['pulang_min']);
                        $pulangMax = Carbon::createFromFormat('H:i', $range['pulang_max']);

                        foreach ($jamList as $j) {
                            $jamObj = Carbon::createFromFormat('H:i', trim($j));
                            if ($jamObj->betweenIncluded($masukMin, $masukMax)) {
                                $preview[] = [
                                    'nama'       => $nama,
                                    'departemen' => $departemen,
                                    'tanggal'    => $tanggal,
                                    'jam_masuk'  => trim($j),
                                    'jam_pulang' => null,
                                ];
                            }

                            if ($jamObj->betweenIncluded($pulangMin, $pulangMax)) {
                                $preview[] = [
                                    'nama'       => $nama,
                                    'departemen' => $departemen,
                                    'tanggal'    => $tanggal,
                                    'jam_masuk'  => null,
                                    'jam_pulang' => trim($j),
                                ];
                            }
                        }
                    }
                }
            }

            // ✅ Validasi: semua file harus dari bulan yang sama
            $bulanTahunUnik = array_unique($bulanTahunSet);
            if (count($bulanTahunUnik) > 1) {
                return back()->with('error', 'Bulan tidak sama antara file!');
            }

            // ✅ Hapus data lama dari bulan yang sama
            [$tahunHapus, $bulanHapus] = explode('-', $bulanTahunUnik[0]);
            Absensi::whereYear('tanggal', $tahunHapus)
                ->whereMonth('tanggal', $bulanHapus)
                ->delete();

            // --- MERGE jam_masuk & jam_pulang per (nama, departemen, tanggal) ---
            $merged = [];
            foreach ($preview as $row) {
                $key = "{$row['nama']}|{$row['departemen']}|{$row['tanggal']}";
                if (!isset($merged[$key])) {
                    $merged[$key] = [
                        'nama'       => $row['nama'],
                        'departemen' => $row['departemen'],
                        'tanggal'    => $row['tanggal'],
                        'jam_masuk'  => null,
                        'jam_pulang' => null,
                    ];
                }
                if (!empty($row['jam_masuk'])) {
                    $merged[$key]['jam_masuk'] = $row['jam_masuk'];
                }
                if (!empty($row['jam_pulang'])) {
                    $merged[$key]['jam_pulang'] = $row['jam_pulang'];
                }
            }
            $preview = array_values($merged);

            session(['preview_data' => $preview]);
        } else {
            $preview = session('preview_data', []);
        }

        if (count($preview) === 0) {
            return back()->with('success', 'Tidak ada data absensi yang bisa ditampilkan.');
        }

        $collection = collect($preview);

        if ($search = $request->input('search')) {
            $collection = $collection->filter(fn($item) =>
                stripos($item['nama'], $search) !== false
            );
        }

        switch ($request->input('sort_by')) {
            case 'nama_asc':
                $collection = $collection->sortBy('nama');
                break;
            case 'nama_desc':
                $collection = $collection->sortByDesc('nama');
                break;
            case 'tanggal_asc':
                $collection = $collection->sortBy('tanggal');
                break;
            case 'tanggal_desc':
                $collection = $collection->sortByDesc('tanggal');
                break;
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage     = 40;
        $currentItems= $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
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
                'nama'       => $row['nama'],
                'departemen' => $row['departemen'],
            ]);

            $exists = Absensi::where('karyawan_id', $karyawan->id)
                ->where('tanggal', $row['tanggal'])
                ->where(function($q) use ($row) {
                    $q->where('jam_masuk',  $row['jam_masuk'])
                      ->orWhere('jam_pulang', $row['jam_pulang']);
                })->exists();

            if (! $exists) {
                Absensi::create([
                    'karyawan_id' => $karyawan->id,
                    'tanggal'     => $row['tanggal'],
                    'jam_masuk'   => $row['jam_masuk'],
                    'jam_pulang'  => $row['jam_pulang'],
                ]);
            }
        }

        session()->forget('preview_data');

        return redirect()->route('absensi.index')
                         ->with('success', 'Semua data absensi berhasil disimpan!');
    }
}
