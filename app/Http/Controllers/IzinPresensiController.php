<?php

namespace App\Http\Controllers;

use App\Models\IzinPresensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class IzinPresensiController extends Controller
{
    /* ---------------------------------------------------- LIST */
    public function index(Request $request)
    {
        $bt   = $request->query('bulan_tahun');          // YYYY-MM
        $sort = $request->query('sort', 'nama_asc');
        $q    = $request->query('q');

        [$kolom, $arah] = array_pad(explode('_', $sort), 2, 'asc');
        $arah           = $arah === 'desc' ? 'desc' : 'asc';

        $validKolom = ['tanggal_awal', 'tanggal_akhir', 'tipe_ijin', 'nama'];
        $kolom      = in_array($kolom, $validKolom) ? $kolom : 'nama';

        $izinTbl = (new IzinPresensi)->getTable();
        $query   = IzinPresensi::with('karyawan');

        if ($bt) {
            [$y, $m] = explode('-', $bt);
            $query->whereYear('tanggal_awal', $y)->whereMonth('tanggal_awal', $m);
        }

        if ($q) {
            $query->where(function ($qr) use ($q) {
                $qr->whereHas('karyawan',
                        fn($k) => $k->where('nama', 'like', "%$q%"))
                   ->orWhere('tipe_ijin', 'like', "%$q%");
            });
        }

        if ($kolom === 'nama') {
            $query->join('karyawans', 'karyawans.id', '=', "$izinTbl.karyawan_id")
                  ->orderBy('karyawans.nama', $arah)
                  ->select("$izinTbl.*");
        } else {
            $query->orderBy($kolom, $arah);
        }

         $data = $query->orderBy($kolom, $arah)->get();

        return view('izin_presensi.index', compact('data', 'bt', 'sort', 'q'));
    }


    /* ---------------------------------------------------- CREATE */
    public function create()
    {
        $rawJenis = [
            'DL - DINAS LUAR',
            'K - KEDINASAN',
            'S - SAKIT',
            'M - MELAHIRKAN',
            'AP - ALASAN PRIBADI',
            'L - LAINNYA',

    ];
    $listJenis = array_map(function($str) {
            $max = 80;
            return mb_strlen($str) > $max ? mb_substr($str, 0, $max-3).'...' : $str;
        }, $rawJenis);

        $tipeIjin  = ['PENUH','PARSIAL','TERLAMBAT','PULANG CEPAT','LAINNYA'];
        $karyawans = Karyawan::orderBy('nama')->get(['id','nama']);

        return view('izin_presensi.create',compact('listJenis','tipeIjin','karyawans'));
    }

    /* ---------------------------------------------------- STORE (POST) */
    public function store(Request $r)
    {
        try {
            $data = $this->validateData($r);
            $data['tanggal_akhir'] = $data['tanggal_akhir'] ?: $data['tanggal_awal'];

            // Cek apakah ada data izin yang overlap
            $existingIzin = $this->checkOverlapped($data);
            if ($existingIzin) {
                $karyawan = Karyawan::find($data['karyawan_id']);
                $tanggalMulai = \Carbon\Carbon::parse($existingIzin->tanggal_awal)->format('d-m-Y');
                $tanggalSelesai = \Carbon\Carbon::parse($existingIzin->tanggal_akhir)->format('d-m-Y');
                
                return back()
                    ->with('error', "⚠️ Data izin sudah ada! Karyawan {$karyawan->nama} sudah memiliki izin {$existingIzin->jenis_ijin} pada tanggal {$tanggalMulai} s/d {$tanggalSelesai}.")
                    ->withInput();
            }

            if($r->file('berkas')){
                $data['berkas'] = $r->file('berkas')->store('izin_presensi','public');
            }

            IzinPresensi::create($data);

            return redirect()->route('izin_presensi.index')->with('success','✓ Data izin berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error','✗ Gagal menambahkan data izin. '.$e->getMessage())->withInput();
        }
    }

    /* ---------------------------------------------------- UPDATE (PUT) */
    public function update(Request $r, IzinPresensi $izin_presensi)
    {
        try {
            $data = $this->validateData($r);
            $data['tanggal_akhir'] = $data['tanggal_akhir'] ?: $data['tanggal_awal'];

            // Cek apakah ada data izin yang overlap (kecuali data yang sedang di-edit)
            $existingIzin = $this->checkOverlapped($data, $izin_presensi->id);
            if ($existingIzin) {
                $karyawan = Karyawan::find($data['karyawan_id']);
                $tanggalMulai = \Carbon\Carbon::parse($existingIzin->tanggal_awal)->format('d-m-Y');
                $tanggalSelesai = \Carbon\Carbon::parse($existingIzin->tanggal_akhir)->format('d-m-Y');
                
                return back()
                    ->with('error', "⚠️ Data izin sudah ada! Karyawan {$karyawan->nama} sudah memiliki izin {$existingIzin->jenis_ijin} pada tanggal {$tanggalMulai} s/d {$tanggalSelesai}.")
                    ->withInput();
            }

            if($r->file('berkas')){
                if($izin_presensi->berkas)
                    Storage::disk('public')->delete($izin_presensi->berkas);
                $data['berkas'] = $r->file('berkas')->store('izin_presensi','public');
            }

            $izin_presensi->update($data);

            return back()->with('success','✓ Data izin berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error','✗ Gagal memperbarui data izin. '.$e->getMessage())->withInput();
        }
    }

    /* ---------------------------------------------------- DESTROY (DELETE) */
    public function destroy(IzinPresensi $izin_presensi)
    {
        try {
            if($izin_presensi->berkas)
                Storage::disk('public')->delete($izin_presensi->berkas);

            $izin_presensi->delete();
            return back()->with('success','✓ Data izin berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error','✗ Gagal menghapus data izin. '.$e->getMessage());
        }
    }

    /* ---------------------------------------------------- API PREVIEW */
    public function byDate(Karyawan $karyawan, $tgl)
    {
        $izin = IzinPresensi::where('karyawan_id',$karyawan->id)
                ->where('tanggal_awal','<=',$tgl)
                ->where('tanggal_akhir','>=',$tgl)
                ->first();
        return response()->json($izin);
    }

    /* ---------------------------------------------------- SHOW (optional) */
    public function show(IzinPresensi $izinPresensi, Request $r)
    {
        if($r->expectsJson()) return $izinPresensi;
        return view('izin_presensi.show',compact('izinPresensi'));
    }

    /* ---------------------------------------------------- LAMPIRAN */
    public function lampiran(IzinPresensi $izin): Response
    {
        if(!$izin->berkas || !Storage::disk('public')->exists($izin->berkas))
            abort(404);
        
        $filePath = Storage::disk('public')->path($izin->berkas);
        
        return response()->file($filePath);
    }

    /* -------------- HELPER -------------- */
    private function validateData(Request $r): array
    {
        return $r->validate([
            'karyawan_id'   => ['required','exists:karyawans,id'],
            'tipe_ijin'     => ['required',Rule::in(['PENUH','PARSIAL','TERLAMBAT','PULANG CEPAT','LAINNYA'])],
            'tanggal_awal'  => ['required','date'],
            'tanggal_akhir' => ['nullable','date','after_or_equal:tanggal_awal'],
            'jenis_ijin'    => ['required','string'],
            'berkas'        => ['required','mimes:pdf,jpg,jpeg,png','max:2048'],
            'keterangan'    => ['nullable','string'],
        ]);
    }

    /**
     * Cek apakah ada data izin yang overlap dengan tanggal yang diinput
     * @return IzinPresensi|null
     */
    private function checkOverlapped(array $data, ?int $except=null): ?IzinPresensi
    {
        $a = $data['tanggal_awal']; 
        $b = $data['tanggal_akhir'];
        
        return IzinPresensi::where('karyawan_id', $data['karyawan_id'])
            ->when($except, fn($q) => $q->where('id', '!=', $except))
            ->where(function($q) use ($a, $b) {
                // Cek apakah tanggal_awal berada di antara range yang sudah ada
                $q->whereBetween('tanggal_awal', [$a, $b])
                  // Atau tanggal_akhir berada di antara range yang sudah ada
                  ->orWhereBetween('tanggal_akhir', [$a, $b])
                  // Atau range baru berada di dalam range yang sudah ada
                  ->orWhere(fn($x) => $x->where('tanggal_awal', '<=', $a)
                                        ->where('tanggal_akhir', '>=', $b));
            })
            ->first();
    }
}
