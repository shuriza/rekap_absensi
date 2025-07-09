<?php

namespace App\Http\Controllers;

use App\Models\IzinPresensi;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class IzinPresensiController extends Controller
{
    /** Tampil list izin */
    public function index()
    {
        $data = IzinPresensi::with('karyawan')
            ->latest('tanggal_awal')
            ->paginate(10);

        return view('izin_presensi.index', compact('data'));
    }

    /** Form create */
    public function create()
    {
        $listJenis = [
            'DL (DINAS LUAR) [TIDAK ADA PENGURANGAN]',
            'PDK (PENDIDIKAN) [TIDAK DAPAT TPP]',
            'SAKIT (1 HARI) [SURAT DOKTER]',
            'CB (CUTI BESAR) [4.5% / hari]',
        ];
        $tipeIjin  = ['Ijin Penuh','Ijin Setengah','Terlambat','Pulang Cepat'];
        $karyawans = Karyawan::orderBy('nama')->get(['id','nama']);

        return view('izin_presensi.create', compact('listJenis','tipeIjin','karyawans'));
    }

    /** Simpan izin */
    public function store(Request $request)
    {
        $data = $request->validate([
            'karyawan_id'   => ['required','exists:karyawans,id'],
            'tipe_ijin'     => ['required', Rule::in(['Ijin Penuh','Ijin Setengah','Terlambat','Pulang Cepat'])],
            'tanggal_awal'  => ['required','date'],
            'tanggal_akhir' => ['nullable','date','after_or_equal:tanggal_awal'],
            'jenis_ijin'    => ['required','string'],
            'berkas'        => ['nullable','file','mimes:pdf,jpg,png','max:2048'],
            'keterangan'    => ['nullable','string'],
        ]);

        if ($request->file('berkas')) {
            // simpan di storage/app/public/izin_presensi
            $data['berkas'] = $request->file('berkas')->store('izin_presensi', 'public');
        }

        IzinPresensi::create($data);

        return redirect()->route('izin_presensi.index')
            ->with('success', 'Izin presensi berhasil disimpan.');
    }

    /** Detail */
    public function show(IzinPresensi $izinPresensi)
    {
        return view('izin_presensi.show', compact('izinPresensi'));
    }

    /** Hapus */
    public function destroy(IzinPresensi $izinPresensi)
    {
        if ($izinPresensi->berkas) {
            Storage::disk('public')->delete($izinPresensi->berkas);
        }

        $izinPresensi->delete();

        return redirect()->route('izin_presensi.index')
            ->with('success', 'Izin presensi berhasil dihapus.');
    }

    /** AJAX dropdown karyawan */
    public function searchKaryawan(Request $request)
    {
        $q = $request->get('q','');
        $list = Karyawan::where('nama','like',"%{$q}%")
                 ->limit(10)->get(['id','nama']);

        return response()->json([
            'results' => $list->map(fn($k) => [
                'id'   => $k->id,
                'text' => $k->nama,
            ]),
        ]);
    }
}