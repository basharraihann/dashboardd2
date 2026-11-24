<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // database/migrations/xxxx_create_memo_nomor_settings_table.php
    public function up(): void
    {
        Schema::create('memo_nomor_settings', function (Blueprint $t) {
            $t->id();
            $t->string('template', 100)->default('{no}.KU.00.00/{tahun}');
            $t->unsignedInteger('nomor_awal')->default(385);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_nomor_settings');
    }
};
