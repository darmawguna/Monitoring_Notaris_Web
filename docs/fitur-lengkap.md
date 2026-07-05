# Fitur Lengkap - NotarisKu

Dokumen ini merangkum seluruh fitur yang sudah selesai dan aktif di sistem.

---

## 1. Manajemen Berkas Peralihan Hak

**Status:** ✅ Selesai

Modul utama untuk mengelola dokumen peralihan hak atas tanah/properti.

### Fitur:
- Input berkas dengan jenis: Jual Beli, Hibah, Tukar Menukar, APHB
- Form dinamis — label berubah sesuai jenis berkas (Penjual/Pembeli → Pemberi/Penerima Hibah, dll)
- Data pihak tersimpan dalam format JSON (penjual, pembeli, pihak persetujuan)
- Informasi sertifikat (nomor, luas, jenis elektronik/analog, tipe HM/HGB/HP/HGU)
- Informasi PBB (validasi, akta PPJB, NOP)
- Nilai transaksi dengan format mata uang
- Upload dokumen lampiran (KTP, KK, Sertifikat, PBB, Lainnya)
- Nomor berkas auto-generate format: `TAHUN-XXX` (contoh: 2025-001)
- Penugasan otomatis ke Petugas Pengetikan saat berkas dibuat
- View detail berkas dengan section terpisah (info, pihak, sertifikat, PBB, lampiran, riwayat)
- Filter berdasarkan tahapan

---

## 2. Manajemen Berkas Perbankan

**Status:** ✅ Selesai

Modul untuk mengelola berkas covernote/SKMHT dari bank.

### Fitur:
- Input data debitur lengkap (tipe pemohon, NIK, alamat, TTL, NPWP, email, telepon)
- Data kreditur (nama, nomor PK)
- Jangka waktu covernote (1/3/6 bulan atau custom)
- Tanggal awal covernote
- Perhitungan deadline otomatis (tanggal covernote + jangka waktu)
- Kolom "Sisa Hari" dengan warna merah jika ≤ 7 hari
- Upload lampiran berkas bank
- Download lampiran
- Penugasan otomatis ke Petugas Pengetikan
- Riwayat pengerjaan

---

## 3. Manajemen Berkas Diluar Peralihan Hak (Turun Waris)

**Status:** ✅ Selesai

Modul untuk berkas waris dan kasus di luar peralihan hak standar.

### Fitur:
- Input nama kasus/klien
- Upload multi-dokumen (surat kematian, surat nikah, KTP ahli waris, KK, sertifikat, PBB, lainnya)
- Jenis dokumen custom (pilihan "Lainnya" dengan input teks)
- Penugasan otomatis ke Petugas Pengetikan
- View detail dengan lampiran dan riwayat pengerjaan

---

## 4. Tanda Terima Sertifikat

**Status:** ✅ Selesai

Modul pencatatan serah terima sertifikat antar pihak.

### Fitur:
- Input penyerah, penerima, tanggal terima, tanggal menyerahkan
- Info sertifikat (nomor/keterangan)
- Informasi tambahan detail surat
- Upload dokumen akhir (file lampiran)
- Download dokumen akhir
- Generate dokumen Word otomatis dari template (`template_serah_terima.docx`)
- Nomor berkas auto-generate

---

## 5. Kwitansi (Keuangan)

**Status:** ✅ Selesai

Modul pembuatan dan audit kwitansi pembayaran.

### Fitur:
- Nomor kwitansi auto-generate
- Nama pemohon, catatan, informasi peruntukan
- Tautkan ke berkas (opsional)
- Rincian biaya dengan repeater (deskripsi + jumlah)
- Total otomatis dihitung dari rincian
- Format mata uang Rupiah
- Status pembayaran: Lunas / Belum Lunas
- Aksi ubah status pembayaran
- Bulk action: tandai lunas / belum lunas
- Generate dokumen Word dari template (`template_kwitansi.docx`)
- Fungsi "terbilang" (angka ke huruf Indonesia) otomatis di dokumen
- Download kwitansi dalam format .docx

---

## 6. Sistem Tugas & Workflow

**Status:** ✅ Selesai

Engine alur kerja yang mengelola penugasan dan progres berkas.

### Fitur:
- Dashboard "Tugas Saya" untuk setiap petugas
- Menampilkan semua tugas pending yang ditugaskan ke user
- Informasi: nomor berkas, nama dokumen, jenis, tahap saat ini, deadline
- Aksi "Lihat Detail" — navigasi ke halaman view berkas terkait
- Aksi "Proses Tugas":
  - Input catatan pengerjaan
  - Pilih petugas berikutnya (untuk tahap yang memerlukan)
  - Otomatis buat progress record baru
  - Otomatis update `current_stage_key` di parent record
  - Kirim notifikasi ke petugas berikutnya
- Tahap penyerahan: notifikasi ke semua Petugas Entry
- Support polimorfik (Berkas, Perbankan, TurunWaris, TandaTerimaSertifikat)
- Pencarian berdasarkan nomor berkas dan nama dokumen

---

## 7. Konfigurasi Deadline

**Status:** ✅ Selesai

Konfigurasi jumlah hari default per tahapan kerja.

### Fitur:
- Tabel konfigurasi deadline per stage
- Edit jumlah hari default (hanya Superadmin)
- Digunakan otomatis saat tugas baru dibuat

---

## 8. Manajemen Pengguna

**Status:** ✅ Selesai

CRUD pengguna sistem (hanya Superadmin).

### Fitur:
- Tambah pengguna baru
- Email auto-generate dari nama (format: nama.lengkap@domain.com)
- Pilih role/peran
- Password dengan reveal toggle
- Edit dan hapus pengguna
- Bulk delete

---

## 9. Pelacakan Klien (Public)

**Status:** ✅ Selesai

Halaman publik untuk klien melacak status berkas.

### Fitur:
- Akses tanpa login di `/lacak`
- Input nomor berkas (contoh: 2025-001)
- Tampilkan status: Sedang Dalam Proses / Selesai
- Label tahapan yang ramah klien (tidak menampilkan nama internal)
- Pesan error jika nomor tidak ditemukan

---

## 10. Notifikasi

**Status:** ✅ Selesai

Sistem notifikasi internal menggunakan database notifications Filament.

### Fitur:
- Notifikasi saat menerima tugas baru
- Notifikasi saat berkas selesai (ke Petugas Entry)
- Bell icon di topbar admin panel
- Link langsung ke halaman tugas dari notifikasi

---

## 11. Dashboard Widget

**Status:** ✅ Selesai

Widget informatif di halaman dashboard admin.

### Fitur:
- Stats Overview (ringkasan angka)
- Berkas Terbaru
- Kwitansi Terbaru
- Widget Perbankan (deadline)
- Tugas Berkas Masuk
- Tugas Perbankan Masuk
- Tugas Turun Waris Masuk
- Berkas Selesai

---

## 12. Download & Generate Dokumen

**Status:** ✅ Selesai

Pembuatan dokumen Word otomatis dari template.

### Fitur:
- Template kwitansi (`storage/app/template/template_kwitansi.docx`)
- Template tanda terima (`storage/app/template/template_serah_terima.docx`)
- Mail-merge style: placeholder diganti data aktual
- Clone row untuk item rincian biaya
- Fungsi terbilang (angka → huruf Indonesia)
- File temporary dibersihkan setelah download

---

## 13. Otorisasi & Keamanan

**Status:** ✅ Selesai

Sistem hak akses berbasis role yang konsisten di seluruh resource.

### Fitur:
- Role-based access control (RBAC)
- Panel access control berbasis domain email (`.env`)
- Per-record authorization (`canView`, `canEdit`)
- Query filtering per role (`getEloquentQuery`)
- Form read-only untuk petugas non-Entry saat edit
- Menu visibility berdasarkan role
- Middleware auth di semua route download
- HTTPS enforced di production/staging
