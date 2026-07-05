# Arsitektur Teknis - NotarisKu

Dokumen ini menjelaskan struktur kode, pola desain, dan keputusan teknis yang diambil.

---

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 12, PHP 8.2 |
| Admin Panel | Filament 3.3 |
| Database | MySQL (production), SQLite (development) |
| Document Generation | PhpOffice/PhpWord 1.3 |
| Frontend (admin) | Livewire + Alpine.js (via Filament) |
| Frontend (public) | Blade + Tailwind CSS (CDN) |
| Dev Environment | Laravel Sail (Docker) |
| CI/CD | GitHub Actions |

---

## Struktur Direktori (Key Files)

```
app/
├── Enums/
│   ├── BerkasStatus.php         # baru, progres, selesai, revisi
│   ├── PembayaranStatus.php     # lunas, belum_lunas
│   └── StageKey.php             # petugas_entry → ... → selesai
├── Filament/
│   ├── Resources/               # 8 Filament resources
│   │   ├── BerkasResource.php
│   │   ├── PerbankanResource.php
│   │   ├── TurunWarisResource.php
│   │   ├── TandaTerimaSertifikatResource.php
│   │   ├── KwitansiResource.php
│   │   ├── TugasResource.php
│   │   ├── UserResource.php
│   │   └── DeadlineConfigResource.php
│   └── Widgets/                 # 9 dashboard widgets
├── Http/Controllers/
│   ├── KwitansiController.php        # Generate Word kwitansi
│   ├── TandaTerimaController.php     # Generate Word tanda terima
│   ├── ClientTrackingController.php  # Halaman publik /lacak
│   ├── BerkasFileController.php      # Download file berkas
│   ├── PerbankanFileController.php   # Download file perbankan
│   └── TurunWarisFileController.php  # Download file turun waris
├── Models/
│   ├── Berkas.php
│   ├── Perbankan.php
│   ├── TurunWaris.php
│   ├── TandaTerimaSertifikat.php
│   ├── Receipt.php
│   ├── Progress.php              # Polymorphic task tracking
│   ├── DeadlineConfig.php
│   ├── User.php
│   ├── Role.php
│   ├── BerkasFile.php
│   ├── PerbankanFile.php
│   └── TurunWarisFile.php
│   └── Concerns/
│       └── HasProgress.php       # Trait untuk relasi polymorphic progress
├── Providers/
│   ├── Filament/
│   │   └── AdminPanelProvider.php
│   └── FilamentServiceProvider.php
database/
├── migrations/                   # 22 migration files
├── seeders/
│   └── RoleSeeder.php           # Seed role awal
resources/views/
├── client-tracking.blade.php     # Halaman publik pelacakan
├── filament/
│   ├── infolists/               # Blade views untuk ViewEntry
│   └── partials/                # Navbar custom
routes/
└── web.php                      # Route download + pelacakan
storage/app/template/
├── template_kwitansi.docx       # Template Word kwitansi
└── template_serah_terima.docx   # Template Word tanda terima
```

---

## Pola Desain

### 1. Polymorphic Progress Tracking

Semua jenis berkas (Berkas, Perbankan, TurunWaris, TandaTerimaSertifikat) menggunakan **satu tabel `progress`** dengan relasi polymorphic.

```php
// Trait HasProgress (di setiap model berkas)
public function progress(): MorphMany
{
    return $this->morphMany(Progress::class, 'progressable');
}
```

**Keuntungan:** Satu engine workflow untuk semua jenis dokumen, tanpa duplikasi tabel.

### 2. Role-Based Query Filtering

Setiap resource menerapkan `getEloquentQuery()` yang memfilter data berdasarkan role user yang sedang login. Ini memastikan petugas hanya melihat data yang relevan.

### 3. Staged Workflow via TugasResource

`TugasResource` bertindak sebagai "inbox" yang menampilkan semua progress record dengan status `pending` untuk user yang login. Aksi "Proses Tugas" menggerakkan berkas ke tahap berikutnya.

### 4. Document Generation (Mail Merge)

Menggunakan PhpWord `TemplateProcessor` untuk mengganti placeholder di template .docx dengan data aktual. Mendukung:
- Placeholder sederhana: `${nama_pemohon}`
- Clone row untuk repeater: `${deskripsi_item#1}`, `${deskripsi_item#2}`

### 5. File Management per Resource

Setiap jenis berkas memiliki model file tersendiri:
- `BerkasFile` → tabel `berkas_files`
- `PerbankanFile` → tabel `perbankan_files`
- `TurunWarisFile` → tabel `turun_waris_files`

Upload menggunakan Filament Repeater + FileUpload ke disk `public`.

---

## Database Schema (Simplified)

```
berkas
├── id, nomor_berkas, nama_berkas, nama_pemohon
├── penjual_data (JSON), pembeli_data (JSON), pihak_persetujuan_data (JSON)
├── sertifikat_*, pbb_*, nilai_transaksi
├── status_overall, current_stage_key, created_by

perbankans
├── id, tipe_pemohon, nik, nama_debitur, alamat_debitur
├── ttl_*, npwp, email, telepon, nomor_pk, nama_kreditur
├── jangka_waktu, tanggal_covernote
├── status_overall, current_stage_key, created_by

turun_waris
├── id, nama_kasus, status_overall, current_stage_key, created_by

tanda_terima_sertifikats
├── id, penyerah, penerima, nomor_berkas
├── tanggal_terima, tanggal_menyerahkan, sertifikat_info
├── informasi_tambahan, dokumen_akhir_path, created_by

progress (POLYMORPHIC)
├── id, progressable_id, progressable_type
├── stage_key, status (pending/done), notes
├── deadline, started_at, completed_at, assignee_id

receipts
├── id, berkas_id (nullable), receipt_number
├── nama_pemohon_kwitansi, amount, status_pembayaran
├── detail_biaya (JSON), notes_kwitansi, informasi_kwitansi

berkas_files / perbankan_files / turun_waris_files
├── id, [parent]_id, type, path

deadline_configs
├── id, stage_key, default_days

users
├── id, name, email, password, role_id

roles
├── id, name
```

---

## Environment Variables Penting

| Key | Fungsi |
|-----|--------|
| `USER_EMAIL_DOMAIN` | Domain email yang diizinkan login ke panel (kosong = semua boleh) |
| `APP_ENV` | Menentukan apakah HTTPS di-force (production/staging = ya) |

---

## Deployment

- **Docker:** `docker-compose.yml` dengan Laravel Sail
- **CI/CD:** GitHub Actions (`build.yml` untuk build, `deploy.yml` untuk deploy)
- **Storage:** File upload di `storage/app/public/`, symlink ke `public/storage`
