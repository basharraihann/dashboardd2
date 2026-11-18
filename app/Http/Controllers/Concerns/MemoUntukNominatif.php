<?php

namespace App\Http\Controllers\Concerns;

use App\Models\MemoEntry;

trait MemoUntukNominatif
{
    // findOrFail kena global scope unit, jadi unit lain dapat 404.
    protected function memoUntukNominatif($id): ?MemoEntry
    {
        if (blank($id)) {
            return null;
        }

        $memo = MemoEntry::findOrFail($id);

        abort_unless($memo->perlu_nominatif, 422, 'Memo ini tidak ditandai perlu nominatif.');
        abort_unless($memo->bolehDiubah(), 403, 'Memo sudah dikirim ke Keuangan, nominatif tidak bisa ditambah.');

        return $memo;
    }

    protected function prefillDariMemo(?MemoEntry $memo): array
    {
        if (!$memo) {
            return [];
        }

        return [
            'uraian_kegiatan' => $memo->uraian_kegiatan,
            'tanggal' => optional($memo->tanggal_memo)->format('Y-m-d'),
            'pic_id' => $memo->pic_id,
            'ppk_id' => $memo->ppk_id,
            'bendahara_id' => $memo->bendahara_id,
            'penanggung_jawab_id' => $memo->penanggung_jawab_id,
        ];
    }
}