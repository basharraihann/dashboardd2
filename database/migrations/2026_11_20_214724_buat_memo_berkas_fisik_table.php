<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('memo_berkas_fisik', function (Blueprint $t) {
            $t->id();
            $t->string('memo_key')->index();   // sama dengan $m['id'] di Blade
            $t->string('posisi', 20);          // Tim Keuangan | PPSPM | Unit
            $t->string('oleh')->nullable();    // nama user yang mengubah
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_berkas_fisik');
    }
};
