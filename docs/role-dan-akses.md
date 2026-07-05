# Role & Hak Akses - NotarisKu

Dokumen ini menjelaskan setiap role pengguna dan hak aksesnya di sistem.

---

## Daftar Role

| Role | Deskripsi |
|------|-----------|
| Superadmin | Administrator penuh, akses ke seluruh fitur dan data |
| Petugas Entry | Front Office, input berkas baru, kelola kwitansi, serah terima |
| Petugas Pengetikan | Proses pengetikan/verifikasi internal |
| Petugas Pajak | Proses urusan pajak |
| Petugas Penyiapan | Penyiapan dan finalisasi berkas |
| Petugas BPN | Proses di BPN |

---

## Matriks Hak Akses

### Menu Navigasi

| Menu | Superadmin | Petugas Entry | Petugas Lain |
|------|:----------:|:-------------:|:------------:|
| Dashboard | ✅ | ✅ | ✅ |
| Tugas Saya | ❌ | ✅ | ✅ |
| Berkas Peralihan Hak | ✅ | ✅ | ✅ |
| Berkas Perbankan | ✅ | ✅ | ✅ |
| Berkas Diluar Peralihan Hak | ✅ | ✅ | ✅ |
| Tanda Terima Sertifikat | ✅ | ✅ | ❌ |
| Kwitansi | ✅ | ✅ | ❌ |
| Pengguna (Pengaturan) | ✅ | ❌ | ❌ |
| Deadline (Pengaturan) | ✅ | ❌ | ❌ |

### Aksi per Resource

| Aksi | Superadmin | Petugas Entry | Petugas Lain |
|------|:----------:|:-------------:|:------------:|
| Lihat semua berkas | ✅ | Milik sendiri + selesai | Hanya yang ditugaskan |
| Buat berkas baru | ✅ | ✅ | ❌ |
| Edit berkas | ✅ | ✅ | Hanya yang ditugaskan |
| Proses tugas | ❌ | ✅ | ✅ |
| Download dokumen | ✅ | ✅ | ✅ (jika punya akses) |
| Kelola pengguna | ✅ | ❌ | ❌ |
| Konfigurasi deadline | ✅ | ❌ | ❌ |
| Bulk delete kwitansi | ✅ | ❌ | ❌ |

---

## Logika Otorisasi

### Query Filtering (Data yang Tampil)

```
Superadmin       → Semua data
Petugas Entry    → Data yang dibuat sendiri + berkas dengan status 'selesai'
Petugas Lain     → Hanya berkas yang memiliki progress 'pending' untuk user tersebut
```

### Per-Record Authorization

Setiap resource menerapkan:
- `canView(Model $record)` — boleh lihat detail?
- `canEdit(Model $record)` — boleh edit?

Logika konsisten di BerkasResource, PerbankanResource, dan TurunWarisResource.

### Form Read-Only

Saat petugas non-Entry mengedit berkas:
- Section informasi utama (data berkas, pihak, sertifikat, PBB) → **read-only**
- Hanya bisa melihat data, tidak bisa mengubah

---

## Akses Panel

- Panel admin di `/admin`
- Login menggunakan email + password
- Akses panel dibatasi berdasarkan domain email (konfigurasi via `.env` key `USER_EMAIL_DOMAIN`)
- Jika tidak diatur, semua user bisa login (mode development)

---

## Halaman Publik

| URL | Akses | Deskripsi |
|-----|-------|-----------|
| `/lacak` | Tanpa login | Pelacakan status berkas untuk klien |
| `/admin` | Login required | Panel administrasi |
