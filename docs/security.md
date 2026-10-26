# SIPEGE Security & Data Integrity Blueprint

## 1. Isolasi Multi-Tenant Unit SPPG
Setiap tabel operasional (termasuk `work_orders`, `purchase_orders`, `kelompok_penerima_manfaat`, `survey_harga_pangan`, dll.) memiliki foreign key `unit_sppg_id`.
- Setiap query controller atau model wajib menyertakan filter `->where('unit_sppg_id', $unitSppg->id)`.
- Pengguna tidak diperbolehkan mengakses atau memodifikasi data SPPG lain sekalipun memodifikasi URL parameter atau payload ID.

## 2. PostgreSQL Strict Type Safety (Pencegahan SQLSTATE[22P02])
PostgreSQL memberlakukan validasi tipe data ketat pada kolom berjenis `uuid`.
- Jangan pernah menjalankan query `OR` yang mencampuradukkan string integer ke dalam kolom bertipe UUID, misalnya:
  `WHERE ("id" = 52 OR "uuid" = '52')` -> AKAN MENYEBABKAN FATAL ERROR 22P02.
- Standar penanganan identifier:
  ```php
  $target = $request->input('id');
  $query = WorkOrder::where('unit_sppg_id', $unitSppg->id);

  if (is_numeric($target)) {
      $query->where('id', (int) $target);
  } elseif (\Illuminate\Support\Str::isUuid((string) $target)) {
      $query->where('uuid', (string) $target);
  } else {
      $query->where('nomor_wo', (string) $target);
  }
  ```

## 3. Atomic Transaction pada Work Order Mutation
- Setiap pembuatan atau pengubahan Work Order beserta item resep/bahan baku harus dibungkus dalam `DB::transaction(function () { ... })` untuk menjamin konsistensi data.
- Mutasi tanggal WO tidak boleh menduplikasi baris database, melainkan memperbarui entitas yang ada secara in-place setelah validasi keunikan tanggal distribusi dalam lingkup unit SPPG.
