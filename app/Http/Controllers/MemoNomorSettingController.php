<?php

namespace App\Http\Controllers;

use App\Models\MemoNomorSetting;
use App\Services\NomorMemoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemoNomorSettingController extends Controller
{
    public function edit(): View
    {
        return view('memo.nomor-setting', [
            's' => MemoNomorSetting::current()
                ?? new MemoNomorSetting(['template' => '{no}.KU.00.00/{tahun}', 'nomor_awal' => 385]),
            'terbesar' => NomorMemoService::nomorTerbesar(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'template' => [
                'required',
                'string',
                'max:100',
                'starts_with:{no}',
                function ($attr, $val, $fail) {
                    if (substr_count($val, '{no}') !== 1) {
                        return $fail('{no} harus ada tepat satu kali.');
                    }
                    $sisa = str_replace(NomorMemoService::TOKEN, '', $val);
                    if (str_contains($sisa, '{') || str_contains($sisa, '}')) {
                        return $fail('Ada token yang tidak dikenal.');
                    }
                },
            ],
            'nomor_awal' => ['required', 'integer', 'min:1'],
        ], [
            'template.starts_with' => 'Format harus diawali {no}.',
        ]);

        $s = MemoNomorSetting::current() ?? new MemoNomorSetting();
        $s->fill($data)->save();

        // Pakai redirect()->route('memo.index') kalau ingin kembali ke daftar memo setelah simpan.
        return back()->with('success', 'Pengaturan nomor memo disimpan.');
    }
}