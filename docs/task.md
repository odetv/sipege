# SIPEGE Task & Implementation Roadmap

## Status Fitur & Bug Tracker

### 1. Modul Work Order Tracking (Selesai ✅)
- [x] Perbaikan penanganan PostgreSQL UUID type-safety pada pencarian WO (`is_numeric()` vs `Str::isUuid()`).
- [x] Mekanisme edit tanggal in-place tanpa pembuatan entitas duplikat.
- [x] Diferensiasi pipeline tahapan progres WO Otomatis vs WO Manual pada dashboard (`DashboardWoTrackingBar.vue`):
  - **WO Otomatis**: Perencanaan Sasaran -> Formulasi Gizi TKPI -> Pengajuan Purchase Order -> Verifikasi Keuangan -> Siap Produksi.
  - **WO Manual**: Perencanaan Sasaran & Menu -> Formulasi Gizi Manual (AKG & Alergi) -> Pengajuan Work Order -> Verifikasi Keuangan -> Siap Produksi & Cetak Label.
- [x] Visual badge pembeda `✍️ WO MANUAL` vs `⚡ WO OTOMATIS` pada banner dashboard dan dropdown selector.
- [x] Tautan cepat kontekstual (Tombol "Daftar WO" untuk manual, "Daftar Menu" untuk otomatis).
- [x] Tombol CTA footer dan teks panduan dinamis sesuai metode WO yang sedang aktif.

### 2. Modul Gizi & Resep TKPI (Berjalan 🚀)
- [x] Integrasi database pangan TKPI 2020.
- [x] Perhitungan AKG PK (PAUD-SD) dan PB (SMP-SMA).
- [ ] Export menu bulanan ke PDF / Excel dengan kop dokumen resmi SPPG.

### 3. Modul Verifikasi Keuangan & PO (Berjalan 🚀)
- [x] Survey harga pasar lokal.
- [x] Verifikasi dan validasi PO bahan baku.
- [ ] Pelacakan status pembayaran faktur supplier.
