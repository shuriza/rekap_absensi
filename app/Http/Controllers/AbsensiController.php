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

    /* ===================== Helpers Umum ===================== */

    /** Normalisasi nama/departemen → trim + collapse space + UPPER */
    private function normName(string $v): string
    {
        $v = preg_replace('/\s+/u', ' ', trim($v ?? ''));
        return mb_strtoupper($v, 'UTF-8');
    }

    /** Normalisasi jam sederhana ke HH:MM (utk input "07:33" dsb) */
    private function normTime(?string $t): ?string
    {
        if (!$t) return null;
        [$h, $i] = array_pad(explode(':', $t), 2, '00');
        return sprintf('%02d:%02d', (int)$h, (int)$i);
    }

    /** Parser waktu fleksibel → Carbon|null (07:33, 07:33:00, 7:33 AM) */
    private function parseTimeFlexible(?string $t): ?Carbon
    {
        if (!$t) return null;
        $t = trim($t);

        if (preg_match('/^\d{2}:\d{2}$/', $t))                return Carbon::createFromFormat('H:i', $t);
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t))          return Carbon::createFromFormat('H:i:s', $t);
        if (preg_match('/^\d{1,2}:\d{2}\s?(AM|PM)$/i', $t))   return Carbon::createFromFormat('g:i A', strtoupper($t));
        if (strlen($t) >= 5 && preg_match('/^\d{2}:\d{2}/', $t)) return Carbon::createFromFormat('H:i', substr($t, 0, 5));
        return null;
    }

    /** Normalisasi string jam → 'H:i' (pakai parser fleksibel) */
    private function normClock(?string $t): ?string
    {
        $c = $this->parseTimeFlexible($t);
        return $c ? $c->format('H:i') : null;
    }

    /** Pilih status dengan prioritas tertinggi */
    private function pickStatus(string $a = null, string $b = null): ?string
    {
        $prio = ['tepat waktu' => 1, 'terlambat' => 2, 'diluar waktu absen' => 3, 'tidak valid' => 0];
        $ka = $a ? ($prio[$a] ?? 0) : -1;
        $kb = $b ? ($prio[$b] ?? 0) : -1;

        if ($ka === -1) return $b;
        if ($kb === -1) return $a;
        return $ka >= $kb ? $a : $b;
    }

    /** Tentukan status (tepat waktu/terlambat/diluar/ tidak valid) berdasar jam & range */
    private function decideStatus(?string $jamMasuk, ?string $jamPulang, array $range): ?string
    {
        $jm = $jamMasuk ? Carbon::createFromFormat('H:i', $jamMasuk) : null;
        $jp = $jamPulang ? Carbon::createFromFormat('H:i', $jamPulang) : null;

        if (($jm && !$jp) || (!$jm && $jp)) return 'tidak valid';
        if (!$jm && !$jp) return null;

        $masukMin  = Carbon::createFromFormat('H:i', $range['masuk_min']);
        $masukMax  = Carbon::createFromFormat('H:i', $range['masuk_max']);
        $pulangMin = Carbon::createFromFormat('H:i', $range['pulang_min']);
        $pulangMax = Carbon::createFromFormat('H:i', $range['pulang_max']);

        $sMasuk  = $jm->lt($masukMin)  ? 'diluar waktu absen' : ($jm->gt($masukMax) ? 'terlambat' : 'tepat waktu');
        $sPulang = $jp->gt($pulangMax) ? 'diluar waktu absen' : ($jp->lt($pulangMin) ? 'terlambat' : 'tepat waktu');

        return $this->pickStatus($sMasuk, $sPulang);
    }

    /* ===================== Preview (Orchestrator) ===================== */

    public function preview(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->handleRamadhanDebug($request);
            $this->validatePreviewRequest($request);
            [$seninKamis, $jumat, $ramadhanRange] = $this->buildTimeRanges($request);

            [$preview, $bulanTahunSet] = $this->processUploadedFiles(
                $request,
                $seninKamis,
                $jumat,
                $ramadhanRange
            );

            $this->ensureSingleMonthOrFail($bulanTahunSet);
            $this->storeSessionPreview($preview, $seninKamis, $jumat, $ramadhanRange);
        } else {
            // GET: ambil dari session
            $preview = session('preview_data', []);
        }

        if (count($preview) === 0) {
            return back()->with('success', 'Tidak ada data absensi yang bisa ditampilkan.');
        }

        $paginated = $this->paginateAndFilter($preview, $request);

        return view('absensi.index', ['preview' => $paginated]);
    }

    /* ===================== Subroutines Preview ===================== */

    /** 1. Logging & flash info Ramadan (opsional) */
    private function handleRamadhanDebug(Request $request): void
    {
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

    /** 2. Validasi input POST */
    private function validatePreviewRequest(Request $request): void
    {
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
            // Ramadan (opsional)
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

    /** 3. Bangun range jam normal & Ramadan dari request */
    private function buildTimeRanges(Request $request): array
    {
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

        $ramadhanRange = null;
        $start = $request->input('ramadhan_start_date');
        $end   = $request->input('ramadhan_end_date');

        if ($start && $end) {
            try {
                $startDate = Carbon::parse($start);
                $endDate   = Carbon::parse($end);
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
                back()->with('error', 'Tanggal Ramadan tidak valid: '.$e->getMessage())->throwResponse();
            }
        }

        return [$seninKamis, $jumat, $ramadhanRange];
    }

    /** 4. Proses semua file yang di-upload → return [previewArray, bulanTahunSet] */
    private function processUploadedFiles(Request $request, array $seninKamis, array $jumat, ?array $ramadhanRange): array
    {
        $previewMap     = []; // key: NORM_NAMA|NORM_DEPT|YYYY-MM-DD
        $obCache        = []; // cache status OB by "nama|departemen"
        $bulanTahunSet  = [];

        if (!$request->hasFile('file_excel')) {
            return [session('preview_data', []), $bulanTahunSet];
        }

        foreach ($request->file('file_excel') as $file) {
            $data         = Excel::toArray(new \stdClass(), $file->getRealPath());
            $sheet        = $data[2] ?? [];  // sheet ke-3
            $barisTanggal = $sheet[3] ?? []; // row ke-4 (header tanggal)

            $cellC3 = $sheet[2][2] ?? null;
            if (!$cellC3) {
                back()->with('error', 'Cell C3 tidak ditemukan.')->throwResponse();
            }

            [$startDate, $endDate] = $this->parseC3DateRange($cellC3);
            $tahun = $startDate->year;
            $bulan = $startDate->month;
            $bulanTahunSet[] = sprintf('%04d-%02d', $tahun, $bulan);

            for ($i = 4; $i < count($sheet); $i += 2) {
                $infoRow = $sheet[$i];
                $dataRow = $sheet[$i + 1] ?? [];

                $nama       = $infoRow[10] ?? null;
                $departemen = $infoRow[20] ?? null;
                if (!$nama || !$departemen) continue;

                for ($col = 1; $col <= 31; $col++) {
                    $tanggalKe = $barisTanggal[$col] ?? null;
                    $raw       = $dataRow[$col]      ?? null;
                    if (!$tanggalKe || !$raw) continue;

                    $jamList = $this->extractJamList($raw);
                    if (empty($jamList)) continue;

                    $tgl = Carbon::createFromDate($tahun, $bulan, (int)$tanggalKe)->format('Y-m-d');
                    if (Carbon::parse($tgl)->lt($startDate) || Carbon::parse($tgl)->gt($endDate)) continue;

                    $tanggalObj = Carbon::parse($tgl);
                    $range = $this->pickDailyRange($tanggalObj, $seninKamis, $jumat, $ramadhanRange);

                    [$in, $out] = $this->selectValidInOut($jamList, $range);
                    if (!$in && !$out) continue;

                    $keterangan = $this->decideStatus($this->normClock($in), $this->normClock($out), $range);
                    $isOb       = $this->getIsOb($obCache, $nama, $departemen);

                    $this->mergePreviewRow($previewMap, [
                        'nama' => $nama,
                        'departemen' => $departemen,
                        'tanggal' => $tgl,
                        'jam_masuk' => $this->normClock($in),
                        'jam_pulang'=> $this->normClock($out),
                        'keterangan'=> $keterangan,
                        'range'     => $range,
                        'is_ob'     => $isOb,
                    ]);
                }
            }
        }

        return [array_values($previewMap), $bulanTahunSet];
    }

    /** Parse rentang tanggal dari cell C3 */
    private function parseC3DateRange($cellC3): array
    {
        if (is_string($cellC3) && str_contains($cellC3, '~')) {
            [$rawAwal, $rawAkhir] = array_map('trim', explode('~', $cellC3));
            return [Carbon::parse($rawAwal), Carbon::parse($rawAkhir)];
        } elseif (is_numeric($cellC3)) {
            $dt = Date::excelToDateTimeObject($cellC3);
            $c  = Carbon::instance($dt);
            return [$c, clone $c];
        } else {
            $c = Carbon::parse($cellC3);
            return [$c, clone $c];
        }
    }

    /** Ekstrak daftar jam dari sel (support numeric excel time & string "07:31 16:05") */
    private function extractJamList($raw): array
    {
        if (is_numeric($raw)) {
            try {
                $dt = Date::excelToDateTimeObject($raw);
                return [Carbon::instance($dt)->format('H:i')];
            } catch (\Throwable $e) {
                return [];
            }
        }
        if (is_string($raw)) {
            preg_match_all('/\d{1,2}:\d{2}/', $raw, $m);
            return $m[0] ?? [];
        }
        return [];
    }

    /** Pilih range harian: Ramadan/non-Ramadan dan Jumat/Senin-Kamis */
    private function pickDailyRange(Carbon $tanggal, array $seninKamis, array $jumat, ?array $ramadhanRange): array
    {
        $isFri = $tanggal->dayOfWeekIso === 5;

        if ($ramadhanRange && $tanggal->between($ramadhanRange['start_date'], $ramadhanRange['end_date'])) {
            return $isFri ? $ramadhanRange['jumat'] : $ramadhanRange['senin_kamis'];
        }
        return $isFri ? $jumat : $seninKamis;
    }

    /** Tentukan jam masuk/pulang yang “valid” dari list */
    private function selectValidInOut(array $jamList, array $range): array
    {
        sort($jamList);
        $masukMin  = Carbon::createFromFormat('H:i', $range['masuk_min']);
        $masukMax  = Carbon::createFromFormat('H:i', $range['masuk_max']);
        $pulangMin = Carbon::createFromFormat('H:i', $range['pulang_min']);
        $pulangMax = Carbon::createFromFormat('H:i', $range['pulang_max']);

        $in = null; $out = null;

        foreach ($jamList as $j) {
            $jObj = Carbon::createFromFormat('H:i', $j);
            if ($jObj->betweenIncluded($masukMin, $masukMax)) { $in = $j; break; }
        }
        if (!$in) $in = $jamList[0] ?? null;

        foreach (array_reverse($jamList) as $j) {
            $jObj = Carbon::createFromFormat('H:i', $j);
            if ($in && $j > $in && $jObj->betweenIncluded($pulangMin, $pulangMax)) { $out = $j; break; }
        }
        if (!$out && count($jamList) > 1 && end($jamList) > $in) {
            $out = end($jamList);
        }

        return [$in, $out];
    }

    /** Ambil status OB dengan cache */
    private function getIsOb(array &$cache, string $nama, string $departemen): bool
    {
        $key = $nama.'|'.$departemen;
        if (!array_key_exists($key, $cache)) {
            $cache[$key] = (bool) optional(
                Karyawan::where('nama', $nama)->where('departemen', $departemen)->first(['is_ob'])
            )->is_ob;
        }
        return $cache[$key];
    }

    /** Merge-by-key ke map preview (hindari dobel antar file) */
    private function mergePreviewRow(array &$previewMap, array $row): void
    {
        $key = $this->normName($row['nama']) . '|' . $this->normName($row['departemen']) . '|' . $row['tanggal'];
        $in  = $row['jam_masuk'];
        $out = $row['jam_pulang'];

        if (!isset($previewMap[$key])) {
            $calc = $this->computePenalty($in, $out, $row['range'], $row['is_ob']);
            $previewMap[$key] = [
                'nama'            => $row['nama'],
                'departemen'      => $row['departemen'],
                'tanggal'         => $row['tanggal'],
                'jam_masuk'       => $in,
                'jam_pulang'      => $out,
                'keterangan'      => $row['keterangan'],
                'late_minutes'    => $calc['late'],
                'early_minutes'   => $calc['early'],
                'penalty_minutes' => $calc['penalty'],
                'work_minutes'    => $calc['work'],
                'is_ob'           => $row['is_ob'],
            ];
            return;
        }

        $ex      = $previewMap[$key];
        $inPick  = $ex['jam_masuk'];
        if ($in && (!$inPick || $in < $inPick)) $inPick = $in;

        $outPick = $ex['jam_pulang'];
        if ($out && (!$outPick || $out > $outPick)) $outPick = $out;

        $status = $this->decideStatus($inPick, $outPick, $row['range']);
        $calc   = $this->computePenalty($inPick, $outPick, $row['range'], (bool)$ex['is_ob']);

        $previewMap[$key] = [
            'nama'            => $ex['nama'],
            'departemen'      => $ex['departemen'],
            'tanggal'         => $ex['tanggal'],
            'jam_masuk'       => $inPick,
            'jam_pulang'      => $outPick,
            'keterangan'      => $status,
            'late_minutes'    => $calc['late'],
            'early_minutes'   => $calc['early'],
            'penalty_minutes' => $calc['penalty'],
            'work_minutes'    => $calc['work'],
            'is_ob'           => $ex['is_ob'],
        ];
    }

    /** 5. Pastikan semua file di bulan yang sama */
    private function ensureSingleMonthOrFail(array $bulanTahunSet): void
    {
        $bulanUnik = array_unique($bulanTahunSet);
        if (count($bulanUnik) > 1) {
            back()->with('error', 'Bulan tidak sama antara file!')->throwResponse();
        }
    }

    /** 6. Simpan hasil preview & filter ke session */
    private function storeSessionPreview(array $preview, array $seninKamis, array $jumat, ?array $ramadhanRange): void
    {
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
    }

    /** 7. Filter, sort, dan paginasi hasil */
    private function paginateAndFilter(array $preview, Request $request): LengthAwarePaginator
    {
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

        $currentPage  = LengthAwarePaginator::resolveCurrentPage();
        $perPage      = 25;
        $currentItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /* ===================== Hitung menit/penalti ===================== */

    private function computePenalty(?string $jamMasuk, ?string $jamPulang, array $range, bool $isOb): array
    {
        $parse = fn (?string $t) => $this->parseTimeFlexible($t);

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

        $work = (int) $jm->diffInMinutes($jp);

        if ($isOb) {
            return ['late'=>0, 'early'=>0, 'work'=>$work, 'penalty'=>0];
        }

        if (($inMin && $jm->lt($inMin)) || ($outMax && $jp->gt($outMax))) {
            return ['late'=>0, 'early'=>0, 'work'=>$work, 'penalty'=>450];
        }

        $late  = ($inMax && $jm->gt($inMax))  ? $inMax->diffInMinutes($jm)  : 0;
        $early = ($outMin && $jp->lt($outMin))? $jp->diffInMinutes($outMin) : 0;

        return [
            'late'    => max(0, (int)$late),
            'early'   => max(0, (int)$early),
            'work'    => $work,
            'penalty' => max(0, (int)$late) + max(0, (int)$early),
        ];
    }

    /* ===================== Store ===================== */

    public function store(Request $request)
    {
        $data = session('preview_data');

        if (!$this->validateStoreData($data)) {
            return back()->with('error', 'Tidak ada data yang bisa disimpan.');
        }
        if (!$this->validateSingleMonth($data)) {
            return back()->with('error', 'Data yang akan disimpan mengandung lebih dari satu bulan.');
        }

        $this->deleteExistingData($data);
        $this->saveAttendanceData($data);
        $this->clearSessionData();

        return redirect()->route('absensi.index')->with('success', 'Semua data absensi berhasil disimpan!');
    }

    /** Validasi data ada & array */
    private function validateStoreData($data): bool
    {
        return is_array($data) && !empty($data);
    }

    /** Semua data di satu bulan yang sama */
    private function validateSingleMonth(array $data): bool
    {
        $bulanTahun = collect($data)
            ->map(fn($r) => Carbon::parse($r['tanggal'])->format('Y-m'))
            ->unique()
            ->values();

        return $bulanTahun->count() === 1;
    }

    /** Hapus data bulan yang sama sebelum simpan baru */
    private function deleteExistingData(array $data): void
    {
        $firstDate = Carbon::parse($data[0]['tanggal']);
        Absensi::whereYear('tanggal', $firstDate->year)
            ->whereMonth('tanggal', $firstDate->month)
            ->delete();
    }

    /** Simpan data absensi ke DB (ikutkan menit jika ada) */
    private function saveAttendanceData(array $data): void
    {
        foreach ($data as $row) {
            $karyawan = Karyawan::firstOrCreate([
                'nama'       => $row['nama'],
                'departemen' => $row['departemen'],
            ]);

            Absensi::updateOrCreate(
                ['karyawan_id' => $karyawan->id, 'tanggal' => $row['tanggal']],
                [
                    'jam_masuk'       => $this->normClock($row['jam_masuk'] ?? null),
                    'jam_pulang'      => $this->normClock($row['jam_pulang'] ?? null),
                    'keterangan'      => $row['keterangan'] ?? null,
                    'late_minutes'    => $row['late_minutes']    ?? null,
                    'early_minutes'   => $row['early_minutes']   ?? null,
                    'penalty_minutes' => $row['penalty_minutes'] ?? null,
                    'work_minutes'    => $row['work_minutes']    ?? null,
                ]
            );
        }
    }

    /** Bersihkan session terkait absensi */
    private function clearSessionData(): void
    {
        session()->forget(['preview_data', 'absensi_filter']);
    }
}
