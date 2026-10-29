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
        Schema::create('pengaturan_apis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_sppg_id')->constrained('unit_sppg')->cascadeOnDelete();
            $table->string('provider', 50)->default('gemini'); // gemini, openai, etc
            $table->text('api_key')->nullable();
            $table->string('model', 100)->default('auto'); // auto, gemini-2.0-flash, etc
            $table->boolean('is_active')->default(true);
            $table->string('status_koneksi', 30)->default('belum_dites'); // belum_dites, terhubung, gagal
            $table->timestamp('last_tested_at')->nullable();
            $table->text('pesan_koneksi')->nullable();
            $table->json('extra_config')->nullable();
            $table->timestamps();

            $table->unique(['unit_sppg_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_apis');
    }
};
