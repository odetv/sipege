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
        Schema::create('saved_labels', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_label')->unique(); // e.g. LBL-20260909-001
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->string('nama_menu');
            $table->date('tanggal_produksi');
            $table->string('jam_produksi')->default('07:00');
            $table->string('batas_konsumsi')->default('09:00');
            $table->text('petunjuk_menu')->nullable();
            $table->string('template_id')->default('bgn_standard_4_3');
            $table->string('template_name')->nullable();
            $table->string('aspect_ratio')->default('4:3');
            
            // Evaluasi Nilai Gizi
            $table->json('gizi_data')->nullable(); // { energi_pk, energi_pb, karbo_pk, karbo_pb, protein_pk, protein_pb, lemak_pk, lemak_pb, serat_pk, serat_pb }
            
            // Sasaran Kelompok Terpilih & Snapshot
            $table->json('selected_kelompok_ids')->nullable(); // [1, 2, 3]
            $table->json('kelompoks_snapshot')->nullable(); // Snapshot data kelompok sasaran saat disimpan
            
            // Ringkasan Numerik
            $table->unsignedInteger('total_sasaran')->default(0);
            $table->unsignedInteger('total_porsi')->default(0);
            $table->unsignedInteger('total_pk')->default(0);
            $table->unsignedInteger('total_pb')->default(0);
            
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_labels');
    }
};
