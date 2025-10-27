# 🔒 REVISI - VALIDASI DUPLIKAT DATA IZIN

## 🎯 Tujuan Revisi

Mencegah penambahan data izin yang **overlap/duplikat** dengan data izin yang sudah ada, dan memberikan **peringatan yang jelas** kepada user tanpa menghapus data yang sudah ada.

---

## 📋 Masalah Sebelumnya

### **SEBELUM:**
- Sistem menggunakan method `deleteOverlapped()` yang **otomatis menghapus** data izin yang overlap
- Tidak ada peringatan kepada user
- Data yang sudah ada bisa hilang tanpa pemberitahuan
- User tidak tahu bahwa data overlap

### **RISIKO:**
```
User A: Input izin tanggal 1-5 Jan → Tersimpan ✓
User B: Input izin tanggal 3-7 Jan → Data User A TERHAPUS ❌
                                      Data User B tersimpan
```

---

## ✅ Solusi yang Diterapkan

### **SEKARANG:**
- Sistem menggunakan method `checkOverlapped()` yang **hanya mengecek** tanpa menghapus
- Memberikan **peringatan yang informatif** kepada user
- Data yang sudah ada **tetap aman**
- User mendapat informasi detail tentang data yang sudah ada

### **ALUR BARU:**
```
User A: Input izin tanggal 1-5 Jan → Tersimpan ✓
User B: Input izin tanggal 3-7 Jan → ⚠️ PERINGATAN:
        "Data izin sudah ada! Karyawan [Nama] sudah memiliki 
         izin [Jenis] pada tanggal 01-01-2025 s/d 05-01-2025."
        
        → Data User A TETAP ADA ✓
        → Data User B TIDAK TERSIMPAN
        → Form tetap terbuka dengan data yang sudah diisi
```

---

## 🔧 Perubahan Teknis

### **1. Method STORE - Tambah Validasi Overlap**

**File**: `app/Http/Controllers/IzinPresensiController.php`

**SEBELUM:**
```php
public function store(Request $r)
{
    try {
        $data = $this->validateData($r);
        $data['tanggal_akhir'] = $data['tanggal_akhir'] ?: $data['tanggal_awal'];

        if($r->file('berkas')){
            $data['berkas'] = $r->file('berkas')->store('izin_presensi','public');
        }

        $this->deleteOverlapped($data);  // ❌ MENGHAPUS DATA YANG ADA
        IzinPresensi::create($data);

        return redirect()->route('izin_presensi.index')
                        ->with('success','✓ Data izin berhasil ditambahkan.');
    } catch (\Exception $e) {
        return back()->with('error','✗ Gagal menambahkan data izin. '.$e->getMessage())
                     ->withInput();
    }
}
```

**SESUDAH:**
```php
public function store(Request $r)
{
    try {
        $data = $this->validateData($r);
        $data['tanggal_akhir'] = $data['tanggal_akhir'] ?: $data['tanggal_awal'];

        // ✅ CEK OVERLAP TANPA MENGHAPUS
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

        return redirect()->route('izin_presensi.index')
                        ->with('success','✓ Data izin berhasil ditambahkan.');
    } catch (\Exception $e) {
        return back()->with('error','✗ Gagal menambahkan data izin. '.$e->getMessage())
                     ->withInput();
    }
}
```

---

### **2. Method UPDATE - Tambah Validasi Overlap**

**SESUDAH:**
```php
public function update(Request $r, IzinPresensi $izin_presensi)
{
    try {
        $data = $this->validateData($r);
        $data['tanggal_akhir'] = $data['tanggal_akhir'] ?: $data['tanggal_awal'];

        // ✅ CEK OVERLAP (kecuali data yang sedang di-edit)
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
        return back()->with('error','✗ Gagal memperbarui data izin. '.$e->getMessage())
                     ->withInput();
    }
}
```

---

### **3. Helper Method Baru - checkOverlapped()**

**HAPUS:**
```php
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
        })->delete();  // ❌ MENGHAPUS!
}
```

**TAMBAH:**
```php
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
        ->first();  // ✅ HANYA CEK, TIDAK HAPUS!
}
```

**Perbedaan Utama:**
- Return type: `void` → `?IzinPresensi` (mengembalikan data yang overlap atau null)
- Action: `->delete()` → `->first()` (hanya mengambil data, tidak menghapus)

---

## 🔍 Logika Pengecekan Overlap

Method `checkOverlapped()` mengecek 3 kondisi overlap:

### **1. Tanggal Awal Input berada di antara range yang sudah ada**
```
Existing: [====== 1-5 Jan ======]
Input:         [==== 3-7 Jan ====]
               ↑ tanggal_awal (3 Jan) di antara 1-5
Result: OVERLAP ⚠️
```

### **2. Tanggal Akhir Input berada di antara range yang sudah ada**
```
Existing:      [====== 5-10 Jan ======]
Input:    [==== 3-7 Jan ====]
                         ↑ tanggal_akhir (7 Jan) di antara 5-10
Result: OVERLAP ⚠️
```

### **3. Range Input mencakup/berada di dalam range yang sudah ada**
```
Existing:   [==== 3-7 Jan ====]
Input:    [====== 1-10 Jan ======]
           ↑ mencakup semua tanggal yang sudah ada
Result: OVERLAP ⚠️
```

---

## 📊 Contoh Kasus Penggunaan

### **Skenario 1: Data Overlap - Tambah Izin Baru**

**Data yang sudah ada:**
- Karyawan: Budi Santoso
- Jenis Izin: S - SAKIT
- Tanggal: 01-12-2025 s/d 05-12-2025

**User coba input:**
- Karyawan: Budi Santoso
- Jenis Izin: DL - DINAS LUAR
- Tanggal: 03-12-2025 s/d 07-12-2025

**Hasil:**
```
⚠️ Data izin sudah ada! 
Karyawan Budi Santoso sudah memiliki izin S - SAKIT 
pada tanggal 01-12-2025 s/d 05-12-2025.
```
- Form tetap terbuka
- Data yang diisi masih ada (withInput)
- Data yang sudah ada TETAP ADA
- User bisa ubah tanggal atau batalkan

---

### **Skenario 2: Data Tidak Overlap - Berhasil**

**Data yang sudah ada:**
- Karyawan: Budi Santoso
- Tanggal: 01-12-2025 s/d 05-12-2025

**User input:**
- Karyawan: Budi Santoso
- Tanggal: 10-12-2025 s/d 12-12-2025

**Hasil:**
```
✓ Data izin berhasil ditambahkan.
```
- Redirect ke halaman index
- Notifikasi sukses muncul
- Data tersimpan

---

### **Skenario 3: Edit Data - Skip Data Sendiri**

**Data yang sedang di-edit (ID: 5):**
- Karyawan: Budi Santoso
- Tanggal: 01-12-2025 s/d 05-12-2025

**User edit jadi:**
- Tanggal: 01-12-2025 s/d 06-12-2025 (tambahin 1 hari)

**Hasil:**
```
✓ Data izin berhasil diperbarui.
```
- System mengecualikan ID 5 dari pengecekan overlap
- Update berhasil
- Tidak terdeteksi sebagai duplikat dengan dirinya sendiri

---

## 🧪 Testing Checklist

### **Test Case 1: Overlap di Awal Range**
- [ ] Data existing: 1-5 Jan
- [ ] Input baru: 3-7 Jan
- [ ] Expected: ⚠️ Peringatan muncul, data tidak tersimpan

### **Test Case 2: Overlap di Akhir Range**
- [ ] Data existing: 5-10 Jan
- [ ] Input baru: 3-7 Jan
- [ ] Expected: ⚠️ Peringatan muncul, data tidak tersimpan

### **Test Case 3: Range Input Mencakup Existing**
- [ ] Data existing: 3-7 Jan
- [ ] Input baru: 1-10 Jan
- [ ] Expected: ⚠️ Peringatan muncul, data tidak tersimpan

### **Test Case 4: Tidak Overlap**
- [ ] Data existing: 1-5 Jan
- [ ] Input baru: 10-15 Jan
- [ ] Expected: ✓ Data berhasil tersimpan

### **Test Case 5: Edit Data Sendiri**
- [ ] Edit data ID 5 (1-5 Jan) jadi (1-6 Jan)
- [ ] Expected: ✓ Update berhasil (tidak terdeteksi overlap dengan diri sendiri)

### **Test Case 6: Karyawan Berbeda**
- [ ] Data existing: Budi (1-5 Jan)
- [ ] Input baru: Ani (1-5 Jan)
- [ ] Expected: ✓ Data berhasil tersimpan (karyawan berbeda)

### **Test Case 7: Tanggal Sama Persis**
- [ ] Data existing: 1-5 Jan
- [ ] Input baru: 1-5 Jan (karyawan sama)
- [ ] Expected: ⚠️ Peringatan muncul

---

## 📁 File yang Dimodifikasi

| File | Method | Perubahan |
|------|--------|-----------|
| `app/Http/Controllers/IzinPresensiController.php` | `store()` | ✅ Tambah validasi `checkOverlapped()` sebelum create |
| `app/Http/Controllers/IzinPresensiController.php` | `update()` | ✅ Tambah validasi `checkOverlapped()` sebelum update |
| `app/Http/Controllers/IzinPresensiController.php` | `deleteOverlapped()` | ❌ DIHAPUS (tidak digunakan lagi) |
| `app/Http/Controllers/IzinPresensiController.php` | `checkOverlapped()` | ✅ DITAMBAH (method baru untuk cek overlap) |

---

## 💡 Keuntungan Perubahan

### **Sebelum:**
❌ Data bisa hilang tanpa peringatan  
❌ User tidak tahu ada data yang terhapus  
❌ Tidak ada konfirmasi  
❌ Risiko kehilangan data tinggi  

### **Sesudah:**
✅ Data yang sudah ada **tetap aman**  
✅ User mendapat **peringatan yang jelas**  
✅ Form tetap terbuka dengan data yang sudah diisi (**withInput**)  
✅ User bisa **ubah tanggal** atau batalkan  
✅ Informasi lengkap tentang data yang sudah ada  

---

## ⚠️ Catatan Penting

1. **Format Tanggal Peringatan**: `d-m-Y` (01-12-2025)
2. **Session Message**: Menggunakan `error` session
3. **withInput**: Memastikan data form tetap ada saat error
4. **Parameter $except**: Penting untuk edit agar tidak terdeteksi overlap dengan diri sendiri
5. **Carbon Format**: Menggunakan `\Carbon\Carbon::parse()` untuk format tanggal

---

## 🚀 Workflow User Sekarang

```
1. User isi form izin
        ↓
2. Submit form
        ↓
3. System cek overlap
        ↓
   ┌────┴────┐
   │         │
 ADA?      TIDAK?
   │         │
   ↓         ↓
⚠️ Error   ✓ Simpan
   │         │
   ↓         ↓
Back with  Redirect
withInput  to index
   │         │
   ↓         ↓
Form tetap Notifikasi
terbuka    sukses
```

---

**Revisi ini memastikan data izin yang sudah ada tidak akan terhapus dan user mendapat feedback yang jelas!** 🎉
