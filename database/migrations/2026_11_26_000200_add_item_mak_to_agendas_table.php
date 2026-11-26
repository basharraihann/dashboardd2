<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item anggaran per memo agenda. Satu agenda punya dua memo (PNS dan Non PNS) dengan
 * total berbeda, jadi item disimpan terpisah. Format JSON sama dengan memo_entries.item_mak:
 * [{"kode":"004671","uraian":"...","jumlah":1620000}, ...]
 * Kolom `mak` tetap 6 bagian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->json('item_mak_pns')->nullable()->after('mak');
            $table->json('item_mak_non_pns')->nullable()->after('item_mak_pns');
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn(['item_mak_pns', 'item_mak_non_pns']);
        });
    }
};
