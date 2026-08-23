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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16)->unique();
            $table->string('nip')->nullable();
            $table->string('nama');
            $table->string('gelar_depan')->nullable();
            $table->string('gelar_belakang')->nullable();
            $table->string('agama');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->string('jenjang_pendidikan')->nullable();
            $table->string('bidang_pendidikan')->nullable();
            $table->string('status_kawin');
            $table->string('provinsi_ktp');
            $table->string('kabupaten_ktp');
            $table->string('kecamatan_ktp');
            $table->string('desa_kelurahan_ktp');
            $table->string('kode_pos_ktp', 10)->nullable();
            $table->text('alamat_lengkap_ktp')->nullable();
            $table->string('provinsi_domisili');
            $table->string('kabupaten_domisili');
            $table->string('kecamatan_domisili');
            $table->string('desa_kelurahan_domisili');
            $table->string('kode_pos_domisili', 10)->nullable();
            $table->text('alamat_lengkap_domisili')->nullable();
            $table->decimal('latitude_domisili', 10, 7);
            $table->decimal('longitude_domisili', 10, 7);
            $table->string('telepon');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('photo')->nullable();
            $table->string('role')->default('guest');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
