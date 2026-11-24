<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\MemoEntry;
use App\Models\MemoNomorSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Nomor memo itu satu urutan global — gak dibedain PNS/Non-PNS, gak ada
 * reset per tahun, dan gak dibedain per unit. Nomor "terbesar yang pernah
 * dipakai" dihitung dari gabungan dua sumber, SEMUA unit (scope 'unit'
 * sengaja dilepas, yang diambil cuma angka/format nomor, bukan uraian):
 *   1. agendas.nomor_memo_pns / nomor_memo_non_pns  (nomor lama, teks bebas,
 *      format "323/LS.D1.PPK/KU.00/07/2026" — angka di depan yang dipakai)
 *   2. memo_entries.nomor_urut                       (nomor yang dibuat lewat
 *      tombol "Ambil Nomor Memo" atau form mandiri)
 *
 * Format nomor (template) dan nomor awal bisa diatur admin lewat tabel
 * memo_nomor_settings. Kalau belum pernah diatur, perilaku lama dipakai:
 * nomor awal 385 dan format meniru nomor terakhir.
 */
class NomorMemoService
{
    /** Nomor awal bawaan kalau admin belum mengatur. */
    private const NOMOR_AWAL_DEFAULT = 385;

    /** Token yang dikenali template format nomor. */
    public const TOKEN = ['{no}', '{tahun}', '{tahun2}', '{bulan}', '{romawi}'];

    /** Nomor memo pertama yang boleh keluar (diatur admin). */
    public static function nomorAwal(): int
    {
        return (int) (MemoNomorSetting::current()?->nomor_awal ?: self::NOMOR_AWAL_DEFAULT);
    }

    /**
     * Render template jadi teks nomor. Contoh:
     * "{no}.KU.00.00/{tahun}" + 386 -> "386.KU.00.00/2026"
     */
    public static function render(string $template, int $urut, ?Carbon $tgl = null): string
    {
        $tgl ??= now();
        $romawi = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return strtr($template, [
            '{no}' => (string) $urut,
            '{tahun}' => $tgl->format('Y'),
            '{tahun2}' => $tgl->format('y'),
            '{bulan}' => $tgl->format('m'),
            '{romawi}' => $romawi[$tgl->month],
        ]);
    }

    /**
     * Nomor urut terbesar yang pernah dipakai dari kedua sumber. Kalau
     * belum ada nomor memo sama sekali, hasilnya nomorAwal() - 1 (jadi nomor
     * berikutnya mulai dari nomorAwal()).
     */
    public static function nomorTerbesar(): int
    {
        $dariAgenda = self::semuaNomorDariAgenda()
            ->map(fn($nomor) => self::ekstrakUrutan($nomor))
            ->filter()
            ->max();

        // lintas unit: nomor itu satu urutan untuk seluruh instansi
        $dariEntries = MemoEntry::withoutGlobalScope('unit')->max('nomor_urut');

        return max((int) $dariAgenda, (int) $dariEntries, self::nomorAwal() - 1);
    }

    public static function nomorBerikutnya(): int
    {
        return self::nomorTerbesar() + 1;
    }

    /**
     * Data lengkap buat tombol "Ambil Nomor Memo": nomor urut berikutnya,
     * "ekor" format (bagian setelah angka), dan nomor jadi.
     *
     * Ekor diambil dari template admin kalau ada; kalau belum diatur,
     * niru nomor terakhir yang pernah dipakai dengan bulan/tahun disesuaikan
     * ke sekarang.
     */
    public static function dataBerikutnya(): array
    {
        $urutan = self::nomorBerikutnya();
        $ekor = self::templateEkor();

        return [
            'urutan' => $urutan,
            'ekor' => $ekor,
            'nomor' => $urutan . $ekor,
        ];
    }

    /**
     * Ambil angka urut di paling depan nomor memo, mis.
     * "323/LS.D1.PPK/KU.00/07/2026" -> 323. Null kalau gak ketemu angka di depan.
     */
    public static function ekstrakUrutan(?string $nomor): ?int
    {
        if (!$nomor) {
            return null;
        }

        return preg_match('/^\s*(\d+)/', $nomor, $m) ? (int) $m[1] : null;
    }

    /**
     * Apakah angka urut dari $nomor sudah dipakai di unit mana pun
     * (memo_entries atau agendas)? Cek lintas unit, dibandingkan per angka urut.
     */
    public static function sudahDipakai(?string $nomor, ?int $ignoreEntryId = null, ?int $ignoreAgendaId = null): bool
    {
        $urut = self::ekstrakUrutan($nomor);

        if ($urut === null) {
            return false;
        }

        $diEntries = MemoEntry::withoutGlobalScope('unit')
            ->where('nomor_urut', $urut)
            ->when($ignoreEntryId, fn($q) => $q->where('id', '!=', $ignoreEntryId))
            ->exists();

        if ($diEntries) {
            return true;
        }

        return Agenda::withoutGlobalScope('unit')
            ->when($ignoreAgendaId, fn($q) => $q->where('id', '!=', $ignoreAgendaId))
            ->where(function ($q) {
                $q->whereNotNull('nomor_memo_pns')->orWhereNotNull('nomor_memo_non_pns');
            })
            ->get(['nomor_memo_pns', 'nomor_memo_non_pns'])
            ->flatMap(fn($a) => [$a->nomor_memo_pns, $a->nomor_memo_non_pns])
            ->filter()
            ->contains(fn($n) => self::ekstrakUrutan($n) === $urut);
    }

    private static function semuaNomorDariAgenda(): Collection
    {
        return Agenda::withoutGlobalScope('unit')
            ->where(function ($q) {
                $q->whereNotNull('nomor_memo_pns')->orWhereNotNull('nomor_memo_non_pns');
            })
            ->get(['nomor_memo_pns', 'nomor_memo_non_pns'])
            ->flatMap(fn($a) => [$a->nomor_memo_pns, $a->nomor_memo_non_pns])
            ->filter();
    }

    /**
     * Susun "ekor" (bagian format setelah angka urut) buat nomor memo berikutnya.
     *
     * 1. Kalau admin sudah menyimpan template, pakai template itu
     *    (semua setelah {no}, token bulan/tahun/romawi diisi sekarang).
     * 2. Kalau belum, tiru nomor terakhir yang pernah dipakai, dengan
     *    bulan/tahun disesuaikan ke sekarang.
     *
     * Deteksi tahun/bulan pakai regex terhadap 4-digit & 2-digit di ujung
     * string, BUKAN explode('/') + hitung index segmen — karena format nomor
     * memo yang beneran dipakai ternyata gak konsisten jumlah segmennya
     * (mis. format lama "323/LS.D1.PPK/KU.00/07/2026" vs format baru
     * "374.KU.00.00/2027").
     */
    private static function templateEkor(): string
    {
        $setting = MemoNomorSetting::current();
        if ($setting && $setting->template) {
            // template selalu diawali {no}; "ekor" = semua setelah {no}
            return self::render(Str::after($setting->template, '{no}'), 0);
        }

        $bulan = now()->format('m');
        $tahun = now()->format('Y');

        $contoh = self::contohTerakhir();

        if (!$contoh) {
            // Belum pernah ada nomor memo sama sekali -> template default
            return ".KU.00.00/{$tahun}";
        }

        // Buang angka urut di depan, sisanya dipakai sbg "ekor" format.
        $ekor = preg_replace('/^\s*\d+/', '', $contoh);

        // Ganti 4 digit tahun di paling ujung ke tahun sekarang.
        $ekor = preg_replace('/\d{4}\s*$/', $tahun, $ekor);

        // Kalau formatnya punya segmen bulan terpisah (".../07/2026"),
        // segmen bulan ikut di-update juga.
        $ekor = preg_replace('/(\/)\d{2}(\/\d{4}\s*$)/', '$1' . $bulan . '$2', $ekor);

        return $ekor;
    }

    /**
     * Teks nomor memo lengkap dengan nomor_urut TERBESAR yang pernah dipakai
     * (dari kedua sumber, semua unit), dipakai sebagai acuan format "ekor".
     */
    private static function contohTerakhir(): ?string
    {
        $entryTerakhir = MemoEntry::withoutGlobalScope('unit')
            ->orderByDesc('nomor_urut')
            ->first()?->nomor_memo;

        $agendaTerakhir = self::semuaNomorDariAgenda()
            ->sortByDesc(fn($n) => self::ekstrakUrutan($n))
            ->first();

        return collect([$entryTerakhir, $agendaTerakhir])
            ->filter()
            ->sortByDesc(fn($n) => self::ekstrakUrutan($n))
            ->first();
    }
}