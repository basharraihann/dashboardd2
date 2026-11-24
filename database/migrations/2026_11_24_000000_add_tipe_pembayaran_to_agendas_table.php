<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            // Tipe pembayaran (ls, up, dst. atau ketikan manual), maks. 20 karakter
            // sama seperti di tabel memo. Default 'ls' supaya agenda lama otomatis terisi.
            $table->string('tipe_pembayaran', 20)->default('ls')->after('alat_angkut');
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('tipe_pembayaran');
        });
    }
};
