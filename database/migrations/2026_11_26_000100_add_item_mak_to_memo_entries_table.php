<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item anggaran yang dipilih pada memo langsung. Disimpan sebagai JSON:
 * [{"kode":"004671","uraian":"...","jumlah":1620000}, ...]
 * Kolom `mak` tetap 6 bagian (level akun), jadi data dan perhitungan lama tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memo_entries', function (Blueprint $table) {
            $table->json('item_mak')->nullable()->after('mak');
        });
    }

    public function down(): void
    {
        Schema::table('memo_entries', function (Blueprint $table) {
            $table->dropColumn('item_mak');
        });
    }
};
