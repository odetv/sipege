<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Work Orders (Rancangan Menu & Perencanaan Produksi MBG)
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_wo')->unique(); // e.g. WO-MBG-20260829
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->date('tanggal_distribusi');
            $table->string('nama_menu');
            $table->unsignedTinyInteger('siklus_ke')->default(1);
            $table->string('status')->default('Draft'); // Draft, Diajukan ke Keuangan, Siap Produksi, Selesai, Dibatalkan
            
            // Rincian Sub Menu Komponen Gizi
            $table->string('komponen_energi')->nullable();
            $table->string('komponen_protein')->nullable();
            $table->string('komponen_lemak')->nullable();
            $table->string('komponen_karbohidrat')->nullable();
            $table->string('komponen_serat')->nullable();
            
            // Rekapitulasi Sasaran PM
            $table->unsignedInteger('total_pm')->default(0);
            $table->unsignedInteger('total_pk')->default(0);
            $table->unsignedInteger('total_pb')->default(0);
            $table->unsignedInteger('total_alergi')->default(0);
            $table->unsignedInteger('total_kelompok')->default(0);

            // Hasil Evaluasi AKG (JSON)
            $table->json('akg_pk')->nullable(); // { energi, protein, lemak, karbohidrat, serat }
            $table->json('akg_pb')->nullable();

            // Biaya & Food Cost
            $table->decimal('food_cost_pk', 12, 2)->default(0);
            $table->decimal('food_cost_pb', 12, 2)->default(0);
            $table->decimal('total_anggaran_master', 15, 2)->default(0);
            $table->decimal('total_anggaran_aktual', 15, 2)->default(0);

            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // 2. Tabel Work Order Items (Bahan Baku Komposisi dari TKPI 2020)
        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->string('tkpi_id')->nullable();
            $table->string('nama');
            $table->string('nama_po')->nullable();
            $table->string('kategori')->default('Lainnya');
            $table->string('tipe_porsi')->default('normal'); // normal, alergi
            $table->string('jenis_alergi')->nullable();
            $table->string('alergen')->nullable();

            // Gramasi dan Gross Calculation
            $table->decimal('gram_pk', 8, 2)->default(0);
            $table->decimal('gram_pb', 8, 2)->default(0);
            $table->decimal('bdd', 5, 2)->default(100);
            $table->decimal('buffer', 5, 2)->default(0);
            $table->decimal('gross_kg_pk', 10, 2)->default(0);
            $table->decimal('gross_kg_pb', 10, 2)->default(0);
            $table->decimal('total_gross_kg', 10, 2)->default(0);

            // Harga dan Subtotal
            $table->decimal('harga_master', 12, 2)->default(0);
            $table->decimal('subtotal_master', 15, 2)->default(0);

            // Rincian Nutrisi per Porsi (JSON)
            $table->json('nutrisi_pk')->nullable();
            $table->json('nutrisi_pb')->nullable();

            $table->timestamps();
        });

        // 3. Tabel Work Order Kelompok (Snapshot Partisipasi Kelompok Sasaran pada WO)
        Schema::create('work_order_kelompoks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('kelompok_id')->nullable()->constrained('kelompok_penerima_manfaat')->nullOnDelete();
            $table->string('nama_kelompok');
            $table->string('kategori')->default('Lainnya');
            $table->boolean('is_menerima')->default(true);
            $table->unsignedInteger('porsi_kecil')->default(0);
            $table->unsignedInteger('porsi_besar')->default(0);
            $table->unsignedInteger('total_penerima')->default(0);
            $table->string('status_alergi')->nullable();
            $table->timestamps();
        });

        // 4. Tabel Purchase Orders (Keuangan - Pengadaan Bahan Baku)
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_po')->unique(); // e.g. PO-20260829-001
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('vendor')->default('Rekanan Pangan SPPG');
            $table->unsignedInteger('items_count')->default(0);
            $table->decimal('total_nominal_master', 15, 2)->default(0);
            $table->decimal('total_nominal_aktual', 15, 2)->default(0);
            $table->string('status_po')->default('Draft PO'); // Draft PO, Menunggu Verifikasi, Terverifikasi, Siap Produksi, Ditolak
            $table->string('status_bayar')->default('Belum Bayar'); // Belum Bayar, Lunas
            $table->text('catatan')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->timestamps();
        });

        // 5. Tabel Purchase Order Items (Item Belanja Realisasi Akuntan)
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('work_order_item_id')->nullable()->constrained('work_order_items')->nullOnDelete();
            $table->string('nama');
            $table->string('kategori')->default('Lainnya');
            $table->string('tipe')->default('Normal'); // Normal, Alergi
            $table->decimal('gross_kg', 10, 2)->default(0);
            $table->decimal('harga_master', 12, 2)->default(0);
            $table->decimal('harga_aktual', 12, 2)->default(0);
            $table->decimal('subtotal_aktual', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('work_order_kelompoks');
        Schema::dropIfExists('work_order_items');
        Schema::dropIfExists('work_orders');
    }
};
