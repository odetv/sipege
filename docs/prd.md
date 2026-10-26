# Product Requirements Document (PRD) - SIPEGE (Sistem Informasi Pelayanan Gizi & Distribusi MBG)

## 1. Ikhtisar Sistem
SIPEGE adalah platform ERP & Manajemen Operasional Unit SPPG (Satuan Pelayanan Program Bergizi) untuk program Makanan Bergizi Gratis (MBG). Platform ini mengintegrasikan seluruh rantai operasional mulai dari perencanaan penerima manfaat, formulasi standar gizi AKG & TKPI, perencanaan dan eksekusi Work Order (WO), pengadaan bahan baku (Purchase Order), verifikasi keuangan, hingga distribusi dan pencetakan label berstandar nasional.

---

## 2. Struktur Modul Utama

### A. Modul Work Order (WO)
Modul untuk merencanakan dan mengeksekusi siklus produksi MBG harian/siklus:
1. **WO Otomatis (Sistem)**:
   - Terintegrasi dengan database pangan (TKPI 2020) & resep gramatur bahan baku.
   - Menghasilkan Purchase Order (PO) bahan baku secara otomatis untuk diajukan dan diverifikasi oleh Bagian Keuangan.
   - **Tahapan Progres**:
     1. Perencanaan Sasaran (Kuantitas PM, PK, PB)
     2. Formulasi Gizi TKPI (Penyusunan Resep & Bahan Baku)
     3. Pengajuan Purchase Order (Kalkulasi Estimasi Anggaran Belanja)
     4. Verifikasi Keuangan (Telaah Harga Pasar & Rekanan Supplier)
     5. Siap Produksi (PO Resmi Terbit & Jadwal Masak Dapur)

2. **WO Manual**:
   - Disediakan untuk operasional cepat atau penyediaan makanan siap santap/katering mitra tanpa kalkulasi gramatur bahan baku mentah.
   - Komponen menu (lauk utama, nabati, sayur, buah, snack) dan estimasi nilai gizi AKG (Energi, Protein, Lemak, Karbohidrat, Serat) diinput secara langsung oleh Petugas Gizi.
   - **Tahapan Progres**:
     1. Perencanaan Sasaran & Menu (Kuantitas PM, PK, PB, & Komponen)
     2. Formulasi Gizi Manual (Standar Nilai AKG & Alergen)
     3. Pengajuan Work Order (Pengajuan Berkas WO ke Keuangan/Kepala SPPG)
     4. Verifikasi Keuangan (Telaah Status & Validasi Pagu Anggaran)
     5. Siap Produksi (WO Terverifikasi, Jadwal Masak, & Cetak Label)

### B. Modul Gizi & Menu
- **Database Pangan**: Berisi data komposisi pangan Indonesia (TKPI 2020) dengan informasi makronutrien dan mikronutrien lengkap per 100g BDD.
- **Rancang Menu**: Formula interaktif perhitungan AKG per kelompok usia (PK & PB) dan simulasi food cost bahan baku.
- **Daftar Menu & Kalender Menu**: Tampilan kalender siklus menu dan status kesiapan produksi per tanggal.

### C. Modul Keuangan & Pengadaan
- **Survey Harga Pasar**: Input berkala harga bahan baku pangan lokal untuk benchmark pagu belanja.
- **Verifikasi PO**: Verifikasi kuantitas, harga satuan aktual vs master, margin toleransi, dan rekanan supplier terpilih.
- **Daftar PO Resmi & Stok Bahan**: Penerbitan nomor PO resmi, approval digital, dan monitoring mutasi stok gudang SPPG.

### D. Modul Labeling & Distribusi
- **Cetak Label MBG**: Cetak label kemasan porsi makanan bergizi per sekolah/kelompok sasaran dengan barcode/QR code, informasi alergen, dan profil gizi.
- **Rekap Penyaluran**: Pelacakan armada distribusi dan tanda terima porsi di titik bagi penerima manfaat.

### E. Modul Penerima Manfaat (KPM)
- Pendataan sekolah, kelompok belajar, posyandu, jumlah peserta didik (PK: PAUD-Kelas 3, PB: Kelas 4-SMP/SMA), serta catatan riwayat alergi khusus.

---

## 3. Aturan Integritas Data Multi-Tenant SPPG
- Seluruh query wajib memfilter `unit_sppg_id` sesuai tenant yang sedang aktif.
- Satu SPPG hanya boleh memiliki 1 Work Order aktif per tanggal distribusi. Operasi edit tanggal harus melakukan pembaruan di tempat (*in-place update*) tanpa membuat rekaman ganda.
- Identifikasi entitas pada rute dan query harus mendukung UUID (`uuid`) dan numeric primary key (`id`) dengan type casting PostgreSQL yang aman (`is_numeric()` vs `Str::isUuid()`).
