<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\MemoEntry;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class UnduhMassalController extends Controller
{
    private const MAKS = 60;
    private const POLA_KEY = '/^(memo\|(entry-\d+|agenda-\d+-(pns|non_pns))'
        . '|nomagenda\|\d+\|(pns|non-pns)'
        . '|nomentry\|\d+\|(custom|honor)'
        . '|(spd|rincian|riil|merge)\|\d+\|\d+)$/';

    private const JENIS_DOK = [
        'memo' => 'Memorandum',
        'nomagenda' => 'Nominatif',
        'nomentry' => 'Nominatif',
        'spd' => 'SPD',
        'rincian' => 'Rincian Biaya',
        'riil' => 'Pengeluaran Riil',
        'merge' => 'Merge Dokumen',
    ];

    public function index(Request $request)
    {
        $semua = $this->dokumen();
        $rows = $this->saring($semua, $request);

        $grup = $rows->groupBy('memo_id')->map(fn($items) => [
            'memo' => $items->first(),
            'docs' => $items->values(),
        ])->values();

        return view('unduh-massal.index', [
            'grup' => $grup->take(100),
            'totalMemo' => $grup->count(),
            'totalDok' => $rows->count(),
            'maks' => self::MAKS,
            'opsiJenisDok' => collect(self::JENIS_DOK)->unique()->values(),
            'opsiJenis' => $semua->pluck('jenis_label')->filter()->unique()->sort()->values(),
            'opsiUnit' => $semua->pluck('unit')->filter()->unique()->sort()->values(),
            'opsiPic' => $semua->pluck('pic')->filter()->unique()->sort()->values(),
            'opsiMak' => $semua->pluck('mak')->filter()->unique()->sort()->values(),
            'opsiTahap' => $semua->pluck('tahap')->filter()->unique()->sort()->values(),
            'lintasUnit' => $this->lintasUnit(),
        ]);
    }

    /** Excel seluruh hasil filter (tanpa batas centang). */
    public function excel(Request $request)
    {
        $rows = $this->saring($this->dokumen(), $request);
        $isi = $this->buatExcel($rows);

        return response($isi, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Rekap-Dokumen-' . now()->format('Ymd-His') . '.xlsx"',
        ]);
    }

    public function unduh(Request $request)
    {
        $data = $request->validate([
            'keys' => ['required', 'array', 'min:1', 'max:' . self::MAKS],
            'keys.*' => ['string', 'regex:' . self::POLA_KEY],
            'sertakan_excel' => ['nullable', 'boolean'],
        ], [
            'keys.required' => 'Pilih minimal satu dokumen.',
            'keys.max' => 'Maksimal ' . self::MAKS . ' dokumen per unduhan.',
        ]);

        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        // Hanya dokumen yang ada di daftar yang boleh dilihat user ini.
        $peta = $this->dokumen()->keyBy('key');
        $terpilih = collect($data['keys'])->unique()->filter(fn($k) => $peta->has($k))->values();

        if ($terpilih->isEmpty()) {
            return back()->with('error', 'Dokumen yang dipilih tidak ditemukan atau tidak bisa diakses.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'unduh');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $gagal = [];
        foreach ($terpilih as $key) {
            $r = $peta[$key];
            try {
                $zip->addFromString($r['file'], $this->isiPdf($key));
            } catch (\Throwable $e) {
                report($e);
                $gagal[] = "{$r['jenis_dok']} {$r['nomor_memo']} ({$key}): " . $e->getMessage();
            }
        }

        if ($data['sertakan_excel'] ?? true) {
            $zip->addFromString('Rekap.xlsx', $this->buatExcel($terpilih->map(fn($k) => $peta[$k])));
        }
        if ($gagal) {
            $zip->addFromString('CATATAN.txt', "Dokumen berikut gagal dibuat:\n\n" . implode("\n", $gagal));
        }
        $zip->close();

        return response()
            ->download($tmp, 'Dokumen-' . now()->format('Ymd-His') . '.zip')
            ->deleteFileAfterSend(true);
    }

    // ---- Pembuatan PDF ----

    private function isiPdf(string $key): string
    {
        $p = explode('|', $key);

        switch ($p[0]) {
            case 'memo':
                if (preg_match('/^entry-(\d+)$/', $p[1], $x)) {
                    return app(MemoController::class)->pdf((int) $x[1])->getContent();
                }
                preg_match('/^agenda-(\d+)-(pns|non_pns)$/', $p[1], $x);
                return app(AgendaPdfController::class)
                    ->memorandumPdf($this->agenda($x[1]), str_replace('_', '-', $x[2]))->getContent();

            case 'nomagenda':
                return app(AgendaPdfController::class)->generateNominatif($this->agenda($p[1]), $p[2])->getContent();

            case 'nomentry':
                $nom = \App\Models\NominatifEntry::withoutGlobalScope('unit')->findOrFail($p[1]);
                return $p[2] === 'custom'
                    ? app(NominatifCustomController::class)->pdf($nom)->getContent()
                    : app(NominatifController::class)->pdf($nom)->getContent();

            default: // spd, rincian, riil, merge
                $agenda = $this->agenda($p[1]);
                $pegawai = Pegawai::withoutGlobalScopes()->findOrFail($p[2]);
                $c = app(AgendaPdfController::class);

                $respons = match ($p[0]) {
                    'spd' => $c->generateSpd($agenda, $pegawai),
                    'rincian' => $c->generateRincianBiaya($agenda, $pegawai),
                    'riil' => $c->generatePengeluaranRiil($agenda, $pegawai),
                    'merge' => $c->generateMergedPdf($agenda, $pegawai),
                };

                return $respons->getContent();
        }
    }

    private function agenda($id): Agenda
    {
        return Agenda::withoutGlobalScope('unit')->findOrFail($id);
    }

    // ---- Daftar dokumen ----

    private function lintasUnit(): bool
    {
        return (bool) Gate::allows('kelola-anggaran');
    }

    /** Memo yang boleh dilihat user ini (sumber sama dengan Monitoring Memo). */
    private function daftarMemo()
    {
        $lintas = $this->lintasUnit();
        $rows = app(MonitoringMemoController::class)->semuaMemo($lintas);

        if ($lintas) {
            $rows = $rows->reject(fn($m) => in_array($m['tahap'], ['draft', 'dikembalikan'], true));
        }

        return $rows->sortByDesc(fn($m) => $m['tgl_memo']?->timestamp ?? 0)->values();
    }

    /** Satu baris per dokumen. */
    private function dokumen()
    {
        $memos = $this->daftarMemo();

        $agendaIds = $memos->filter(fn($m) => str_starts_with($m['id'], 'agenda-'))
            ->map(fn($m) => (int) explode('-', $m['id'])[1])->unique()->all();
        $entryIds = $memos->filter(fn($m) => str_starts_with($m['id'], 'entry-'))
            ->map(fn($m) => (int) substr($m['id'], 6))->unique()->all();

        $agendas = Agenda::withoutGlobalScope('unit')->with('pegawai')
            ->whereIn('id', $agendaIds)->get()->keyBy('id');
        $entries = MemoEntry::withoutGlobalScope('unit')
            ->with(['nominatifs' => fn($q) => $q->withoutGlobalScope('unit')])
            ->whereIn('id', $entryIds)->get()->keyBy('id');

        $out = collect();

        foreach ($memos as $m) {
            $folder = $this->aman($m['nomor_memo']) . ($m['label'] ? '-' . $this->aman($m['label']) : '');

            $tambah = function (string $key, string $jenisDok, string $nama, ?string $pegawai = null, bool $pakaiNominal = false) use (&$out, $m, $folder) {
                $out->push([
                    'key' => $key,
                    'jenis_dok' => $jenisDok,
                    'memo_id' => $m['id'],
                    'nomor_memo' => $m['nomor_memo'],
                    'label' => $m['label'],
                    'tgl_memo' => $m['tgl_memo'],
                    'unit' => $m['unit'],
                    'jenis_label' => $m['jenis_label'],
                    'uraian' => $m['uraian'],
                    'pic' => $m['pic'],
                    'mak' => $m['mak'],
                    'nominal' => $pakaiNominal ? $m['nominal'] : null,
                    'status' => $m['status'],
                    'pegawai' => $pegawai,
                    'file' => "{$folder}/{$nama}.pdf",
                    'tahap' => $m['tahap'],
                    'nominal_memo' => $m['nominal'],
                    'folder' => $folder,
                ]);
            };

            $tambah("memo|{$m['id']}", 'Memorandum', 'Memorandum', null, true);

            if (preg_match('/^agenda-(\d+)-(pns|non_pns)$/', $m['id'], $x)) {
                $agenda = $agendas[(int) $x[1]] ?? null;
                if (!$agenda) {
                    continue;
                }
                $slug = $x[2] === 'pns' ? 'pns' : 'non-pns';
                $label = $x[2] === 'pns' ? 'PNS' : 'Non PNS';
                $peserta = $agenda->pegawai->where('status_kepegawaian', $label)->values();

                if ($peserta->isNotEmpty()) {
                    $tambah("nomagenda|{$agenda->id}|{$slug}", 'Nominatif', 'Nominatif');
                }
                foreach ($peserta as $p) {
                    $nama = $p->nama_gelar ?? $p->nama;
                    $n = $this->aman($nama);
                    $tambah("spd|{$agenda->id}|{$p->id}", 'SPD', "SPD-{$n}", $nama);
                    $tambah("rincian|{$agenda->id}|{$p->id}", 'Rincian Biaya', "RincianBiaya-{$n}", $nama);
                    $tambah("riil|{$agenda->id}|{$p->id}", 'Pengeluaran Riil', "PengeluaranRiil-{$n}", $nama);
                    $tambah("merge|{$agenda->id}|{$p->id}", 'Merge Dokumen', "Merge-{$n}", $nama);
                }
            } elseif (preg_match('/^entry-(\d+)$/', $m['id'], $x)) {
                $entry = $entries[(int) $x[1]] ?? null;
                foreach (($entry?->nominatifs ?? []) as $i => $nom) {
                    $custom = $nom->jenis_detail === 'custom';
                    $tambah(
                        'nomentry|' . $nom->id . '|' . ($custom ? 'custom' : 'honor'),
                        'Nominatif',
                        'Nominatif-' . ($custom ? 'Custom' : 'Honorarium') . '-' . ($i + 1)
                    );
                }
            }
        }

        return $out;
    }

    private function saring($rows, Request $r)
    {
        $q = mb_strtolower(trim((string) $r->query('q', '')));
        $dok = $r->query('dok');
        $jenis = $r->query('jenis');
        $status = $r->query('status');
        $tahap = $r->query('tahap');
        $unit = $r->query('unit');
        $pic = $r->query('pic');
        $mak = $r->query('mak');
        $dari = $r->query('dari');
        $sampai = $r->query('sampai');
        $min = $r->query('min') !== null && $r->query('min') !== '' ? (int) preg_replace('/\D/', '', $r->query('min')) : null;
        $maks = $r->query('maks') !== null && $r->query('maks') !== '' ? (int) preg_replace('/\D/', '', $r->query('maks')) : null;

        return $rows->filter(function ($m) use ($q, $dok, $jenis, $status, $tahap, $unit, $pic, $mak, $dari, $sampai, $min, $maks) {
            if ($q !== '' && !str_contains(mb_strtolower("{$m['nomor_memo']} {$m['uraian']} {$m['pic']} {$m['pegawai']} {$m['mak']}"), $q))
                return false;
            if ($dok && $m['jenis_dok'] !== $dok)
                return false;
            if ($jenis && $m['jenis_label'] !== $jenis)
                return false;
            if ($status && $m['status'] !== $status)
                return false;
            if ($tahap && $m['tahap'] !== $tahap)
                return false;
            if ($unit && $m['unit'] !== $unit)
                return false;
            if ($pic && $m['pic'] !== $pic)
                return false;
            if ($mak && $m['mak'] !== $mak)
                return false;
            $tgl = $m['tgl_memo']?->toDateString();
            if ($dari && (!$tgl || $tgl < $dari))
                return false;
            if ($sampai && (!$tgl || $tgl > $sampai))
                return false;
            $nom = (int) $m['nominal_memo'];
            if ($min !== null && $nom < $min)
                return false;
            if ($maks !== null && $nom > $maks)
                return false;
            return true;
        })->values();
    }
    private function aman(?string $s): string
    {
        $s = trim(preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $s), '-');

        return $s !== '' ? $s : 'dok';
    }

    private function buatExcel($rows): string
    {
        $ss = new Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Rekap');

        $ws->fromArray([
            'No',
            'Jenis Dokumen',
            'Nomor Memo',
            'Tanggal Memo',
            'Unit',
            'Jenis Memo',
            'Pegawai',
            'Uraian',
            'PIC',
            'MAK',
            'Nominal Memo',
            'Status',
            'Nama File',
        ], null, 'A1');
        $ws->getStyle('A1:M1')->getFont()->setBold(true);

        $r = 2;
        foreach ($rows as $i => $m) {
            $ws->setCellValue("A{$r}", $i + 1);
            $ws->setCellValue("B{$r}", $m['jenis_dok']);
            $ws->setCellValueExplicit("C{$r}", trim($m['nomor_memo'] . ' ' . ($m['label'] ? "({$m['label']})" : '')), DataType::TYPE_STRING);
            $ws->setCellValue("D{$r}", $m['tgl_memo']?->format('d-m-Y'));
            $ws->setCellValue("E{$r}", $m['unit']);
            $ws->setCellValue("F{$r}", $m['jenis_label']);
            $ws->setCellValue("G{$r}", $m['pegawai']);
            $ws->setCellValue("H{$r}", $m['uraian']);
            $ws->setCellValue("I{$r}", $m['pic']);
            $ws->setCellValueExplicit("J{$r}", (string) $m['mak'], DataType::TYPE_STRING);
            if ($m['nominal'] !== null) {
                $ws->setCellValue("K{$r}", (int) $m['nominal']);
            }
            $ws->setCellValue("L{$r}", strtoupper((string) $m['status']));
            $ws->setCellValue("M{$r}", $m['file']);
            $r++;
        }

        $ws->getStyle("K2:K{$r}")->getNumberFormat()->setFormatCode('#,##0');
        foreach (range('A', 'M') as $col) {
            $ws->getColumnDimension($col)->setAutoSize(true);
        }
        $ws->getColumnDimension('H')->setAutoSize(false)->setWidth(60);
        $ws->getStyle("H2:H{$r}")->getAlignment()->setWrapText(true);
        $ws->freezePane('A2');

        $path = tempnam(sys_get_temp_dir(), 'rekap');
        (new Xlsx($ss))->save($path);
        $isi = file_get_contents($path);
        @unlink($path);

        return $isi;
    }
}