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
        Schema::create('kelompok_penerima_manfaat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->onDelete('cascade');
            $table->string('nama_kelompok');
            $table->string('kategori'); // TK, RA, PAUD, SD, MI, SMP, MTs, SMA, SMK, MA, MAK, Posyandu
            $table->string('jenis_kepemilikan'); // Negeri, Swasta
            $table->string('tipe_identitas'); // NPSN, NSPP, NSM, NSNP, TPK, Lainnya
            $table->string('kode_identitas');
            $table->string('nama_kepala');
            $table->string('email_kepala');
            $table->string('telepon_kepala');
            $table->string('nama_pic');
            $table->string('email_pic');
            $table->string('telepon_pic');
            $table->string('provinsi');
            $table->string('kabupaten');
            $table->string('kecamatan');
            $table->string('desa_kelurahan');
            $table->string('kode_pos');
            $table->text('alamat_lengkap');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('total_laki_laki')->default(0);
            $table->unsignedInteger('total_perempuan')->default(0);
            $table->unsignedInteger('total_penerima')->default(0);
            $table->timestamps();
        });

        Schema::create('rincian_penerima_manfaat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kelompok_penerima_manfaat_id')->constrained('kelompok_penerima_manfaat')->onDelete('cascade');
            $table->string('sub_kategori'); // e.g. Kelas 1, Pelajar, Ibu Hamil, Pendukung (Guru)
            $table->unsignedInteger('jumlah_laki_laki')->default(0);
            $table->unsignedInteger('jumlah_perempuan')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rincian_penerima_manfaat');
        Schema::dropIfExists('kelompok_penerima_manfaat');
    }
};
