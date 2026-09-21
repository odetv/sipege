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
        Schema::create('setting_kop_dokumens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_sppg_id')->nullable()->constrained('unit_sppg')->onDelete('cascade');
            $table->string('nama_instansi_1')->default('BADAN GIZI NASIONAL');
            $table->string('nama_instansi_2')->default('SATUAN PELAYANAN PROGRAM GIZI (SPPG)');
            $table->string('nama_unit')->nullable();
            $table->string('kode_unit')->nullable();
            $table->string('id_sppg')->nullable();
            $table->text('alamat_lengkap')->nullable();
            $table->string('desa_kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos')->nullable();
            $table->string('telepon')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_kiri_url')->default('/images/BGN_LOGO_MAIN.png');
            $table->string('logo_kanan_url')->nullable();
            $table->string('layout_logo')->default('dual'); // dual, kiri, tengah
            $table->string('gaya_garis')->default('ganda_kedinasan'); // ganda_kedinasan, tunggal_tebal, modern_aksen, minimalis
            $table->string('template_style')->default('kedinasan_resmi'); // kedinasan_resmi, modern_sppg, klasik_formal
            $table->boolean('is_aktif')->default(true);
            $table->json('extra_attributes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setting_kop_dokumens');
    }
};
