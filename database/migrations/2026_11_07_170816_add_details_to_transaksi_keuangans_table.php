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
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi_keuangans', 'tanggal_pesanan')) {
                $table->date('tanggal_pesanan')->nullable()->after('tanggal');
            }
            if (!Schema::hasColumn('transaksi_keuangans', 'volume')) {
                $table->decimal('volume', 12, 3)->nullable()->after('nama_transaksi');
            }
            if (!Schema::hasColumn('transaksi_keuangans', 'satuan')) {
                $table->string('satuan')->nullable()->default('Kg')->after('volume');
            }
            if (!Schema::hasColumn('transaksi_keuangans', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('perlu_npo')->constrained('suppliers')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_keuangans', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn(['supplier_id']);
            }
            if (Schema::hasColumn('transaksi_keuangans', 'satuan')) {
                $table->dropColumn('satuan');
            }
            if (Schema::hasColumn('transaksi_keuangans', 'volume')) {
                $table->dropColumn('volume');
            }
            if (Schema::hasColumn('transaksi_keuangans', 'tanggal_pesanan')) {
                $table->dropColumn('tanggal_pesanan');
            }
        });
    }
};
