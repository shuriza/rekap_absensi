<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Absensi;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Log;

class AbsensiController extends Controller
{
    public function index()
    {
        return view('absensi.index');
    }

    public function preview(Request $request)
    {
        // (opsional) debug input Ramadhan
        if ($request->isMethod('post')) {
            Log::info('Ramadhan Debug', [
                'ramadhan_start_date' => $request->input('ramadhan_start_date'),
                'ramadhan_end_date'   => $request->input('ramadhan_end_date'),
            ]);
            if ($request->filled(['ramadhan_start_date', 'ramadhan_end_date'])) {
                session()->flash(
                    'debug_ramadhan',
                    'Ramadhan: '.$request->input('ramadhan_start_date').' s/d '.$request->input('ramadhan_end_date')
                );
            }
        }

        // 1) Validasi input saat POST
        if ($request->isMethod('post')) {
            $request->validate([
                'file_excel.*'          => 'required|mimes:xlsx,xls',
                'jam_masuk_min_senin'   => 'required|date_format:H:i',
                'jam_masuk_max_senin'   => 'required|date_format:H:i',
                'jam_pulang_min_senin'  => 'required|date_format:H:i',
                'jam_pulang_max_senin'  => 'required|date_format:H:i',
                'jam_masuk_min_jumat'   => 'required|date_format:H:i',
                'jam_masuk_max_jumat'   => 'required|date_format:H:i',
                'jam_pulang_min_jumat'  => 'required|date_format:H:i',
                'jam_pulang_max_jumat'  => 'required|date_format:H:i',
                // Ramadhan (opsional)
                'ramadhan_start_date'   => 'nullable|date',
                'ramadhan_end_date'     => 'nullable|date',
                'jam_masuk_min_ramadhan_senin'   => 'nullable|date_format:H:i',
                'jam_masuk_max_ramadhan_senin'   => 'nullable|date_format:H:i',
                'jam_pulang_min_ramadhan_senin'  => 'nullable|date_format:H:i',
                'jam_pulang_max_ramadhan_senin'  => 'nullable|date_format:H:i',
                'jam_masuk_min_ramadhan_jumat'   => 'nullable|date_format:H:i',
                'jam_masuk_max_ramadhan_jumat'   => 'nullable|date_format:H:i',
                'jam_pulang_min_ramadhan_jumat'  => 'nullable|date_format:H:i',
                'jam_pulang_max_ramadhan_jumat'  => 'nullable|date_format:H:i',
            ]);
        }

        // 2) Container hasil
        $preview       = [];
        $obCache       = []; // cache status OB by "nama|departemen"
        $bulanTahunSet = [];

        // 3) Filter normal (Senin–Kamis, Jumat)
        $seninKamis = [
            'masuk_min'  => $request->input('jam_masuk_min_senin', '07:00'),
            'masuk_max'  => $request->input('jam_masuk_max_senin', '07:30'),
            'pulang_min' => $request->input('jam_pulang_min_senin', '15:30'),
            'pulang_max' => $request->input('jam_pulang_max_senin', '17:00'),
        ];
        $jumat = [
            'masuk_min'  => $request->input('jam_masuk_min_jumat', '07:00'),
            'masuk_max'  => $request->input('jam_masuk_max_jumat', '07:30'),
            'pulang_min' => $request->input('jam_pulang_min_jumat', '15:00'),
            'pulang_max' => $request->input('jam_pulang_max_jumat', '17:00'),
        ];

        // 4) Konfigurasi Ramadhan (jika ada)
        $ramadhanStartDate = $request->input('ramadhan_start_date');
        $ramadhanEndDate   = $request->input('ramadhan_end_date');
        $ramadhanRange     = null;

        if ($ramadhanStartDate && $ramadhanEndDate) {
            try {
                $startDate = Carbon::parse($ramadhanStartDate);
                $endDate   = Carbon::parse($ramadhanEndDate);

                $ramadhanRange = [
                    'start_date'  => $startDate,
                    'end_date'    => $endDate,
                    'senin_kamis' => [
                        'masuk_min'  => $request->input('jam_masuk_min_ramadhan_senin', '08:00'),
                        'masuk_max'  => $request->input('jam_masuk_max_ramadhan_senin', '08:30'),
                        'pulang_min' => $request->input('jam_pulang_min_ramadhan_senin', '15:00'),
                        'pulang_max' => $request->input('jam_pulang_max_ramadhan_senin', '16:00'),
                    ],
                    'jumat' => [
                        'masuk_min'  => $request->input('jam_masuk_min_ramadhan_jumat', '08:00'),
                        'masuk_max'  => $request->input('jam_masuk_max_ramadhan_jumat', '08:30'),
                        'pulang_min' => $request->input('jam_pulang_min_ramadhan_jumat', '15:00'),
                        'pulang_max' => $request->input('jam_pulang_max_ramadhan_jumat', '16:00'),
                    ],
                ];
            } catch (\Throwable $e) {
                return back()->with('error', 'Tanggal Ramadan tidak valid: '.$e->getMessage());
            }
        }

        // 5) Baca file Excel
        if ($request->hasFile('file_excel')) {
            foreach ($request->file('file_excel') as $file) {
                $data         = Excel::toArray(new \stdClass(), $file->getRealPath());
                $sheet        = $data[2] ?? [];       // sheet ke-3
                $barisTanggal = $sheet[3] ?? [];      // row ke-4 (header tanggal)

                // 5a) Rentang tanggal dari C3
                $cellC3 = $sheet[2][2] ?? null;
                if (!$cellC3) return back()->with('error', 'Cell C3 tidak ditemukan.');

                if (is_string($cellC3) && str_contains($cellC3, '~')) {
                    [$rawAwal, $rawAkhir] = array_map('trim', explode('~', $cellC3));
                    $startDate = Carbon::parse($rawAwal);
                    $endDate   = Carbon::parse($rawAkhir);
                } elseif (is_numeric($cellC3)) {
                    $dt        = Date::excelToDateTimeObject($cellC3);
                    $startDate = Carbon::instance($dt);
                    $endDate   = Carbon::instance((clone $dt));
                } else {
                    $startDate = Carbon::parse($cellC3);
                    $endDate   = Carbon::parse($cellC3);
                }

                $tahun = $startDate->year;
                $bulan = $startDate->month;
                $bulanTahunSet[] = sprintf('%04d-%02d', $tahun, $bulan);

                // 5b) Loop baris data
                for ($i = 4; $i < count($sheet); $i += 2) {
                    $infoRow = $sheet[$i];
                    $dataRow = $sheet[$i + 1] ?? [];

                    $nama       = $infoRow[10] ?? null;
                    $departemen = $infoRow[20] ?? null;
                    if (!$nama || !$departemen) continue;

                    // 5c) Per tanggal 1–31
                    for ($col = 1; $col <= 31; $col++) {
                        $tanggalKe = $barisTanggal[$col] ?? null;
                        $raw       = $dataRow[$col]      ?? null;
                        if (!$tanggalKe || !$raw) continue;

                        // parse cell → daftar HH:mm
                        $jamList = [];
                        if (is_numeric($raw)) {
                            try {
                                $dt2 = Date::excelToDateTimeObject($raw);
                                $jamList[] = Carbon::instance($dt2)->format('H:i');
                            } catch (\Throwable $e) {
                                continue;
                            }
                        } elseif (is_string($raw)) {
                            preg_match_all('/\d{1,2}:\d{2}/', $raw, $m);
                            $jamList = $m[0] ?? [];
                        }
                        if (empty($jamList)) continue;

                        $tgl = Carbon::createFromDate($tahun, $bulan, (int) $tanggalKe)->format('Y-m-d');
                        if (Carbon::parse($tgl)->lt($startDate) || Carbon::parse($tgl)->gt($endDate)) {
                            continue;
                        }

                        // Pilih range: Ramadhan vs normal, Jumat vs Senin–Kamis
                        $tanggalObj = Carbon::parse($tgl);
                        if ($ramadhanRange && $tanggalObj->between($ramadhanRange['start_date'], $ramadhanRange['end_date'])) {
                            $range = ($tanggalObj->dayOfWeekIso === 5)
                                ? $ramadhanRange['jumat']
                                : $ramadhanRange['senin_kamis'];
                        } else {
                            $range = ($tanggalObj->dayOfWeekIso === 5) ? $jumat : $seninKamis;
                        }

                        // Batas waktu
                        $masukMin  = Carbon::createFromFormat('H:i', $range['masuk_min']);
                        $masukMax  = Carbon::createFromFormat('H:i', $range['masuk_max']);
                        $pulangMin = Carbon::createFromFormat('H:i', $range['pulang_min']);
                        $pulangMax = Carbon::createFromFormat('H:i', $range['pulang_max']);

                        // 5d) Tentukan jam masuk/pulang valid
                        sort($jamList);
                        $jamMasukValid  = null;
                        $jamPulangValid = null;

                        foreach ($jamList as $j) {
                            $jObj = Carbon::createFromFormat('H:i', $j);
                            if ($jObj->betweenIncluded($masukMin, $masukMax)) {
                                $jamMasukValid = $j; break;
                            }
                        }
                        if (!$jamMasukValid) $jamMasukValid = $jamList[0];

                        foreach (array_reverse($jamList) as $j) {
                            $jObj = Carbon::createFromFormat('H:i', $j);
                            if ($jamMasukValid && $j > $jamMasukValid && $jObj->betweenIncluded($pulangMin, $pulangMax)) {
                                $jamPulangValid = $j; break;
                            }
                        }
                        if (!$jamPulangValid && count($jamList) > 1 && end($jamList) > $jamMasukValid) {
                            $jamPulangValid = end($jamList);
                        }
                        if (!$jamMasukValid && !$jamPulangValid) continue;

                        // Keterangan (tetap seperti sebelumnya)
                        $jm = $jamMasukValid ? Carbon::createFromFormat('H:i', $jamMasukValid) : null;
                        $jp = $jamPulangValid ? Carbon::createFromFormat('H:i', $jamPulangValid) : null;
                        $keterangan = null;

                        if ($jm && !$jp) {
                            $keterangan = 'tidak valid';
                        } elseif (!$jm && $jp) {
                            $keterangan = 'tidak valid';
                        } elseif ($jm && $jp) {
                            $sMasuk  = $jm->lt($masukMin)  ? 'diluar waktu absen' : ($jm->gt($masukMax) ? 'terlambat' : 'tepat waktu');
                            $sPulang = $jp->gt($pulangMax) ? 'diluar waktu absen' : ($jp->lt($pulangMin) ? 'terlambat' : 'tepat waktu');
                            $all = array_filter([$sMasuk, $sPulang]);
                            $keterangan = in_array('diluar waktu absen', $all) ? 'diluar waktu absen'
                                        : (in_array('terlambat', $all) ? 'terlambat' : 'tepat waktu');
                        }

                        // Hitung menit (late/early/penalty/work) sesuai range + status OB
                        $obKey = $nama.'|'.$departemen;
                        if (!array_key_exists($obKey, $obCache)) {
                            $obCache[$obKey] = (bool) optional(
                                Karyawan::where('nama', $nama)->where('departemen', $departemen)->first(['is_ob'])
                            )->is_ob;
                        }
                        $isOb = $obCache[$obKey];

                        $calc = $this->computePenalty($jamMasukValid, $jamPulangValid, $range, $isOb);

                        // Simpan ke preview
                        $preview[] = [
                            'nama'            => $nama,
                            'departemen'      => $departemen,
                            'tanggal'         => $tgl,
                            'jam_masuk'       => $jamMasukValid,
                            'jam_pulang'      => $jamPulangValid,
                            'keterangan'      => $keterangan,
                            'late_minutes'    => $calc['late'],
                            'early_minutes'   => $calc['early'],
                            'penalty_minutes' => $calc['penalty'],
                            'work_minutes'    => $calc['work'],
                            'is_ob'           => $isOb,
                        ];
                    }
                }
            }

            // 6) Validasi semua file di bulan yang sama
            $bulanUnik = array_unique($bulanTahunSet);
            if (count($bulanUnik) > 1) {
                return back()->with('error', 'Bulan tidak sama antara file!');
            }
            [$y, $m] = explode('-', $bulanUnik[0]);

            // Hapus data lama bulan tsb
            Absensi::whereYear('tanggal', $y)->whereMonth('tanggal', $m)->delete();

            // 7) Simpan preview & filter ke session
            session(['preview_data' => $preview]);
            session(['absensi_filter' => [
                'senin_kamis' => $seninKamis,
                'jumat'       => $jumat,
                'ramadhan'    => $ramadhanRange ? [
                    'start'       => $ramadhanRange['start_date']->toDateString(),
                    'end'         => $ramadhanRange['end_date']->toDateString(),
                    'senin_kamis' => $ramadhanRange['senin_kamis'],
                    'jumat'       => $ramadhanRange['jumat'],
                ] : null,
            ]]);
        } else {
            // GET: ambil dari session
            $preview = session('preview_data', []);
        }

        // 8) Jika kosong
        if (count($preview) === 0) {
            return back()->with('success', 'Tidak ada data absensi yang bisa ditampilkan.');
        }

        // 9) Filter & sort di preview
        $collection = collect($preview);
        if ($search = $request->input('search')) {
            $collection = $collection->filter(fn($row) => stripos($row['nama'], $search) !== false);
        }
        switch ($request->input('sort_by')) {
            case 'nama_asc':     $collection = $collection->sortBy('nama');       break;
            case 'nama_desc':    $collection = $collection->sortByDesc('nama');   break;
            case 'tanggal_asc':  $collection = $collection->sortBy('tanggal');    break;
            case 'tanggal_desc': $collection = $collection->sortByDesc('tanggal');break;
        }

        // 10) Paginasi
        $currentPage  = LengthAwarePaginator::resolveCurrentPage();
        $perPage      = 25;
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedPreview = new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // 11) Tampilkan
        return view('absensi.index', [
            'preview' => $paginatedPreview,
        ]);
    }

    /** Normalisasi "07:33" / "07:33:00" → "07:33" (atau null bila format aneh) */
   // Terima '07:33', '07:33:00', '7:33 AM', dst. Return Carbon atau null.
    private function parseTimeFlexible(?string $t): ?Carbon
    {
        if (!$t) return null;
        $t = trim($t);

        // sudah HH:mm
        if (preg_match('/^\d{2}:\d{2}$/', $t)) {
            return Carbon::createFromFormat('H:i', $t);
        }
        // HH:mm:ss
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) {
            return Carbon::createFromFormat('H:i:s', $t);
        }
        // 12-hour '7:33 AM' / '07:33 PM'
        if (preg_match('/^\d{1,2}:\d{2}\s?(AM|PM)$/i', $t)) {
            return Carbon::createFromFormat('g:i A', strtoupper($t));
        }
        // fallback: coba potong ke 5 char pertama "HH:mm"
        if (strlen($t) >= 5 && preg_match('/^\d{2}:\d{2}/', $t)) {
            return Carbon::createFromFormat('H:i', substr($t,0,5));
        }
        return null;
    }

    // Normalisasi string jam ke 'H:i' agar konsisten di DB
    private function norm(?string $t): ?string
    {
        $c = $this->parseTimeFlexible($t ?? null); // atau pakai closure parse di atas, ekstrak jadi method
        return $c ? $c->format('H:i') : null;
    }



    /**
     * Hitung menit telat, pulang cepat, penalti (total kedisiplinan) & work minutes.
     * - Non-OB: di luar window (masuk < min atau pulang > max) ⇒ penalti 450
     * - OB: lengkap (in-out) ⇒ penalti 0; selain itu ⇒ 450
     */
    private function computePenalty(?string $jamMasuk, ?string $jamPulang, array $range, bool $isOb): array
    {
        // parser yang fleksibel (sesuaikan dengan punyamu)
        $parse = function (?string $t): ?\Carbon\Carbon {
            if (!$t) return null;
            $t = trim($t);
            if (preg_match('/^\d{2}:\d{2}$/', $t))  return \Carbon\Carbon::createFromFormat('H:i', $t);
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) return \Carbon\Carbon::createFromFormat('H:i:s', $t);
            if (preg_match('/^\d{1,2}:\d{2}\s?(AM|PM)$/i', $t)) return \Carbon\Carbon::createFromFormat('g:i A', strtoupper($t));
            if (strlen($t) >= 5 && preg_match('/^\d{2}:\d{2}/', $t)) return \Carbon\Carbon::createFromFormat('H:i', substr($t,0,5));
            return null;
        };

        $jm = $parse($jamMasuk);
        $jp = $parse($jamPulang);

        $inMin   = $parse($range['masuk_min']  ?? null);
        $inMax   = $parse($range['masuk_max']  ?? null);
        $outMin  = $parse($range['pulang_min'] ?? null);
        $outMax  = $parse($range['pulang_max'] ?? null);

        // tidak ada/ tidak lengkap / urutan salah → penalti 7.5 jam
        $incomplete = (!$jm && !$jp) || ($jm && !$jp) || (!$jm && $jp) || ($jm && $jp && !$jp->gt($jm));
        if ($incomplete) {
            return ['late'=>0, 'early'=>0, 'work'=>null, 'penalty'=>450];
        }

        $work = (int) $jm->diffInMinutes($jp); // selalu positif

        if ($isOb) {
            // OB: tidak dihitung telat/pulang cepat, tidak ada penalti
            return ['late'=>0, 'early'=>0, 'work'=>$work, 'penalty'=>0];
        }

        // Non-OB: kalau masuk < min ATAU pulang > max ⇒ penalti 7.5 jam
        if (($inMin && $jm->lt($inMin)) || ($outMax && $jp->gt($outMax))) {
            return ['late'=>0, 'early'=>0, 'work'=>$work, 'penalty'=>450];
        }

        // Hitung dengan arah yang benar (tidak mungkin negatif)
        $late  = ($inMax && $jm->gt($inMax))  ? $inMax->diffInMinutes($jm)  : 0;   // 07:30 -> 07:33 = 3
        $early = ($outMin && $jp->lt($outMin))? $jp->diffInMinutes($outMin) : 0;   // 15:47 < 15:30? tidak

        // Clamp untuk jaga-jaga
        $late  = max(0, (int)$late);
        $early = max(0, (int)$early);

        return [
            'late'    => $late,
            'early'   => $early,
            'work'    => $work,
            'penalty' => $late + $early,
        ];
    }


    public function store(Request $request)
    {
        $data = session('preview_data');
        $cfg  = session('absensi_filter'); // filter yang dipakai saat preview

        if (!is_array($data) || empty($data) || empty($cfg)) {
            return back()->with('error', 'Tidak ada data/konfigurasi yang bisa disimpan.');
        }

        foreach ($data as $row) {
            // cari/buat karyawan
            $karyawan = Karyawan::firstOrCreate([
                'nama'       => $row['nama'],
                'departemen' => $row['departemen'],
            ]);

            // pilih range untuk tanggal tsb (ramadan/normal + jumat/senin-kamis)
            $tgl   = Carbon::parse($row['tanggal']);
            $range = null;

            if (!empty($cfg['ramadhan'])) {
                $rStart = Carbon::parse($cfg['ramadhan']['start']);
                $rEnd   = Carbon::parse($cfg['ramadhan']['end']);
                if ($tgl->between($rStart, $rEnd)) {
                    $range = ($tgl->dayOfWeekIso === 5)
                        ? $cfg['ramadhan']['jumat']
                        : $cfg['ramadhan']['senin_kamis'];
                }
            }
            if (!$range) {
                $range = ($tgl->dayOfWeekIso === 5) ? $cfg['jumat'] : $cfg['senin_kamis'];
            }

            // hitung lagi menit (aman kalau jam di session H:i:s)
            $jmNorm = $this->norm($row['jam_masuk']  ?? null);
            $jpNorm = $this->norm($row['jam_pulang'] ?? null);

            // ... lalu pass ke computePenalty & DB
            $calc = $this->computePenalty($jmNorm, $jpNorm, $range, (bool)$karyawan->is_ob);

            Absensi::updateOrCreate(
                ['karyawan_id' => $karyawan->id, 'tanggal' => $row['tanggal']],
                [
                    'jam_masuk'       => $jmNorm,
                    'jam_pulang'      => $jpNorm,
                    'keterangan'      => $row['keterangan'] ?? null,
                    'late_minutes'    => $calc['late'],
                    'early_minutes'   => $calc['early'],
                    'penalty_minutes' => $calc['penalty'],
                    'work_minutes'    => $calc['work'],
                ]
            );
        }

        // bersihkan session
        session()->forget(['preview_data','absensi_filter']);

        return redirect()
            ->route('absensi.index')
            ->with('success', 'Semua data absensi berhasil disimpan!');
    }
}
