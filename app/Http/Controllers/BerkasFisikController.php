<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BerkasFisikController extends Controller
{
    public function simpan(Request $r, $memo)
    {
        abort_unless($r->user()?->can('kelola-anggaran'), 403);
        $data = $r->validate(['posisi' => ['required', 'in:Tim Keuangan,PPSPM,Unit']]);

        $terakhir = DB::table('memo_berkas_fisik')
            ->where('memo_key', $memo)->latest('id')->value('posisi');

        if ($terakhir !== $data['posisi']) {
            DB::table('memo_berkas_fisik')->insert([
                'memo_key' => $memo,
                'posisi' => $data['posisi'],
                'oleh' => $r->user()->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}