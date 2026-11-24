<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MakUnitService
{
    /**
     * Tandai tiap MakOption dengan:
     *  - unit_pemilik : array kode unit pemilik MAK (dari anggaran_pemetaan)
     *  - rekomendasi  : true kalau MAK milik unit user yang login
     * Lalu urutkan: rekomendasi dulu, sisanya urut kode MAK.
     */
    public static function tandai(Collection $options, ?User $user): Collection
    {
        $baris = DB::table('anggaran_pemetaan')->get()->map(fn($r) => [
            'keg' => filled($r->keg_kode) ? Str::afterLast((string) $r->keg_kode, '.') : null, // "CL.7458" -> "7458"
            'ro' => (string) $r->ro_kode,
            'komp' => filled($r->komp_kode) ? (string) $r->komp_kode : null,
            'sub' => filled($r->sub_kode) ? (string) $r->sub_kode : null,
            'unit' => (string) $r->unit,
        ]);
        $unitUser = $user?->unit?->kode;
        $cache = [];

        foreach ($options as $o) {
            $prefix = Str::beforeLast($o->mak, '.');
            $cache[$prefix] ??= self::pemilik($baris, $o->mak);

            $o->unit_pemilik = $cache[$prefix];
            $o->rekomendasi = $unitUser !== null && in_array($unitUser, $cache[$prefix], true);
        }

        return $options->sortBy([['rekomendasi', 'desc'], ['mak', 'asc']])->values();
    }

    private static function pemilik(Collection $baris, string $mak): array
    {
        $s = explode('.', $mak);
        if (count($s) < 6) {
            return []; // MAK manual / format lain
        }

        array_pop($s);            // akun
        $sub = array_pop($s);
        $komp = array_pop($s);
        $keg = array_shift($s);
        $ro = implode('.', $s);

        $cocok = $baris->filter(
            fn($r) =>
                $r['ro'] === $ro
                && ($r['keg'] === null || $r['keg'] === $keg)
                && ($r['komp'] === null || $r['komp'] === $komp)
                && ($r['sub'] === null || in_array($r['sub'], [$sub, "$komp.$sub"], true))
        );

        if ($cocok->isEmpty()) {
            return [];
        }

        // Makin spesifik makin menang: sub > komp > keg
        $skor = fn($r) => ($r['keg'] !== null ? 1 : 0)
            + ($r['komp'] !== null ? 2 : 0)
            + ($r['sub'] !== null ? 4 : 0);
        $max = $cocok->max($skor);

        return $cocok->filter(fn($r) => $skor($r) === $max)
            ->pluck('unit')->unique()->values()->all();
    }
}