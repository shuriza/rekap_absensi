    @extends('layouts.app')

    @section('content')
    <div class="min-h-screen flex flex-col px-6 py-4">
        <h1 class="text-lg font-semibold mb-4">
            Laporan Detail Absensi Dinas Penanaman Modal & Pelayanan Terpadu Satu Pintu
        </h1>

        {{-- Filter Bar --}}
        <form method="GET" class="flex flex-wrap items-end gap-4 mb-6">
        {{-- Bulan --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Bulan</label>
                <select name="bulan" class="mt-1 block w-40 rounded border-gray-300 shadow-sm text-sm">
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $bulan == $i ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            {{-- Tahun --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Tahun</label>
                <select name="tahun" class="mt-1 block w-28 rounded border-gray-300 shadow-sm text-sm">
                    @for ($y = 2022; $y <= now()->year; $y++)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            {{-- Unit Kerja --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Unit Kerja</label>
                <select name="unit" class="mt-1 block w-80 rounded border-gray-300 shadow-sm text-sm">
                    <option value="">-- Semua Unit --</option>
                    <option value="DPMPTSP" {{ request('unit') == 'DPMPTSP' ? 'selected' : '' }}>
                        Dinas Penanaman Modal & Pelayanan Terpadu Satu Pintu
                    </option>
                </select>
            </div>

            {{-- Cari Nama (auto-submit on input) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Cari Nama</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama pegawai..."
                    oninput="this.form.submit()"
                    class="mt-1 block w-64 rounded border-gray-300 shadow-sm text-sm" />
            </div>

        <div>
                <label class="block text-sm font-medium text-gray-700">Segment Tanggal</label>
                <select name="segment" class="mt-1 block w-44 rounded border-gray-300 shadow-sm text-sm" onchange="this.form.submit()">
                    <option value="1" {{ request('segment', 1) == 1 ? 'selected' : '' }}>Tanggal 1–10</option>
                    <option value="2" {{ request('segment') == 2 ? 'selected' : '' }}>Tanggal 11–20</option>
                    <option value="3" {{ request('segment') == 3 ? 'selected' : '' }}>Tanggal 21–{{ \Carbon\Carbon::create($tahun, $bulan)->daysInMonth }}</option>
                </select>
                </div>


        </form>
        @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
        @endpush

        @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

        <script>
            $(document).ready(function () {
                $('#tabel-rekap').DataTable({
                    dom: 'Bfrtip',
                    buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                    paging: false,
                    ordering: false,
                    searching: false
                });
            });
        </script>
        @endpush


        <a href="{{ route('rekap.export.bulanan', ['bulan' => $bulan, 'tahun' => $tahun]) }}"
            class="bg-green-600 hover:bg-green-700 text-white w-52   px-4 py-2 rounded text-sm mb-4 inline-block">
            📤 Export Excel Bulanan ({{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }})
        </a>

        <a href="{{ route('rekap.export.tahunan', ['tahun' => $tahun]) }}"
            class="bg-green-600 hover:bg-green-700 text-white w-52 text-sm px-4 py-2 rounded inline-block mb-4">
            📁 Export Excel Tahunan ({{ $tahun }})
        </a>




        {{-- Tabel Data --}}
        <div class="overflow-x-auto border border-gray-300 rounded">
            <table id="tabel-rekap" class="min-w-full text-sm text-center border-collapse display nowrap">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="border px-2 py-2">No</th>
                        <th class="border px-2 py-2">NIP</th>
                        <th class="border px-2 py-2">Nama</th>
                        <th class="border px-2 py-2">Jenjang Jabatan</th>
                        @foreach ($tanggalList as $tgl)
                            <th class="border px-2 py-2">{{ $tgl }}</th>
                        @endforeach
                        <th class="border px-2 py-2">Total Akumulasi</th>
                    </tr>
                </thead>
            <tbody class="bg-white text-gray-800">
                    @foreach ($pegawaiList as $pegawai)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-2 py-1">{{ $loop->iteration }}</td>
                        <td class="border px-2 py-1">{{ $pegawai->nip }}</td>
                        <td class="border px-2 py-1">{{ $pegawai->nama }}</td>
                        <td class="border px-2 py-1">{{ $pegawai->jabatan }}</td>

                        {{-- Loop tanggal --}}
                        @foreach ($tanggalList as $tgl)
                            @php
                                $tanggalFull = sprintf('%04d-%02d-%02d', $tahun, $bulan, $tgl);
                                $absen = $pegawai->absensi->firstWhere('tanggal', $tanggalFull);
                            @endphp
                            <td class="border px-1 py-1 {{ $absen && $absen->keterangan ? 'bg-yellow-100 font-semibold text-xs' : '' }}">
                                @if ($absen)
                                    @if ($absen->keterangan)
                                        <span class="text-xs">{{ $absen->keterangan }}</span>
                                    @else
                                        <div class="text-xs">
                                            @php
                                                $jamMasuk = $absen->jam_masuk ? substr($absen->jam_masuk, 0, 5) : null;
                                                $jamPulang = $absen->jam_pulang ? substr($absen->jam_pulang, 0, 5) : null;
                                            @endphp
                                            {{ $jamMasuk && $jamPulang ? "$jamMasuk - $jamPulang" : ($jamMasuk ?: '-') }}
                                        </div>
                                    @endif
                                @else
                                    /
                                @endif
                            </td>
                        @endforeach

                        {{-- Kolom akumulasi hadir --}}
                    <td class="border px-2 py-1 text-xs">
                            @php
                                $jam = str_pad(floor($pegawai->total_menit / 60), 2, '0', STR_PAD_LEFT);
                                $menit = str_pad($pegawai->total_menit % 60, 2, '0', STR_PAD_LEFT);
                            @endphp
                            {{ $jam }}:{{ $menit }}
                        </td>

                    </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex justify-between items-center mt-4 text-sm text-gray-600">
            <div>
                Showing {{ $pegawaiList->firstItem() }} to {{ $pegawaiList->lastItem() }} of {{ $pegawaiList->total() }} entries
            </div> 
        </div>


    </div>
    {{-- Footer --}}
    <footer class="text-center py-4 text-sm text-gray-600">
            Dinas Penanaman Modal & Pelayanan Terpadu Satu Pintu 
            ({{ $tahun }} - {{ \Carbon\Carbon::create()->month((int) $bulan)->translatedFormat('F') }})
    </footer>
    @endsection
