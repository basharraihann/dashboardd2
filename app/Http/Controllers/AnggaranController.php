<?php

namespace App\Http\Controllers;

use App\Models\AnggaranItem;
use App\Models\AnggaranUpload;
use App\Services\AnggaranImportService;
use App\Services\AnggaranUnitResolver;
use App\Services\MakOutstandingService;
use App\Services\MakSyncService;
use App\Support\AnggaranExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AnggaranController extends Controller
{
    /** Nama unit untuk item yang belum dipetakan (sama dengan BELUM di halaman Anggaran). */
    private const BELUM_DIPETAKAN = 'Belum dipetakan';

    public function index()
    {
        $canUpload = (bool) auth()->user()?->isAdmin();

        return view('anggaran.index', compact('canUpload'));
    }

    public function data()
    {
        $active = AnggaranUpload::where('is_active', true)->latest('id')->first();

        if (!$active) {
            return response()->json(['upload' => null, 'items' => [], 'outstanding' => (object) [], 'outstanding_item' => (object) []]);
        }

        // Pemetaan RO -> unit kerja dicocokkan di sini (saat data diambil), bukan saat import,
        // jadi mengubah pemetaan tidak perlu upload ulang.
        $resolver = app(AnggaranUnitResolver::class);

        $items = AnggaranItem::where('anggaran_upload_id', $active->id)
            ->orderBy('id')
            ->get([
                'keg_kode',
                'keg_uraian',
                'ro_kode',
                'ro_uraian',
                'komp_kode',
                'komp_uraian',
                'sub_kode',
                'sub_uraian',
                'akun_kode',
                'akun_uraian',
                'item_kode',
                'item_uraian',
                'pagu',
                'realisasi_lalu',
                'realisasi_ini',
                'realisasi',
            ])
            ->map(fn($i) => $i->toArray() + $resolver->resolve($i)) // + 'unit', 'induk', 'sub_unit'
            ->values();

        return response()->json([
            'upload' => [
                'periode' => $active->periode,
                'satker' => $active->satker_nama,
                'file' => $active->nama_file,
                'at' => $active->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ],
            'items' => $items,
            // [kunci MAK => total outstanding]. Dipasang di baris Akun oleh halaman, lalu dijumlah ke atas.
            'outstanding' => (object) $this->outstandingPerKunci(),
            // ["kunci MAK|kode item" => total outstanding] untuk memo yang dipilih sampai level item.
            'outstanding_item' => (object) $this->outstandingPerItem(),
        ]);
    }

    /**
     * Kunci MAK yang seragam antara memo dan tabel anggaran: 6 bagian terakhir kode MAK
     * (Kegiatan.KRO.RO.Komponen.Sub.Akun), komponen di-pad 3 digit.
     *   CL.7458.ABB.001.051.0A.521211  -> 7458.ABB.001.051.0A.521211
     *   7458.ABB.001.51.0A.521211      -> 7458.ABB.001.051.0A.521211
     * Harus sama persis dengan makKey() di resources/views/anggaran/index.blade.php
     * dan AnggaranExcel::makKey().
     */
    private function kunciMak(string $mak): string
    {
        $p = array_slice(array_map('trim', explode('.', trim($mak))), -6);
        if (count($p) === 6 && ctype_digit($p[3]) && strlen($p[3]) < 3) {
            $p[3] = str_pad($p[3], 3, '0', STR_PAD_LEFT);
        }

        return implode('.', $p);
    }

    /** Total outstanding per kunci MAK, hanya yang > 0. */
    private function outstandingPerKunci(): array
    {
        $hasil = [];
        foreach (MakOutstandingService::perMak() as $mak => $total) {
            $total = (int) $total;
            if ($total <= 0) {
                continue;
            }
            $k = $this->kunciMak((string) $mak);
            $hasil[$k] = ($hasil[$k] ?? 0) + $total;
        }

        return $hasil;
    }

    /** Total outstanding per "kunci MAK|kode item", hanya yang > 0. */
    private function outstandingPerItem(): array
    {
        $hasil = [];
        foreach (MakOutstandingService::perItem() as $kunci => $total) {
            $total = (int) $total;
            if ($total <= 0 || !str_contains($kunci, '|')) {
                continue;
            }
            [$mak, $item] = explode('|', $kunci, 2);
            $k = $this->kunciMak($mak) . '|' . trim($item);
            $hasil[$k] = ($hasil[$k] ?? 0) + $total;
        }

        return $hasil;
    }

    public function export(Request $request)
    {
        $activeUpload = AnggaranUpload::where('is_active', true)->latest('id')->first();

        $items = [];
        if ($activeUpload) {
            $items = AnggaranItem::where('anggaran_upload_id', $activeUpload->id)
                ->orderBy('id')
                ->get();

            // Filter dari halaman monitor (opsional). unit = kelompok/induk (mis. D1), bisa lebih dari satu
            // dipisah koma ("D1,D2,BIRO UHM"). sub_unit = unit kerja di dalamnya (mis. "Asdep 1 D1";
            // "D1 (langsung)" = langsung ke Deputi).
            $induks = array_values(array_filter(array_map('trim', explode(',', (string) $request->query('unit', '')))));
            $subUnit = trim((string) $request->query('sub_unit', ''));
            if ($induks !== [] || $subUnit !== '') {
                $resolver = app(AnggaranUnitResolver::class);
                $items = $items->filter(function ($i) use ($resolver, $induks, $subUnit) {
                    $m = $resolver->resolve($i);
                    $unit = $m['unit'] ?: self::BELUM_DIPETAKAN;
                    $induk = $m['induk'] ?: $unit;

                    return ($induks === [] || in_array($induk, $induks, true))
                        && ($subUnit === ''
                            || $unit === $subUnit
                            || $unit . ' (langsung)' === $subUnit);
                })->values();
            }
        }

        return AnggaranExcel::download(
            $items,
            $activeUpload,
            $request->only(['keg', 'ro', 'komp', 'sub', 'akun', 'q', 'level']),
            $this->outstandingPerKunci()
        );
    }

    public function uploadForm()
    {
        $uploads = AnggaranUpload::with('uploader:id,name')->latest('id')->take(10)->get();

        return view('anggaran.upload', compact('uploads'));
    }

    public function download(AnggaranUpload $upload)
    {
        $disk = Storage::disk('local');
        if (!$upload->file_path || !$disk->exists($upload->file_path)) {
            return back()->with('error', 'File asli untuk upload ini tidak tersimpan.');
        }

        return $disk->download($upload->file_path, $upload->nama_file);
    }

    public function uploadStore(Request $request, AnggaranImportService $importer)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'per_tanggal' => ['required', 'date'],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat .xlsx atau .xls.',
            'file.max' => 'Ukuran file maksimal 20 MB.',
            'per_tanggal.required' => 'Isi tanggal laporan terlebih dahulu.',
            'per_tanggal.date' => 'Tanggal laporan tidak valid.',
        ]);

        $file = $request->file('file');

        try {
            $upload = $importer->import($file->getRealPath(), $file->getClientOriginalName(), auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Import anggaran gagal: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Gagal memproses file. Pastikan file asli dari SAKTI dan belum diubah.');
        }

        // Simpan file asli di storage privat (bukan public). Gagal simpan tidak membatalkan import.
        try {
            $path = $file->storeAs('anggaran', $upload->id . '.' . strtolower($file->getClientOriginalExtension()), 'local');
            $upload->update(['file_path' => $path]);
        } catch (Throwable $e) {
            Log::warning('File asli anggaran gagal disimpan: ' . $e->getMessage());
        }

        // Tanggal laporan wajib diisi di form (sudah divalidasi di atas), tidak lagi ditebak dari nama file.
        $upload->update(['per_tanggal' => $request->input('per_tanggal')]);

        $pesan = "Data anggaran diperbarui: {$upload->jumlah_item} item ({$upload->periode}).";
        try {
            $r = app(MakSyncService::class)->sync($upload);
            $pesan .= " MAK ikut diperbarui: {$r['baru']} baru, {$r['diperbarui']} diperbarui, {$r['nonaktif']} nonaktif.";
            if (!$r['pagu_cocok']) {
                $pesan .= ' Peringatan: total pagu MAK tidak sama dengan total upload.';
            }
        } catch (Throwable $e) {
            Log::error('Sinkronisasi MAK gagal: ' . $e->getMessage());
            $pesan .= ' Tapi sinkronisasi MAK gagal: ' . $e->getMessage() . ' (jalankan php artisan mak:sync setelah diperbaiki).';
        }

        return redirect()->route('anggaran.index')->with('success', $pesan);
    }
}