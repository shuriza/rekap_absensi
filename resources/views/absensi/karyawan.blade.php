@extends('layouts.app')
@section('head')
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <style>
    #modal-nonaktif {
      align-items: center;
      justify-content: center;
    }
  </style>
@endsection

@section('content')
  <div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Daftar Karyawan</h1>

    {{-- Form Pencarian --}}
    <div class="mb-4">
      <form action="{{ route('absensi.karyawan') }}" method="GET" class="flex items-center gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
          placeholder="Cari nama karyawan..." class="border px-3 py-2 rounded w-1/3" />
        <button type="submit"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Cari</button>
        @if (request('search'))
          <a href="{{ route('absensi.karyawan') }}"
            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Reset</a>
        @endif
      </form>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
      <div class="mb-4 p-2 bg-green-200 text-green-700 rounded">
        {{ session('success') }}
      </div>
    @endif

    {{-- Tabel Karyawan --}}
    <table class="w-full border border-gray-300">
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2 border">Nama</th>
          <th class="p-2 border">Departemen</th>
          <th class="p-2 border">Masa Nonaktif</th>
          <th class="p-2 border">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($karyawans as $karyawan)
          <tr data-karyawan-id="{{ $karyawan->id }}">
            <td class="p-2 border">{{ $karyawan->nama }}</td>
            <td class="p-2 border">{{ $karyawan->departemen }}</td>

            {{-- Kolom Masa Nonaktif --}}
            <td class="p-2 border text-center">
              @if ($karyawan->sedang_nonaktif)
                <div>
                  <span class="block text-sm">
                    {{ \Carbon\Carbon::parse($karyawan->nonaktif_terbaru->tanggal_awal)->translatedFormat('d M Y') }}
                    —
                    {{ \Carbon\Carbon::parse($karyawan->nonaktif_terbaru->tanggal_akhir)->translatedFormat('d M Y') }}
                  </span>
                  <span class="text-red-600 font-semibold text-sm">(Nonaktif)</span>
                </div>
              @else
                <span class="text-green-600 font-semibold">Aktif</span>
              @endif
            </td>

            {{-- Tombol Aksi --}}
            <td class="p-2 border">
              @if ($karyawan->sedang_nonaktif)
                <button type="button" onclick="showActivateModal({{ $karyawan->id }})"
                  class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">
                  Aktifkan
                </button>
              @else
                <button type="button" onclick="showModal({{ $karyawan->id }})"
                  class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                  Nonaktifkan
                </button>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="text-center p-4 text-gray-500">Data tidak ditemukan.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Modal Nonaktifkan --}}
  <div id="modal-nonaktif"
    class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-8 rounded-xl shadow max-w-xl w-full space-y-6">
      <h2 class="text-2xl font-bold text-center">Nonaktifkan Karyawan</h2>

      <form id="nonaktif-form" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="id" id="karyawan-id" />

        <div id="form-errors" class="text-red-600 text-sm"></div>

        {{-- Input Tanggal Awal & Akhir --}}
        <div class="grid sm:grid-cols-2 gap-6">
          <div>
            <label class="block text-base font-semibold mb-2">Tanggal Awal</label>
            <input id="tanggal-awal" type="text" name="tanggal_awal" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-lg cursor-pointer"
              placeholder="dd/mm/yyyy">
          </div>
          <div>
            <label class="block text-base font-semibold mb-2">Tanggal Akhir</label>
            <input id="tanggal-akhir" type="text" name="tanggal_akhir" required
              class="w-full px-4 py-3 border border-gray-300 rounded-lg text-lg cursor-pointer"
              placeholder="dd/mm/yyyy">
          </div>
        </div>

        {{-- Tombol --}}
        <div class="flex justify-end gap-4 pt-2">
          <button type="button" onclick="hideModal()"
            class="px-5 py-2 bg-gray-300 text-gray-700 font-semibold rounded hover:bg-gray-400">Batal</button>
          <button type="submit"
            class="px-5 py-2 bg-red-600 text-white font-semibold rounded hover:bg-red-700">Nonaktifkan</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Modal Konfirmasi Aktivasi --}}
  <div id="modalConfirmActivate"
    class="fixed inset-0 z-50 hidden bg-gray-900 bg-opacity-60 flex items-center justify-center">
    <div class="relative top-0 mx-auto shadow-xl rounded-md bg-white max-w-md w-full">
      <div class="flex justify-end p-2">
        <button onclick="closeModal('modalConfirmActivate')" type="button"
          class="text-gray-400 hover:bg-gray-200 rounded-lg p-1.5" aria-label="Tutup">
          &times;
        </button>
      </div>

      <div class="p-6 pt-0 text-center">
        <!-- Icon hijau untuk aktivasi -->
        <svg class="w-20 h-20 text-green-600 mx-auto" fill="none" stroke="currentColor"
          viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12l2 2l4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <h3 class="text-xl font-semibold text-gray-800 mt-5 mb-2">
          Aktifkan Karyawan
        </h3>
        <p class="text-base text-gray-600 mb-6">
          Yakin ingin mengaktifkan kembali karyawan ini? Masa nonaktif akan dihapus dan
          status otomatis menjadi <span class="font-semibold">Aktif</span>.
        </p>

        <div class="flex justify-center gap-3">
          <button type="button" id="confirm-activate-btn"
            class="text-white bg-green-600 hover:bg-green-800 rounded-lg px-5 py-2.5 font-medium">
            Ya, aktifkan
          </button>
          <button type="button" onclick="closeModal('modalConfirmActivate')"
            class="text-gray-900 bg-white hover:bg-gray-100 border border-gray-200
                        rounded-lg px-5 py-2.5 font-medium">
            Batal
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- Script --}}
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    const nonaktifUrlTemplate = "{{ url('karyawan') }}/:id/nonaktif";
    const activateUrlBase = "{{ url('karyawan') }}"; // base untuk /karyawan/{id}/aktifkan
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Modal nonaktif
    function showModal(id) {
      const modal = document.getElementById('modal-nonaktif');
      modal.classList.remove('hidden');
      modal.classList.add('flex');

      document.getElementById('karyawan-id').value = id;
      document.getElementById('nonaktif-form').action = nonaktifUrlTemplate.replace(':id', id);
      document.getElementById('form-errors').innerHTML = '';
      document.getElementById('tanggal-awal').value = '';
      document.getElementById('tanggal-akhir').value = '';
    }

    function hideModal() {
      const modal = document.getElementById('modal-nonaktif');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    flatpickr("#tanggal-awal", {
      dateFormat: "Y-m-d",
      altInput: true,
      altFormat: "d/m/Y"
    });

    flatpickr("#tanggal-akhir", {
      dateFormat: "Y-m-d",
      altInput: true,
      altFormat: "d/m/Y"
    });

    // Submit nonaktif via AJAX
    document.getElementById('nonaktif-form').addEventListener('submit', async function(e) {
      e.preventDefault();
      const form = e.currentTarget;
      const id = document.getElementById('karyawan-id').value;
      const url = form.action;
      const formData = new FormData(form);

      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: formData
        });

        if (!res.ok) {
          const errorData = await res.json().catch(() => null);
          let msg = 'Terjadi kesalahan.';
          if (errorData && errorData.errors) {
            const errs = Object.values(errorData.errors).flat();
            msg = errs.map(e => `<div>${e}</div>`).join('');
          } else if (errorData && errorData.message) {
            msg = `<div>${errorData.message}</div>`;
          } else {
            msg = await res.text();
          }
          document.getElementById('form-errors').innerHTML = msg;
          return;
        }

        const data = await res.json();

        // Update baris
        const row = document.querySelector(`tr[data-karyawan-id="${id}"]`);
        if (row) {
          const statusCell = row.querySelector('td:nth-child(3)');
          statusCell.innerHTML = `
            <div>
              <span class="block text-sm">${data.tanggal_awal} — ${data.tanggal_akhir}</span>
              <span class="text-red-600 font-semibold text-sm">(Nonaktif)</span>
            </div>
          `;

          const aksiCell = row.querySelector('td:nth-child(4)');
          aksiCell.innerHTML = `
            <button type="button" onclick="showActivateModal(${id})"
              class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">
              Aktifkan
            </button>
          `;
        }

        hideModal();
      } catch (err) {
        document.getElementById('form-errors').innerHTML = `<div>${err.message}</div>`;
      }
    });

    // Modal aktivasi
    let pendingActivateId = null;

    function showActivateModal(id) {
      pendingActivateId = id;
      const modal = document.getElementById('modalConfirmActivate');
      modal.classList.remove('hidden');
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      if (modal) modal.classList.add('hidden');
    }

    document.getElementById('confirm-activate-btn').addEventListener('click', async function() {
      if (!pendingActivateId) return;
      const url = `${activateUrlBase}/${pendingActivateId}/aktifkan`;

      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
        });

        if (!res.ok) {
          alert('Gagal mengaktifkan karyawan.');
          return;
        }

        // Update baris
        const row = document.querySelector(`tr[data-karyawan-id="${pendingActivateId}"]`);
        if (row) {
          const statusCell = row.querySelector('td:nth-child(3)');
          statusCell.innerHTML = `<span class="text-green-600 font-semibold">Aktif</span>`;

          const aksiCell = row.querySelector('td:nth-child(4)');
          aksiCell.innerHTML = `
            <button type="button" onclick="showModal(${pendingActivateId})"
              class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
              Nonaktifkan
            </button>
          `;
        }

        closeModal('modalConfirmActivate');
        pendingActivateId = null;
      } catch (err) {
        alert('Terjadi kesalahan: ' + err.message);
      }
    });
  </script>
@endsection
