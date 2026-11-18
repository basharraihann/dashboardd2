<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('memo_entries', function (Blueprint $table) {
            $table->boolean('perlu_nominatif')->default(false)->after('nominal');
        });

        Schema::table('nominatif_entries', function (Blueprint $table) {
            $table->foreignId('memo_entry_id')->nullable()->after('unit_id')
                ->constrained('memo_entries')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nominatif_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('memo_entry_id');
        });

        Schema::table('memo_entries', function (Blueprint $table) {
            $table->dropColumn('perlu_nominatif');
        });
    }
};
