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
        $data = $this->validateData($r);
        $data['tanggal_akhir'] = $data['tanggal_akhir'] ?: $data['tanggal_awal'];

        if($r->file('berkas')){
            $data['berkas'] = $r->file('berkas')->store('izin_presensi','public');
        }

        $this->deleteOverlapped($data);
        IzinPresensi::create($data);

        return back()->with('success','Izin disimpan.');
    }

    /* ---------------------------------------------------- UPDATE (PUT) */
    public function update(Request $r, IzinPresensi $izin_presensi)
    {
        $data = $this->validateData($r);
        $data['tanggal_akhir'] = $data['tanggal_akhir'] ?: $data['tanggal_awal'];

        if($r->file('berkas')){
            if($izin_presensi->berkas)
                Storage::disk('public')->delete($izin_presensi->berkas);
            $data['berkas'] = $r->file('berkas')->store('izin_presensi','public');
        }

        $this->deleteOverlapped($data,$izin_presensi->id);
        $izin_presensi->update($data);

        return back()->with('success','Izin diperbarui.');
    }

    /* ---------------------------------------------------- DESTROY (DELETE) */
    public function destroy(IzinPresensi $izin_presensi)
    {
        if($izin_presensi->berkas)
            Storage::disk('public')->delete($izin_presensi->berkas);

        $izin_presensi->delete();
        return back()->with('success','Izin dihapus.');
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
            'berkas'        => ['nullable','mimes:pdf,jpg,jpeg,png','max:2048'],
            'keterangan'    => ['nullable','string'],
        ]);
    }

    private function deleteOverlapped(array $data, ?int $except=null): void
    {
        IzinPresensi::where('karyawan_id',$data['karyawan_id'])
            ->when($except,fn($q)=>$q->where('id','!=',$except))
            ->where(function($q)use($data){
                $a=$data['tanggal_awal']; $b=$data['tanggal_akhir'];
                $q->whereBetween('tanggal_awal',[$a,$b])
                  ->orWhereBetween('tanggal_akhir',[$a,$b])
                  ->orWhere(fn($x)=>$x->where('tanggal_awal','<=',$a)
                                      ->where('tanggal_akhir','>=',$b));
            })->delete();
    }
}
