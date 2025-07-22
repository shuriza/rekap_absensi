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
                'file_excel.*'                => 'required|mimes:xlsx,xls',
                'jam_masuk_min_senin'         => 'required|date_format:H:i',
                'jam_masuk_max_senin'         => 'required|date_format:H:i',
                'jam_pulang_min_senin'        => 'required|date_format:H:i',
                'jam_pulang_max_senin'        => 'required|date_format:H:i',
                'jam_masuk_min_jumat'         => 'required|date_format:H:i',
                'jam_masuk_max_jumat'         => 'required|date_format:H:i',
                'jam_pulang_min_jumat'        => 'required|date_format:H:i',
                'jam_pulang_max_jumat'        => 'required|date_format:H:i',
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
                    if (!$nama || !$departemen) continue;

                    for ($col = 1; $col <= 30; $col++) {
                        $tanggalKe = $barisTanggal[$col] ?? null;
                        $raw       = $dataRow[$col]      ?? null;
                        if (!$tanggalKe || !$raw) continue;

                        $jamList = [];
                        if (is_numeric($raw)) {
                            $jamList[] = Date::excelToDateTimeObject($raw)->format('H:i');
                        } elseif (is_string($raw)) {
                            preg_match_all('/\d{1,2}:\d{2}/', $raw, $m);
                            $jamList = $m[0] ?? [];
                        }
                        if (empty($jamList)) continue;

                        $tanggal = Carbon::createFromDate($tahun, $bulan, (int)$tanggalKe)->format('Y-m-d');
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
    $j = trim($j);
    $jamObj = Carbon::createFromFormat('H:i', $j);
    $pushed = false;

    // 1) Jika di window masuk → jam_masuk
    if ($jamObj->betweenIncluded($masukMin, $masukMax)) {
        $preview[] = [
            'nama'       => $nama,
            'departemen' => $departemen,
            'tanggal'    => $tanggal,
            'jam_masuk'  => $j,
            'jam_pulang' => null,
        ];
        $pushed = true;
    }

    // 2) Jika di window pulang → jam_pulang
    if ($jamObj->betweenIncluded($pulangMin, $pulangMax)) {
        $preview[] = [
            'nama'       => $nama,
            'departemen' => $departemen,
            'tanggal'    => $tanggal,
            'jam_masuk'  => null,
            'jam_pulang' => $j,
        ];
        $pushed = true;
    }

    // 3) Jika **tidak** masuk ke keduanya, tetap tampilkan:
    if (! $pushed) {
        if ($jamObj->lt($pulangMin)) {
            // Anggap pagi → masuk
            $preview[] = [
                'nama'       => $nama,
                'departemen' => $departemen,
                'tanggal'    => $tanggal,
                'jam_masuk'  => $j,
                'jam_pulang' => null,
            ];
        } else {
            // Anggap sore → pulang
            $preview[] = [
                'nama'       => $nama,
                'departemen' => $departemen,
                'tanggal'    => $tanggal,
                'jam_masuk'  => null,
                'jam_pulang' => $j,
            ];
        }
    }
}
                    }
                }
            }

            $bulanTahunUnik = array_unique($bulanTahunSet);
            if (count($bulanTahunUnik) > 1) {
                return back()->with('error', 'Bulan tidak sama antara file!');
            }
            [$tahunHapus, $bulanHapus] = explode('-', $bulanTahunUnik[0]);
            Absensi::whereYear('tanggal', $tahunHapus)
                   ->whereMonth('tanggal', $bulanHapus)
                   ->delete();

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
                if ($row['jam_masuk']) {
                    $merged[$key]['jam_masuk'] = $row['jam_masuk'];
                }
                if ($row['jam_pulang']) {
                    $merged[$key]['jam_pulang'] = $row['jam_pulang'];
                }
            }

            foreach ($merged as &$row) {
                $dow   = Carbon::parse($row['tanggal'])->dayOfWeekIso;
                $range = ($dow >= 1 && $dow <= 4) ? $seninKamis : $jumat;
                $minMasuk  = Carbon::createFromFormat('H:i', $range['masuk_min']);
                $maxMasuk  = Carbon::createFromFormat('H:i', $range['masuk_max']);
                $minPulang = Carbon::createFromFormat('H:i', $range['pulang_min']);
                $maxPulang = Carbon::createFromFormat('H:i', $range['pulang_max']);

                $statusMasuk = null;
                if ($row['jam_masuk']) {
                    $jm = Carbon::createFromFormat('H:i', $row['jam_masuk']);
                    if ($jm->lt($minMasuk)) {
                        $statusMasuk = 'diluar waktu absen';
                    } elseif ($jm->gt($maxMasuk)) {
                        $statusMasuk = 'terlambat';
                    } else {
                        $statusMasuk = 'tepat waktu';
                    }
                }
                $statusPulang = null;
                if ($row['jam_pulang']) {
                    $jp = Carbon::createFromFormat('H:i', $row['jam_pulang']);
                    if ($jp->gt($maxPulang)) {
                        $statusPulang = 'diluar waktu absen';
                    } elseif ($jp->lt($minPulang)) {
                        $statusPulang = 'terlambat';
                    } else {
                        $statusPulang = 'tepat waktu';
                    }
                }

                $all = array_filter([$statusMasuk, $statusPulang]);
                if (in_array('diluar waktu absen', $all)) {
                    $row['keterangan'] = 'diluar waktu absen';
                } elseif (in_array('terlambat', $all)) {
                    $row['keterangan'] = 'terlambat';
                } else {
                    $row['keterangan'] = 'tepat waktu';
                }
            }
            unset($row);

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
            case 'nama_asc':    $collection = $collection->sortBy('nama');      break;
            case 'nama_desc':   $collection = $collection->sortByDesc('nama');  break;
            case 'tanggal_asc': $collection = $collection->sortBy('tanggal');   break;
            case 'tanggal_desc':$collection = $collection->sortByDesc('tanggal');break;
        }

        $currentPage      = LengthAwarePaginator::resolveCurrentPage();
        $perPage          = 40;
        $currentItems     = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
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
            if (!$exists) {
                Absensi::create([
                    'karyawan_id' => $karyawan->id,
                    'tanggal'     => $row['tanggal'],
                    'jam_masuk'   => $row['jam_masuk'],
                    'jam_pulang'  => $row['jam_pulang'],
                    'keterangan'  => $row['keterangan'], 
                ]);
            }
        }

        session()->forget('preview_data');
        return redirect()->route('absensi.index')
                         ->with('success', 'Semua data absensi berhasil disimpan!');
    }
}
