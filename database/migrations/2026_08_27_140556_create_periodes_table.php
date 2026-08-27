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
        Schema::create('periodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('nomor_periode')->unique()->comment('Nomor urut periode, misal: 1, 2, 3, ...');
            $table->date('tanggal_mulai')->comment('Tanggal awal periode operasional');
            $table->date('tanggal_selesai')->comment('Tanggal akhir periode operasional');
            $table->string('keterangan', 255)->nullable()->comment('Keterangan tambahan periode');
            $table->boolean('is_aktif')->default(false)->comment('Menandai periode yang sedang aktif digunakan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodes');
    }
};
