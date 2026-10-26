# Blueprint Siklus Hidup Work Order (WO) SIPEGE

Dokumen ini mendefinisikan aturan bisnis, status mutasi, dan visual pipeline untuk Work Order di SIPEGE.

---

## 1. Perbedaan Mendasar WO Otomatis vs WO Manual

| Aspek | WO Otomatis (`sistem`) | WO Manual (`manual`) |
|---|---|---|
| **Basis Perencanaan** | Gramatur bahan baku pangan dari database TKPI 2020 | Input langsung komponen menu & nilai gizi AKG |
| **Pengadaan Bahan** | Menghasilkan Purchase Order (PO) bahan mentah | Tidak membuat PO bahan mentah (siap santap / katering / mandiri) |
| **Keterlibatan Keuangan** | Melalui telaah & verifikasi anggaran oleh Bagian Keuangan | **Tidak ada kaitan dengan Keuangan** (alur mandiri) |
| **Tahap 1** | **01. Perencanaan Sasaran** (Kuantitas PK & PB) | **01. Perencanaan Sasaran** (Kuantitas Sasaran PK & PB) |
| **Tahap 2** | **02. Formulasi Gizi TKPI** (Standar AKG & Bahan Baku) | **02. Komponen & Standar Gizi** (Komponen Menu & Standar AKG) |
| **Tahap 3** | **03. Pengajuan Purchase Order** (Estimasi Belanja Pangan) | **03. Siap Produksi & Penyaluran** (Jadwal Masak & Cetak Label) |
| **Tahap 4** | **04. Verifikasi Keuangan** (Telaah Anggaran & Rekanan) | *(Tidak Ada Tahap Keuangan)* |
| **Tahap 5** | **05. Siap Produksi** (PO Resmi & Jadwal Masak) | *(Langsung Selesai di Tahap 3)* |
| **Status Akhir** | Siap Produksi (setelah diverifikasi Keuangan) | **Siap Produksi & Penyaluran** (100% Selesai saat WO terbentuk) |
| **Navigasi CTA Utama** | Menu Gizi (`gizi.daftar-menu` / `gizi.rancang-menu`) | Cetak Label (`label.index`) & Edit WO (`work-order.edit-manual`) |

---

## 2. Status & Persentase Progres

### WO Otomatis (5 Tahapan)
- **Draft (Step 1)**: 20% - Perencanaan Sasaran baru dibuat.
- **Draft (Step 2)**: 40% - Formulasi gizi bahan baku TKPI mulai diinput (`items_count > 0`).
- **Draft (Step 3)**: 60% - Rincian belanja selesai, siap diajukan ke Keuangan.
- **Dalam Verifikasi Keuangan**: 75% - Menunggu persetujuan verifikator keuangan.
- **Perlu Perbaikan (Ditolak)**: 50% - Ditolak oleh Keuangan, perlu revisi resep/pagu.
- **Siap Produksi**: 100% - PO resmi terbit, jadwal masak dapur aktif.

### WO Manual (3 Tahapan Mandiri)
- Begitu Work Order Manual sudah terbentuk di sistem (disimpan dengan sasaran, nama menu, dan komponen/gizi), seluruh tahapannya **100% Selesai**.
- Status: **Siap Produksi & Penyaluran** (100% Hijau / Emerald).
- Tidak memerlukan pengajuan ke Bagian Keuangan maupun verifikasi anggaran PO.
- Siap langsung digunakan untuk jadwal pengolahan dan pencetakan label distribusi makanan bergizi.

---

## 3. Aturan In-Place Edit Tanggal Distribusi
1. Satu unit SPPG hanya boleh memiliki 1 Work Order per tanggal distribusi.
2. Ketika user mengubah tanggal distribusi pada formulir edit WO, sistem memverifikasi apakah tanggal tersebut digunakan oleh WO lain.
3. Jika tanggal belum digunakan, sistem melakukan pembaruan di tempat (*update record*) terhadap baris WO yang sedang diedit tanpa menghapus atau membuat baris baru.
