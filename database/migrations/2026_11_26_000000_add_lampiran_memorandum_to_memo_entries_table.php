<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data untuk halaman 3 (Lampiran Memorandum) pada memo yang dibuat langsung
 * (memo_entries). Semua kolom nullable supaya memo lama tidak terpengaruh;
 * nilai kosong diberi nilai bawaan saat PDF dibuat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memo_entries', function (Blueprint $table) {
            $table->string('penerima_nama', 150)->nullable()->after('keterangan');
            $table->string('penerima_bank', 50)->nullable()->after('penerima_nama');
            $table->string('penerima_rekening', 50)->nullable()->after('penerima_bank');
            $table->unsignedBigInteger('total_bersih')->nullable()->after('penerima_rekening');
            $table->string('klasifikasi', 10)->nullable()->after('total_bersih');
            $table->unsignedTinyInteger('nilai_persen')->nullable()->after('klasifikasi');
            $table->string('st_info', 150)->nullable()->after('nilai_persen');
        });
    }

    public function down(): void
    {
        Schema::table('memo_entries', function (Blueprint $table) {
            $table->dropColumn([
                'penerima_nama', 'penerima_bank', 'penerima_rekening',
                'total_bersih', 'klasifikasi', 'nilai_persen', 'st_info',
            ]);
        });
    }
};
