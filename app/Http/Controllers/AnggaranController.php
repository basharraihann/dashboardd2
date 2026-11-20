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
    public function index()
    {

        $canUpload = (bool) auth()->user()?->isAdmin();

        return view('anggaran.index', compact('canUpload'));
    }

    public function data()
    {
        $active = AnggaranUpload::where('is_active', true)->latest('id')->first();

        if (!$active) {
            return response()->json(['upload' => null, 'items' => [], 'outstanding' => (object) []]);
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
                'realisasi_lalu',   // baru
                'realisasi_ini',    // baru
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
        ]);
    }

    /**
     * Kunci MAK yang seragam antara memo dan tabel anggaran: 6 bagian terakhir kode MAK
     * (Kegiatan.KRO.RO.Komponen.Sub.Akun), komponen di-pad 3 digit.
     *   CL.7458.ABB.001.051.0A.521211  -> 7458.ABB.001.051.0A.521211
     *   7458.ABB.001.51.0A.521211      -> 7458.ABB.001.051.0A.521211
     * Harus sama persis dengan makKey() di resources/views/anggaran/index.blade.php.
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

    public function export(Request $request)
    {
        $activeUpload = AnggaranUpload::where('is_active', true)->latest('id')->first();

        $items = [];
        if ($activeUpload) {
            $items = AnggaranItem::where('anggaran_upload_id', $activeUpload->id)
                ->orderBy('id')
                ->get();

            // Filter dari halaman monitor (opsional): unit = kelompok/induk (mis. D1),
            // sub_unit = unit kerja di dalamnya (mis. "Asdep 1 D1"; "D1 (langsung)" = langsung ke Deputi).
            $induk = trim((string) $request->query('unit', ''));
            $subUnit = trim((string) $request->query('sub_unit', ''));
            if ($induk !== '' || $subUnit !== '') {
                $resolver = app(AnggaranUnitResolver::class);
                $items = $items->filter(function ($i) use ($resolver, $induk, $subUnit) {
                    $m = $resolver->resolve($i);

                    return ($induk === '' || $m['induk'] === $induk)
                        && ($subUnit === ''
                            || $m['unit'] === $subUnit
                            || $m['unit'] . ' (langsung)' === $subUnit);
                })->values();
            }
        }

        return AnggaranExcel::download(
            $items,
            $activeUpload,
            $request->only(['keg', 'ro', 'komp', 'sub', 'akun', 'q', 'level'])
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
            'per_tanggal' => ['nullable', 'date'],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat .xlsx atau .xls.',
            'file.max' => 'Ukuran file maksimal 20 MB.',
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

        // Tanggal laporan: input manual, kalau kosong ditebak dari nama file (ddmmyy).
        $tgl = $request->filled('per_tanggal')
            ? $request->input('per_tanggal')
            : MakSyncService::tanggalDariNamaFile($file->getClientOriginalName())?->toDateString();
        $upload->update(['per_tanggal' => $tgl]);

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