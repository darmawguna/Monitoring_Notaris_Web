# Alur Kerja (Workflow) - NotarisKu

Dokumen ini menjelaskan bagaimana berkas mengalir dari awal sampai selesai.

---

## Diagram Alur

```
┌──────────────┐     ┌────────────────────┐     ┌──────────────┐     ┌───────────────────┐     ┌─────────────┐     ┌────────────┐
│ Petugas Entry│ ──► │ Petugas Pengetikan │ ──► │ Petugas Pajak│ ──► │ Petugas Penyiapan │ ──► │ Petugas BPN │ ──► │ Penyerahan │
│   (Input)    │     │   (Verifikasi)     │     │  (Pajak)     │     │   (Finalisasi)    │     │   (BPN)     │     │  (Selesai) │
└──────────────┘     └────────────────────┘     └──────────────┘     └───────────────────┘     └─────────────┘     └────────────┘
```

---

## Tahapan Detail

### 1. Petugas Entry (Input Berkas)

**Aktor:** Petugas Entry / Front Office

**Aktivitas:**
- Menerima berkas fisik dari klien
- Input data ke sistem (informasi berkas, pihak, sertifikat, PBB)
- Upload dokumen lampiran
- Pilih Petugas Pengetikan untuk ditugaskan
- Klik simpan

**Otomatis oleh sistem:**
- Generate nomor berkas (format: TAHUN-XXX)
- Set status = `progres`
- Set stage = `petugas pengetikan`
- Buat progress record "done" untuk Petugas Entry
- Buat progress record "pending" untuk Petugas Pengetikan + deadline
- Kirim notifikasi ke Petugas Pengetikan

---

### 2. Petugas Pengetikan

**Aktor:** Petugas Pengetikan

**Aktivitas:**
- Buka "Tugas Saya" di dashboard
- Klik "Lihat Detail" untuk melihat data berkas
- Kerjakan tugas pengetikan (edit berkas jika perlu)
- Klik "Proses Tugas"
- Isi catatan pengerjaan
- Pilih Petugas Pajak berikutnya

**Otomatis oleh sistem:**
- Update progress sebelumnya jadi "done"
- Set stage = `petugas pajak`
- Buat progress record "pending" untuk Petugas Pajak + deadline
- Kirim notifikasi ke Petugas Pajak

---

### 3. Petugas Pajak

**Aktor:** Petugas Pajak

**Aktivitas:**
- Buka "Tugas Saya"
- Proses urusan pajak
- Klik "Proses Tugas"
- Isi catatan dan pilih Petugas Penyiapan

**Otomatis oleh sistem:**
- Update progress → "done"
- Set stage = `petugas penyiapan`
- Buat progress "pending" untuk Petugas Penyiapan + deadline
- Kirim notifikasi

---

### 4. Petugas Penyiapan

**Aktor:** Petugas Penyiapan

**Aktivitas:**
- Buka "Tugas Saya"
- Siapkan berkas final
- Klik "Proses Tugas"
- Isi catatan pengerjaan final

**Otomatis oleh sistem:**
- Update progress → "done"
- Set status = `selesai`
- Set stage = `penyerahan`
- Buat progress "pending" untuk SEMUA Petugas Entry
- Kirim notifikasi ke semua Petugas Entry: "Berkas siap diserahkan"

---

### 5. Penyerahan

**Aktor:** Petugas Entry

**Aktivitas:**
- Terima notifikasi bahwa berkas siap diserahkan
- Hubungi klien
- Serahkan berkas ke klien
- Klik "Proses Tugas"
- Isi catatan (contoh: "Diserahkan ke klien pada tanggal...")

**Otomatis oleh sistem:**
- Update progress → "done"
- Set status_overall = `selesai`
- Berkas bisa dilihat oleh semua Petugas Entry sebagai arsip

---

## Deadline

- Setiap tahap memiliki deadline yang dikonfigurasi di menu **Pengaturan > Deadline**
- Default: 3 hari per tahap (bisa diubah Superadmin)
- Deadline dihitung otomatis saat tugas dibuat: `now() + default_days`
- Deadline ditampilkan di tabel "Tugas Saya"

---

## Catatan Khusus

- **Berkas Perbankan** mengikuti alur yang sama
- **Berkas Turun Waris** mengikuti alur yang sama
- **Tanda Terima Sertifikat** mengikuti alur yang sama
- Semua menggunakan tabel `progress` yang polimorfik (satu engine untuk semua jenis berkas)
- Petugas hanya bisa melihat/edit berkas yang ditugaskan ke mereka (akses berbasis tugas)
