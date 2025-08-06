<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\NonaktifKaryawan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KaryawanController extends Controller
{
    public function index(Request $request)
    {
        $query = Karyawan::with('nonaktif_terbaru');

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%'.$request->search.'%');
        }

        $karyawans = $query->get();

        return view('absensi.karyawan', compact('karyawans'));
    }

    public function nonaktifkan(Request $request, $id)
    {
        $karyawan = Karyawan::findOrFail($id);

        // Cegah duplikasi jika sudah nonaktif
        $existing = NonaktifKaryawan::where('karyawan_id', $id)
            ->whereNull('tanggal_akhir')
            ->first();

        if (!$existing) {
            NonaktifKaryawan::create([
                'karyawan_id'  => $id,
                'tanggal_awal' => now(),
                'tanggal_akhir'=> null,
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'tanggal_awal' => now()->translatedFormat('d M Y H:i'),
                'message' => 'Karyawan dinonaktifkan.',
            ]);
        }

        return redirect()->route('absensi.karyawan')->with('success', 'Karyawan dinonaktifkan.');
    }

    public function aktifkan(Request $request, $id)
    {
        $karyawan = Karyawan::findOrFail($id);

        // Update record nonaktif terakhir
        NonaktifKaryawan::where('karyawan_id', $id)
            ->whereNull('tanggal_akhir')
            ->update(['tanggal_akhir' => now()]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'tanggal_akhir' => now()->translatedFormat('d M Y H:i'),
                'message' => 'Karyawan diaktifkan kembali.'
            ]);
        }

        return redirect()->route('absensi.karyawan')->with('success', 'Karyawan diaktifkan kembali.');
    }
}
