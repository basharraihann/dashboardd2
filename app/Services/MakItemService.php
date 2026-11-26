<?php

namespace App\Services;

use App\Models\AnggaranUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Daftar item (level paling bawah FA Detail) untuk satu MAK 6 bagian.
 * Sumbernya upload FA yang aktif, sama dengan MakSyncService.
 *
 * MAK 7458.ABB.001.053.AA.524111 = Kegiatan(setelah titik terakhir) . RO(memuat KRO)
 * . Komponen . Sub(setelah titik terakhir) . Akun. Item-nya ada di kolom item_kode.
 */
class MakItemService
{
    /**
     * @return array<int, array{kode:string, uraian:string, pagu:int, realisasi:int, sisa:int}>
     */
    public static function daftar(?string $mak): array
    {
        $p = explode('.', trim((string) $mak));
        if (count($p) !== 6) {
            return [];
        }
        [$keg, $ro1, $ro2, $komp, $sub, $akun] = $p;

        $upload = AnggaranUpload::where('is_active', true)->latest('id')->first();
        if (!$upload) {
            return [];
        }

        return DB::table('anggaran_items')
            ->where('anggaran_upload_id', $upload->id)
            ->whereRaw('TRIM(ro_kode) = ?', [$ro1 . '.' . $ro2])
            ->whereRaw('TRIM(komp_kode) = ?', [$komp])
            ->whereRaw('TRIM(akun_kode) = ?', [$akun])
            ->get()
            ->filter(fn($r) => Str::afterLast(trim($r->keg_kode), '.') === $keg
                && Str::afterLast(trim($r->sub_kode), '.') === $sub)
            ->map(fn($r) => [
                'kode' => trim($r->item_kode),
                'uraian' => trim((string) $r->item_uraian),
                'pagu' => (int) $r->pagu,
                'realisasi' => (int) $r->realisasi,
                'sisa' => (int) $r->pagu - (int) $r->realisasi,
            ])
            ->sortBy('kode')
            ->values()
            ->all();
    }

    /**
     * Rapikan & periksa item yang dikirim form.
     * - Tiap item harus ada di FA untuk MAK tersebut, dan tidak boleh dobel.
     * - Uraian diambil dari FA (bukan dari kiriman form).
     * - Kalau $totalWajib diisi, jumlah semua item harus sama persis dengannya.
     * Mengembalikan null kalau tidak ada item. Kalau salah, melempar ValidationException
     * dengan kunci $field.
     *
     * @return array<int, array{kode:string, uraian:string, jumlah:int}>|null
     */
    public static function rapikan(?array $items, ?string $mak, string $field, ?int $totalWajib = null): ?array
    {
        $items = array_values(array_filter($items ?? [], 'is_array'));
        if (empty($items)) {
            return null;
        }

        try {
            Validator::validate($items, [
                '*.kode' => ['required', 'string', 'max:30'],
                '*.jumlah' => ['required', 'numeric', 'min:1'],
            ]);
        } catch (ValidationException) {
            throw ValidationException::withMessages([
                $field => 'Setiap item harus dipilih dan jumlahnya diisi (minimal 1).',
            ]);
        }

        $peta = self::peta($mak);
        $dipakai = [];
        $hasil = [];

        foreach ($items as $it) {
            $kode = trim((string) $it['kode']);

            if (!isset($peta[$kode])) {
                throw ValidationException::withMessages([
                    $field => "Item {$kode} tidak ditemukan pada MAK ini di data FA yang aktif.",
                ]);
            }
            if (isset($dipakai[$kode])) {
                throw ValidationException::withMessages([
                    $field => "Item {$kode} dipilih lebih dari satu kali.",
                ]);
            }

            $dipakai[$kode] = true;
            $hasil[] = [
                'kode' => $kode,
                'uraian' => $peta[$kode]['uraian'],
                'jumlah' => (int) round((float) $it['jumlah']),
            ];
        }

        $total = array_sum(array_column($hasil, 'jumlah'));
        if ($totalWajib !== null && $total !== $totalWajib) {
            throw ValidationException::withMessages([
                $field => 'Total item (Rp' . number_format($total, 0, ',', '.')
                    . ') harus sama dengan total memo (Rp' . number_format($totalWajib, 0, ',', '.') . ').',
            ]);
        }

        return $hasil;
    }

    /** Daftar item dengan kunci kode item. */
    public static function peta(?string $mak): array
    {
        return collect(self::daftar($mak))->keyBy('kode')->all();
    }

    /**
     * Semua item dari upload FA aktif, dikelompokkan per MAK 6 bagian (satu query).
     * Dipakai pemilih MAK sampai level item (form agenda & memo) dan halaman Nomor MAK.
     * Kode item yang sama di satu MAK dijumlahkan.
     *
     * @return array<string, array<int, array{kode:string, uraian:string, pagu:int, realisasi:int, sisa:int}>>
     */
    public static function semua(): array
    {
        $upload = AnggaranUpload::where('is_active', true)->latest('id')->first();
        if (!$upload) {
            return [];
        }

        // Cache per request (dipanggil beberapa kali saat hitung outstanding per item)
        static $cache = [];
        $kunciCache = $upload->id . '@' . $upload->updated_at;
        if (isset($cache[$kunciCache])) {
            return $cache[$kunciCache];
        }

        $hasil = [];
        $rows = DB::table('anggaran_items')
            ->where('anggaran_upload_id', $upload->id)
            ->select('keg_kode', 'ro_kode', 'komp_kode', 'sub_kode', 'akun_kode', 'item_kode', 'item_uraian', 'pagu', 'realisasi')
            ->orderBy('id')
            ->cursor();

        foreach ($rows as $r) {
            $kode = trim((string) $r->item_kode);
            if ($kode === '') {
                continue;
            }
            $mak = self::makDariBaris($r);

            if (!isset($hasil[$mak][$kode])) {
                $hasil[$mak][$kode] = [
                    'kode' => $kode,
                    'uraian' => trim((string) $r->item_uraian),
                    'pagu' => 0,
                    'realisasi' => 0,
                ];
            }
            $hasil[$mak][$kode]['pagu'] += (int) $r->pagu;
            $hasil[$mak][$kode]['realisasi'] += (int) $r->realisasi;
        }

        foreach ($hasil as $mak => $items) {
            ksort($items, SORT_STRING);
            $hasil[$mak] = array_values(array_map(
                fn($i) => $i + ['sisa' => $i['pagu'] - $i['realisasi']],
                $items
            ));
        }

        return $cache[$kunciCache] = $hasil;
    }

    /** MAK 6 bagian dari satu baris anggaran_items (sama dengan MakSyncService). */
    public static function makDariBaris(object $r): string
    {
        return implode('.', [
            Str::afterLast(trim((string) $r->keg_kode), '.'),
            trim((string) $r->ro_kode),
            trim((string) $r->komp_kode),
            Str::afterLast(trim((string) $r->sub_kode), '.'),
            trim((string) $r->akun_kode),
        ]);
    }

    /**
     * Item yang dipilih lewat pemilih MAK (satu item per MAK).
     * - MAK yang punya item di FA aktif: item wajib dipilih dan harus milik MAK tersebut.
     * - MAK tanpa item (MAK manual / belum ada di FA): item diabaikan, hasilnya null.
     * Hasil disimpan di kolom item_mak (format lama: daftar {kode, uraian, jumlah}),
     * jumlahnya = nominal memo, jadi PDF & outstanding per item tetap terbaca.
     *
     * @return array<int, array{kode:string, uraian:string, jumlah:int}>|null
     */
    public static function pilih(?string $item, ?string $mak, int $jumlah, string $field = 'item_kode'): ?array
    {
        $peta = self::peta($mak);
        if ($peta === []) {
            return null;
        }

        $item = trim((string) $item);
        if ($item === '') {
            throw ValidationException::withMessages([
                $field => 'Pilih MAK sampai level item (klik salah satu item di bawah akun belanja).',
            ]);
        }
        if (!isset($peta[$item])) {
            throw ValidationException::withMessages([
                $field => "Item {$item} tidak ditemukan pada MAK {$mak} di data FA yang aktif.",
            ]);
        }

        return [[
            'kode' => $item,
            'uraian' => $peta[$item]['uraian'],
            'jumlah' => max(0, $jumlah),
        ]];
    }

    /** Kode item pertama dari kolom item_mak (null kalau kosong). */
    public static function kodePertama(?array $items): ?string
    {
        $kode = $items[0]['kode'] ?? null;

        return filled($kode) ? (string) $kode : null;
    }
}
