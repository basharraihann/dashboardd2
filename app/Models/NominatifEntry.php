<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUnit;
use Illuminate\Database\Eloquent\Model;
use App\Services\MemoTahapService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominatifEntry extends Model
{
    use BelongsToUnit;

    protected $table = 'nominatif_entries';

    protected $fillable = [
        'unit_id',
        'memo_entry_id',
        'uraian_kegiatan',
        'tanggal',
        'provinsi',
        'kota',
        'ppk_id',
        'bendahara_id',
        'jenis_detail',
        'penanggung_jawab_id',
        'pic_id',
    ];
    protected $casts = [
        'tanggal' => 'date',
    ];

    public function peserta()
    {
        return $this->hasMany(NominatifPeserta::class, 'nominatif_entry_id')->orderBy('urutan');
    }

    public function ppk()
    {
        return $this->belongsTo(Pegawai::class, 'ppk_id');
    }

    public function bendahara()
    {
        return $this->belongsTo(Pegawai::class, 'bendahara_id');
    }

    // ===== BARU: mulai dari sini =====

    public function items()
    {
        return $this->hasMany(NominatifItem::class, 'nominatif_entry_id')->orderBy('urutan');
    }

    public function penanggungJawab()
    {
        return $this->belongsTo(Pegawai::class, 'penanggung_jawab_id');
    }

    public function pic()
    {
        return $this->belongsTo(Pegawai::class, 'pic_id');
    }

    public function totalSubtotal(): int
    {
        return (int) $this->items->sum(fn($i) => $i->subtotal);
    }

    public function totalPpn(): int
    {
        return (int) $this->items->sum(fn($i) => $i->ppn);
    }

    public function totalPph22(): int
    {
        return (int) $this->items->sum(fn($i) => $i->pph22);
    }

    public function totalPph23(): int
    {
        return (int) $this->items->sum(fn($i) => $i->pph23);
    }

    public function totalAkhir(): int
    {
        return (int) $this->items->sum(fn($i) => $i->total);
    }

    // ===== BARU: sampai sini =====

    public function totalBruto(): int
    {
        return (int) $this->peserta->sum(fn($p) => $p->bruto);
    }

    public function totalPajak(): int
    {
        return (int) $this->peserta->sum(fn($p) => $p->pajak);
    }

    public function totalNetto(): int
    {
        return $this->totalBruto() - $this->totalPajak();
    }

    public function memo(): BelongsTo
    {
        return $this->belongsTo(MemoEntry::class, 'memo_entry_id');
    }

    // Terkunci kalau memo sudah keluar dari draft/dikembalikan (sudah dikirim ke Keuangan).
// Nominatif tanpa memo (mandiri atau lama) tidak pernah terkunci.
    public function terkunci(): bool
    {
        $memo = $this->memo;

        if (!$memo) {
            return false;
        }

        $status = $memo->status_anggaran ?? 'outstanding';
        $kunci = 'entry-' . $memo->id;
        $tahap = MemoTahapService::petaTahap([$kunci => $status])[$kunci] ?? null;

        return !($status === 'outstanding' && in_array($tahap, ['draft', 'dikembalikan'], true));
    }

}