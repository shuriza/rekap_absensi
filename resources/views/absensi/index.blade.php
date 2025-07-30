{{-- resources/views/absensi/index.blade.php --}}
@extends('layouts.app')
@push('styles')
  <!-- Flatpickr sudah bisa dihilangkan kalau tidak dipakai -->
  <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.tailwindcss.css" />
@endpush

@push('scripts')
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <!-- DataTables & Tailwind integration -->
  <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
  <script src="https://cdn.datatables.net/2.3.2/js/dataTables.tailwindcss.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      $('#absensiTable').DataTable({
        dom: 't',
        ordering: true,
        stateSave: true,
        pageLength: 40,
        columnDefs: [{
          targets: [0, 4, 5, 6],
          orderable: false
        }],
        responsive: true
      });
    });
  </script>
@endpush

@section('content')
  <div class="w-full mx-auto mt-10 space-y-6">
    {{-- Filter Jam & Upload --}}
    <div class="bg-white p-6 rounded-xl shadow border">
      <h2 class="text-lg font-semibold mb-2">⏰ Filter Jam Masuk & Pulang</h2>

      @if (session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
      @endif
      @if (session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ session('error') }}</div>
      @endif
      @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
          <ul class="list-disc ml-5">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('absensi.preview') }}" enctype="multipart/form-data">
        @csrf
        {{-- Senin – Kamis --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">{{-- resources/views/absensi/index.blade.php --}}

          {{-- Senin – Kamis --}}
          <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Filter Jam Absensi: Senin – Kamis</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {{-- Masuk Minimal --}}
              <div>
                <label class="block text-sm font-medium text-gray-700">Masuk Minimal</label>
                <div class="mt-1 relative">
                  <input type="time" name="jam_masuk_min_senin"
                    value="{{ old('jam_masuk_min_senin', '07:00') }}"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm
                 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>

              {{-- Masuk Maksimal --}}
              <div>
                <label class="block text-sm font-medium text-gray-700">Masuk Maksimal</label>
                <div class="mt-1 relative">
                  <input type="time" name="jam_masuk_max_senin"
                    value="{{ old('jam_masuk_max_senin', '07:30') }}"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm
                 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>

              {{-- Pulang Minimal --}}
              <div>
                <label class="block text-sm font-medium text-gray-700">Pulang Minimal</label>
                <div class="mt-1 relative">
                  <input type="time" name="jam_pulang_min_senin"
                    value="{{ old('jam_pulang_min_senin', '15:30') }}"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm
                 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>

              {{-- Pulang Maksimal --}}
              <div>
                <label class="block text-sm font-medium text-gray-700">Pulang Maksimal</label>
                <div class="mt-1 relative">
                  <input type="time" name="jam_pulang_max_senin"
                    value="{{ old('jam_pulang_max_senin', '17:00') }}"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm
                 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Jumat --}}
          <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Filter Jam Absensi: Jumat</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {{-- Masuk Minimal --}}
              <div>
                <label class="block text-sm font-medium text-gray-700">Masuk Minimal</label>
                <div class="mt-1 relative">
                  <input type="time" name="jam_masuk_min_jumat"
                    value="{{ old('jam_masuk_min_jumat', '07:00') }}"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm
                 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>

              {{-- Masuk Maksimal --}}
              <div>
                <label class="block text-sm font-medium text-gray-700">Masuk Maksimal</label>
                <div class="mt-1 relative">
                  <input type="time" name="jam_masuk_max_jumat"
                    value="{{ old('jam_masuk_max_jumat', '07:30') }}"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm
                 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>

              {{-- Pulang Minimal --}}
              <div>
                <label class="block text-sm font-medium text-gray-700">Pulang Minimal</label>
                <div class="mt-1 relative">
                  <input type="time" name="jam_pulang_min_jumat"
                    value="{{ old('jam_pulang_min_jumat', '15:00') }}"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm
                 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>

              {{-- Pulang Maksimal --}}
              <div>
                <label class="block text-sm font-medium text-gray-700">Pulang Maksimal</label>
                <div class="mt-1 relative">
                  <input type="time" name="jam_pulang_max_jumat"
                    value="{{ old('jam_pulang_max_jumat', '17:00') }}"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm
                 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- UPLOAD FILE --}}
          <div class="mb-8">
            <label class="block text-sm font-medium text-gray-700">Pilih File Excel</label>
            <div class="mt-2 flex items-center space-x-4">
              {{-- Tombol Tambah File --}}
              <button type="button" onclick="document.getElementById('file_input').click()"
                class="inline-flex items-center px-4 py-2 bg-white border-2 border-indigo-500 rounded-lg
               text-indigo-600 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none"
                  viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4v16m8-8H4" />
                </svg>
                Tambah File
              </button>

              {{-- Jumlah file --}}
              <span id="file_count" class="text-gray-600 text-sm">0 file</span>
            </div>

            {{-- real input, disembunyikan --}}
            <input id="file_input" type="file" name="file_excel[]" multiple class="hidden" />

            {{-- Daftar file --}}
            <ul id="file_list"
              class="mt-4 border border-gray-200 rounded-lg divide-y divide-gray-200 bg-white">
              {{-- JS akan render <li> di sini --}}
            </ul>

            {{-- Tombol Preview di bawah --}}
            <button type="submit"
              class="mt-6 w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold
             rounded-lg shadow focus:outline-none focus:ring-2 focus:ring-blue-400">
              Preview Data
            </button>
          </div>
      </form>

      @push('scripts')
        <script>
          document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('file_input');
            const fileList = document.getElementById('file_list');
            const fileCount = document.getElementById('file_count');
            const dt = new DataTransfer();

            function render() {
              // update count
              fileCount.textContent = `${dt.files.length} file`;
              // rebuild list
              fileList.innerHTML = '';
              Array.from(dt.files).forEach((file, i) => {
                const li = document.createElement('li');
                li.className = 'flex justify-between items-center px-4 py-2';
                li.innerHTML = `
        <span class="text-gray-800 text-sm">${file.name}</span>
        <button type="button" class="text-red-500 hover:text-red-700" data-idx="${i}">
          &times;
        </button>
      `;
                fileList.appendChild(li);
              });
            }

            fileInput.addEventListener('change', () => {
              // tambahkan file baru ke DataTransfer
              for (const f of fileInput.files) {
                dt.items.add(f);
              }
              fileInput.files = dt.files;
              render();
            });

            fileList.addEventListener('click', e => {
              if (e.target.matches('button[data-idx]')) {
                const idx = Number(e.target.dataset.idx);
                dt.items.remove(idx);
                fileInput.files = dt.files;
                render();
              }
            });
          });
        </script>
      @endpush
      </form>
    </div>

    {{-- Preview Table --}}
    @if (!empty($preview) && $preview->count())
      <div class="bg-white p-6 rounded-xl shadow border">
        <h2 class="text-xl font-bold mb-4">📄 Preview Data Absensi</h2>
        <p class="text-sm text-gray-600 mb-2">Menampilkan {{ $preview->total() }} data absensi.</p>

        <form method="GET" action="{{ route('absensi.preview') }}"
          class="mb-4 md:flex-row gap-2 md:items-center justify-between">
          <input type="text" name="search" placeholder="Cari nama..."
            value="{{ request('search') }}" class="border p-2 rounded w-full md:w-1/3" />
          <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            Cari
          </button>
        </form>

        <form method="POST" action="{{ route('absensi.store') }}">
          @csrf
          <table id="absensiTable" class="min-w-full text-sm">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-2 py-1 text-left">No</th>
                <th class="px-2 py-1 text-left">Nama</th>
                <th class="px-2 py-1 text-left">Departemen</th>
                <th class="px-2 py-1 text-left">Tanggal</th>
                <th class="px-2 py-1 text-left">Jam Masuk</th>
                <th class="px-2 py-1 text-left">Jam Pulang</th>
                <th class="px-2 py-1 text-left">Keterangan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              @foreach ($preview as $row)
                <tr>
                  <td class="px-2 py-1">
                    {{ ($preview->currentPage() - 1) * $preview->perPage() + $loop->iteration }}
                  </td>
                  <td class="px-2 py-1">{{ $row['nama'] }}</td>
                  <td class="px-2 py-1">{{ $row['departemen'] }}</td>
                  <td class="px-2 py-1">{{ $row['tanggal'] }}</td>
                  <td class="px-2 py-1">{{ $row['jam_masuk'] }}</td>
                  <td class="px-2 py-1">{{ $row['jam_pulang'] }}</td>
                  <td class="px-2 py-1">{{ $row['keterangan'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>

          <div class="flex items-center justify-between flex-col">
            <button type="submit"
              class="bg-green-600 text-white px-4 py-2 my-4 rounded hover:bg-green-700">
              Simpan ke Database
            </button>
            <p class="text-sm text-gray-600 my-4">
              Showing {{ $preview->firstItem() }} to {{ $preview->lastItem() }} of
              {{ $preview->total() }} results
            </p>
            <div class="mt-2">{{ $preview->links() }}</div>
          </div>
        </form>
      </div>
    @elseif(isset($preview))
      <div class="bg-white p-6 rounded-xl shadow border text-gray-500 italic">
        Tidak ada data absensi yang bisa ditampilkan.
      </div>
    @endif
  </div>
@endsection
