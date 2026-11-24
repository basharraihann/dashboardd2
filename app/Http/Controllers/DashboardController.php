<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\AnggaranItem;
use App\Models\AnggaranUpload;
use App\Models\MakOption;
use App\Models\MemoEntry;
use App\Models\Pegawai;
use App\Models\User;
use App\Services\AnggaranUnitResolver;
use App\Services\MakOutstandingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Satu route, isi dashboard menyesuaikan role.
     * Controller hanya mengirim data yang dipakai blok (partial) milik role tersebut,
     * jadi query berat milik role lain tidak ikut jalan.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        [$serapan, $opsiUnit] = $this->serapanUnit($request->query('unit'));
        $uploadAktif = AnggaranUpload::where('is_active', true)->latest('id')->first();
        $data = compact('serapan', 'opsiUnit', 'uploadAktif');

        if ($user->isAdmin()) {
            $jenis = 'admin';
            $sapaan = 'Pantau kondisi sistem, data anggaran, dan aktivitas seluruh unit.';
            $blok = ['ringkasan-admin', 'stat-spj', 'serapan', 'tren-kalender'];
            $aksi = [
                ['label' => 'Agenda Baru', 'route' => 'agendas.create', 'primary' => true],
                ['label' => 'Buat Nomor Memo', 'route' => 'memo.create'],
                ['label' => 'Monitoring Memo', 'route' => 'monitoring.index'],
                ['label' => 'Upload Realisasi', 'route' => 'anggaran.upload'],
                ['label' => 'Pemetaan Unit', 'route' => 'anggaran.pemetaan'],
                ['label' => 'User Manajemen', 'route' => 'users.index'],
            ];
            $data += $this->dataSpj($request) + $this->dataAdmin();
        } elseif ($user->isEselon2()) {
            $jenis = 'eselon2';
            $sapaan = 'Ringkasan serapan anggaran unit Anda.';
            $blok = ['eselon2-chart', 'serapan'];
            $aksi = [
                ['label' => 'Realisasi Anggaran', 'route' => 'anggaran.index', 'primary' => true],
            ];
            $data += $this->dataEselon2($serapan);
        } elseif ($user->isTuSekretariat()) {
            $jenis = 'sekretariat';
            $sapaan = 'Pantau serapan anggaran dan proses memo seluruh unit.';
            $blok = ['serapan'];
            $aksi = [
                ['label' => 'Monitoring Memo', 'route' => 'monitoring.index', 'primary' => true],
                ['label' => 'Realisasi Anggaran', 'route' => 'anggaran.index'],
                ['label' => 'Pegawai', 'route' => 'pegawais.index'],
                ['label' => 'Nomor MAK', 'route' => 'mak-options.index'],
                ['label' => 'Unduh Massal', 'route' => 'unduh.index'],
            ];
        } elseif ($user->isTu()) {
            $jenis = 'tu_unit';
            $sapaan = 'Kelola data pegawai dan Nomor MAK, serta pantau anggaran unit Anda.';
            $blok = ['ringkasan-tu', 'serapan'];
            $aksi = [
                ['label' => 'Pegawai', 'route' => 'pegawais.index', 'primary' => true],
                ['label' => 'Nomor MAK', 'route' => 'mak-options.index'],
                ['label' => 'Realisasi Anggaran', 'route' => 'anggaran.index'],
                ['label' => 'Monitoring Memo', 'route' => 'monitoring.index'],
                ['label' => 'Unduh Massal', 'route' => 'unduh.index'],
            ];
            $data += [
                'jumlahPegawai' => Pegawai::count(),
                'jumlahMak' => MakOption::count(),
            ];
        } else {
            $jenis = 'staf';
            $sapaan = 'Selamat datang kembali. Berikut ringkasan aktivitas perjalanan dinas unit Anda.';
            $blok = ['stat-spj', 'serapan', 'tren-kalender'];
            $aksi = [
                ['label' => 'Agenda Baru', 'route' => 'agendas.create', 'primary' => true],
                ['label' => 'Buat Nomor Memo', 'route' => 'memo.create'],
                ['label' => 'Unduh Massal', 'route' => 'unduh.index'],
                ['label' => 'Monitoring Memo', 'route' => 'monitoring.index'],
            ];
            $data += $this->dataSpj($request);
        }

        return view('dashboard.index', $data + compact('jenis', 'sapaan', 'blok', 'aksi'));
    }

    /**
     * Endpoint JSON untuk pindah bulan kalender tanpa reload halaman.
     * GET /dashboard/kalender?bulan=2026-10
     */
    public function kalender(Request $request)
    {
        return response()->json($this->dataKalender($request));
    }

    /**
     * Data blok SPJ: statistik agenda, dana per tipe, chart tahunan, dan kalender.
     * Dipakai role user dan admin.
     */
    private function dataSpj(Request $request): array
    {
        $now = now();

        // Statistik jumlah agenda
        $agendaBulanIni = Agenda::whereMonth('tanggal_mulai', $now->month)
            ->whereYear('tanggal_mulai', $now->year)
            ->count();

        // Dana diajukan bulan ini, dipecah per tipe pembayaran (LS, UP, GUP, TUP, PTUP)
        $danaBulanIni = $this->danaBulan($now->month, $now->year);

        // Data chart per bulan (Jan - Des) tahun berjalan
        $chartLabels = [];
        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartLabels[] = Carbon::create()->month($i)->translatedFormat('M');
            $chartData[] = $this->danaBulan($i, $now->year)['total'];
        }
        $chartKosong = array_sum($chartData) == 0;

        // Data awal kalender
        $kalender = $this->dataKalender($request);

        return compact(
            'agendaBulanIni',
            'danaBulanIni',
            'chartLabels',
            'chartData',
            'chartKosong',
            'kalender'
        );
    }

    /**
     * Data blok ringkasan admin: jumlah user per role, upload realisasi aktif,
     * dan jumlah RO yang belum dipetakan ke unit.
     */
    private function dataAdmin(): array
    {
        $userPerRole = User::selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $uploadAktif = AnggaranUpload::where('is_active', true)->latest('id')->first();

        $roBelumDipetakan = 0;
        if ($uploadAktif) {
            $resolver = app(AnggaranUnitResolver::class);
            $ro = [];
            foreach (AnggaranItem::where('anggaran_upload_id', $uploadAktif->id)->get() as $i) {
                $m = $resolver->resolve($i);
                if (empty($m['unit'])) {
                    $ro[trim((string) $i->ro_kode)] = true;
                }
            }
            $roBelumDipetakan = count($ro);
        }

        return compact('userPerRole', 'uploadAktif', 'roBelumDipetakan');
    }

    /**
     * Item anggaran upload aktif, dikelompokkan induk => sub unit => item.
     */
    private function itemPerInduk(AnggaranUpload $upload): array
    {
        $resolver = app(AnggaranUnitResolver::class);

        $perInduk = [];
        foreach (AnggaranItem::where('anggaran_upload_id', $upload->id)->orderBy('id')->get() as $i) {
            $m = $resolver->resolve($i);
            $sub = $m['unit'] ?: 'Belum dipetakan';
            $induk = $m['induk'] ?: $sub;
            $perInduk[$induk][$sub][] = $i;
        }

        return $perInduk;
    }

    /**
     * Target serapan (persen) sampai bulan berjalan. Default linear: bulan ke-n dari 12.
     * Ganti isi method ini kalau ada target resmi (misalnya per triwulan).
     */
    private function targetSerapan(): float
    {
        return round(now()->month / 12 * 100, 2);
    }

    /**
     * Data dashboard Eselon II: KPI dan daftar RO, hanya untuk unit akun tersebut.
     * Angka diambil dari $serapan (sudah dibatasi sesuai unit akun oleh serapanUnit()),
     * jadi tidak perlu memuat data anggaran sekali lagi.
     */
    private function dataEselon2(array $serapan): array
    {
        $target = $this->targetSerapan();

        $hasil = [
            'target' => $target,
            'kpi' => null,
            'ro' => [],
        ];

        if (!$serapan['ada']) {
            return $hasil;
        }

        $hasil['kpi'] = [
            'pagu' => $serapan['pagu'],
            'realisasi' => $serapan['realisasi'],
            'persen' => $serapan['persen'],
            'sisa' => $serapan['sisa'],
            'outstanding' => $serapan['outstanding'],
            'sisa_efektif' => $serapan['sisa_efektif'],
            'status' => $this->statusSerapan((float) $serapan['persen'], $target),
        ];

        // Semua RO (kedalaman 2 di tabel bertingkat) yang punya pagu
        $hasil['ro'] = collect($serapan['tree'])
            ->where('depth', 2)
            ->filter(fn($t) => $t['pagu'] > 0)
            ->map(fn($t) => [
                'kode' => $t['kode'],
                'uraian' => $t['uraian'],
                'unit' => $t['unit'],
                'pagu' => $t['pagu'],
                'realisasi' => $t['sd'],
                'outstanding' => $t['out'] ?? 0,
                'sisa' => $t['sisa'],
                'sisa_efektif' => $t['efektif'] ?? $t['sisa'],
            ])
            ->values()
            ->all();

        return $hasil;
    }

    /** hijau: sesuai/di atas target, kuning: sampai 20% di bawah target, merah: lebih rendah. */
    private function statusSerapan(float $persen, float $target): string
    {
        if ($persen >= $target) {
            return 'hijau';
        }

        return $persen >= $target * 0.8 ? 'kuning' : 'merah';
    }

    /**
     * Serapan anggaran satu unit induk (D1, D2, dst.) beserta tabel bertingkat.
     * - TU Sekretariat, Admin, Super Admin: boleh memilih induk, tabel menampilkan semua unit kerja di dalamnya.
     * - Akun Asdep (unit kerja): hanya melihat Asdep-nya sendiri. Parameter ?unit= diabaikan untuk mereka.
     * - Akun tingkat Deputi/Biro (termasuk Eselon II): melihat seluruh induknya, tanpa bisa memilih.
     * Sisa = Pagu - Realisasi; Sisa Efektif = Sisa - Outstanding.
     *
     * @return array{0: array, 1: array} [$serapan, $opsiUnit]
     */
    private function serapanUnit(?string $unitDipilih): array
    {
        $u = auth()->user();
        $bisaPilih = (bool) ($u && $u->canApproveMemo());

        $kosong = fn($unit = '-', $ada = false) => [
            'unit' => $unit,
            'ada' => $ada,
            'bisa_pilih' => $bisaPilih,
            'persen' => 0,
            'pagu' => 0,
            'realisasi' => 0,
            'sisa' => 0,
            'outstanding' => 0,
            'sisa_efektif' => 0,
            'tree' => [],
        ];

        $upload = AnggaranUpload::where('is_active', true)->latest('id')->first();
        if (!$upload) {
            return [$kosong(), []];
        }

        // induk => sub unit => item
        $perInduk = $this->itemPerInduk($upload);

        if ($perInduk === []) {
            return [$kosong(), []];
        }

        ksort($perInduk);
        $semuaInduk = array_keys($perInduk);

        $norm = fn($s) => preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $s)));
        $kodeUnit = (string) (auth()->user()?->unit?->kode ?? '');
        $kodeNorm = $norm($kodeUnit);

        $unit = null;          // induk yang ditampilkan
        $subTerbatas = null;   // kalau diisi: hanya unit kerja ini yang ditampilkan

        if ($bisaPilih) {
            $opsi = $semuaInduk;
            $unit = in_array($unitDipilih, $opsi, true) ? $unitDipilih : null;
        } else {
            if ($kodeNorm !== '') {
                // 1) Cocok persis dengan nama unit kerja (mis. "Asdep 2 D2")
                foreach ($perInduk as $induk => $subs) {
                    foreach (array_keys($subs) as $sub) {
                        if ($norm($sub) === $kodeNorm) {
                            $unit = $induk;
                            $subTerbatas = $sub;
                            break 2;
                        }
                    }
                }
                // 2) Kode unit memuat nama unit kerja (bukan unit "langsung" milik induk)
                if (!$unit) {
                    foreach ($perInduk as $induk => $subs) {
                        foreach (array_keys($subs) as $sub) {
                            if ($sub !== $induk && str_contains($kodeNorm, $norm($sub))) {
                                $unit = $induk;
                                $subTerbatas = $sub;
                                break 2;
                            }
                        }
                    }
                }
                // 3) Akun tingkat Deputi/Biro (bukan Asdep): boleh melihat seluruh induknya
                if (!$unit && !str_contains($kodeNorm, 'asdep')) {
                    foreach ($semuaInduk as $o) {
                        if (preg_match('/\b' . preg_quote($norm($o), '/') . '\b/', $kodeNorm)) {
                            $unit = $o;
                            break;
                        }
                    }
                }
            }

            if (!$unit) {
                return [$kosong($kodeUnit ?: '-', false), []];
            }
            $opsi = [$unit];
        }

        // Unit awal yang bisa memilih: induk yang cocok dengan unit akun, kalau tidak ada yang pertama
        if (!$unit) {
            foreach ($semuaInduk as $o) {
                if ($kodeNorm !== '' && preg_match('/\b' . preg_quote($norm($o), '/') . '\b/', $kodeNorm)) {
                    $unit = $o;
                    break;
                }
            }
            $unit ??= $semuaInduk[0];
        }

        $outstandingMak = $this->outstandingPerKunci();

        $hitung = function (array $items) use ($outstandingMak) {
            $pagu = (int) collect($items)->sum('pagu');
            $realisasi = (int) collect($items)->sum('realisasi');
            $sisa = $pagu - $realisasi;

            $kunci = [];
            foreach ($items as $i) {
                $kunci[$this->kunciItem($i)] = true;
            }
            $outstanding = (int) collect(array_keys($kunci))->sum(fn($k) => $outstandingMak[$k] ?? 0);

            return [
                'pagu' => $pagu,
                'realisasi' => $realisasi,
                'persen' => $pagu > 0 ? round($realisasi / $pagu * 100, 2) : 0,
                'sisa' => $sisa,
                'outstanding' => $outstanding,
                'sisa_efektif' => $sisa - $outstanding,
            ];
        };

        // Semua unit kerja di induk, atau hanya satu kalau akun dibatasi ke Asdep-nya
        $subs = $perInduk[$unit];
        ksort($subs);
        if ($subTerbatas !== null) {
            $subs = [$subTerbatas => $subs[$subTerbatas]];
        }

        $pasangan = []; // [item, nama unit kerja]
        foreach ($subs as $sub => $items) {
            foreach ($items as $it) {
                $pasangan[] = [$it, $sub];
            }
        }
        $semuaItem = array_column($pasangan, 0);

        return [
            [
                'unit' => $subTerbatas ?? $unit,
                'ada' => true,
                'bisa_pilih' => $bisaPilih,
                'tree' => $this->bangunTree($pasangan, $outstandingMak),
            ] + $hitung($semuaItem),
            $opsi,
        ];
    }

    /**
     * Tabel bertingkat: Program > Kegiatan > RO > Komponen > Sub > Akun > Item.
     * Angka dijumlahkan ke semua induk. Outstanding dihitung di tingkat Akun lalu dijumlahkan ke atas.
     *
     * @param array<int, array{0: AnggaranItem, 1: string}> $pasangan
     */
    private function bangunTree(array $pasangan, array $outstandingMak): array
    {
        $n = [];     // id => node
        $peta = [];  // path => id
        $anak = [];  // id induk ('r' = akar) => [id anak]

        foreach ($pasangan as [$i, $sub]) {
            [$pk, $pu] = $this->programItem($i);
            $komp = trim((string) $i->komp_kode);
            if (ctype_digit($komp) && strlen($komp) < 3) {
                $komp = str_pad($komp, 3, '0', STR_PAD_LEFT);
            }

            // [kode tampil, uraian]
            $level = [
                [$pk, $pu],
                [trim((string) $i->keg_kode), (string) $i->keg_uraian],
                [trim((string) $i->ro_kode), (string) $i->ro_uraian],
                [$komp, (string) $i->komp_uraian],
                [trim((string) $i->sub_kode), (string) $i->sub_uraian],
                [trim((string) $i->akun_kode), (string) $i->akun_uraian],
                [trim((string) $i->item_kode), (string) $i->item_uraian],
            ];
            $akunKey = $this->kunciItem($i);

            $path = '';
            $induk = null;
            foreach ($level as $d => [$kode, $uraian]) {
                // Item selalu satu baris sendiri, level lain digabung per kode
                $path .= '|' . ($d === 6 ? '#' . spl_object_id($i) : $kode);

                if (!isset($peta[$path])) {
                    $id = count($n) + 1;
                    $peta[$path] = $id;
                    $n[$id] = [
                        'id' => $id,
                        'induk' => $induk,
                        'kode' => $kode,
                        'uraian' => $uraian,
                        'pagu' => 0,
                        'lalu' => 0,
                        'ini' => 0,
                        'sd' => 0,
                        'out' => 0,
                        'subs' => [],
                        'akun' => $d === 5 ? $akunKey : null,
                        'item' => $d === 6,
                    ];
                    $anak[$induk ?? 'r'][] = $id;
                }

                $id = $peta[$path];
                $n[$id]['pagu'] += (int) $i->pagu;
                $n[$id]['lalu'] += (int) $i->realisasi_lalu;
                $n[$id]['ini'] += (int) $i->realisasi_ini;
                $n[$id]['sd'] += (int) $i->realisasi;
                $n[$id]['subs'][$sub] = true;
                $induk = $id;
            }
        }

        // Outstanding: dari tingkat Akun, dijumlahkan ke semua induknya
        foreach ($n as $x) {
            $o = $x['akun'] !== null ? ($outstandingMak[$x['akun']] ?? 0) : 0;
            for ($p = $o > 0 ? $x['id'] : null; $p !== null; $p = $n[$p]['induk']) {
                $n[$p]['out'] += $o;
            }
        }

        // Ratakan berurutan (induk lalu turunannya)
        $baris = [];
        $jalan = function ($id, $anc) use (&$jalan, &$n, &$anak, &$baris) {
            $x = $n[$id];
            $sisa = $x['pagu'] - $x['sd'];
            $subs = array_keys($x['subs']);

            $baris[] = [
                'id' => $id,
                'anc' => $anc,
                'depth' => count($anc),
                'kode' => $x['kode'],
                'uraian' => $x['uraian'],
                'unit' => count($subs) === 1 ? $subs[0] : count($subs) . ' unit',
                'unit_tunggal' => count($subs) === 1,
                'pagu' => $x['pagu'],
                'lalu' => $x['lalu'],
                'ini' => $x['ini'],
                'sd' => $x['sd'],
                'persen' => $x['pagu'] > 0 ? round($x['sd'] / $x['pagu'] * 100, 2) : 0,
                'sisa' => $sisa,
                'out' => $x['item'] ? null : $x['out'],
                'efektif' => $x['item'] ? null : $sisa - $x['out'],
                'anak' => !empty($anak[$id]),
            ];

            foreach ($anak[$id] ?? [] as $c) {
                $jalan($c, array_merge($anc, [$id]));
            }
        };
        foreach ($anak['r'] ?? [] as $c) {
            $jalan($c, []);
        }

        return $baris;
    }

    /** Kunci MAK item. Harus sama dengan makKey() di halaman Anggaran. */
    private function kunciItem($i): string
    {
        $last = fn($s) => array_slice(explode('.', (string) $s), -1)[0];
        $komp = trim((string) $i->komp_kode);
        if (ctype_digit($komp) && strlen($komp) < 3) {
            $komp = str_pad($komp, 3, '0', STR_PAD_LEFT);
        }

        return implode('.', [$last($i->keg_kode), $i->ro_kode, $komp, $last($i->sub_kode), $i->akun_kode]);
    }

    /** Program diturunkan dari kode kegiatan (CL.7458 -> CL), sama seperti halaman Anggaran. */
    private function programItem($i): array
    {
        $kode = trim((string) ($i->prog_kode ?? ''));
        if ($kode === '') {
            $k = trim((string) $i->keg_kode);
            $kode = str_contains($k, '.') ? explode('.', $k)[0] : 'PROG';
        }
        $uraian = trim((string) ($i->prog_uraian ?? ''));
        if ($uraian === '') {
            $uraian = ['CL' => 'Program Koordinasi Pelaksanaan Kebijakan', 'WA' => 'Program Dukungan Manajemen'][$kode]
                ?? ($kode === 'PROG' ? 'Program Utama' : 'Program ' . $kode);
        }

        return [$kode, $uraian];
    }

    /** Sama dengan AnggaranController::kunciMak(): 6 bagian terakhir kode MAK, komponen di-pad 3 digit. */
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
            if ((int) $total <= 0) {
                continue;
            }
            $k = $this->kunciMak((string) $mak);
            $hasil[$k] = ($hasil[$k] ?? 0) + (int) $total;
        }

        return $hasil;
    }

    /** Nama lengkap tiap tipe pembayaran (kode = key di MemoEntry::TIPE_PEMBAYARAN). */
    private const NAMA_TIPE = [
        'ls' => 'Pembayaran Langsung',
        'up' => 'Uang Persediaan',
        'gup' => 'Ganti Uang Persediaan',
        'tup' => 'Tambahan Uang Persediaan',
        'ptup' => 'Pertanggungjawaban TUP',
    ];

    /**
     * Dana yang diajukan pada bulan & tahun tertentu, dipecah per tipe pembayaran (LS, UP, GUP, TUP, PTUP).
     * - Perdin dari Agenda (nomor memo sudah diisi). Agenda tidak punya tipe sendiri, dianggap LS
     *   kecuali kolom tipe_pembayaran di agenda terisi.
     * - Semua jenis memo mandiri dari MemoEntry, dikelompokkan per tipe_pembayaran (kosong = LS).
     *
     * @return array{tipe: array<int, array{key: string, kode: string, nama: string, nominal: int}>, total: int}
     */
    private function danaBulan(int $bulan, int $tahun): array
    {
        $perTipe = [];

        $agendas = Agenda::whereMonth('tanggal_mulai', $bulan)
            ->whereYear('tanggal_mulai', $tahun)
            ->get();

        foreach ($agendas as $agenda) {
            $total = 0;

            if (!empty($agenda->nomor_memo_pns)) {
                $total += $agenda->biaya_asn;
            }

            if (!empty($agenda->nomor_memo_non_pns)) {
                $total += $agenda->biaya_non_asn;
            }

            if ($total > 0) {
                $k = MemoEntry::normalisasiTipe($agenda->tipe_pembayaran ?? '') ?: 'ls';
                $perTipe[$k] = ($perTipe[$k] ?? 0) + (int) $total;
            }
        }

        $entries = MemoEntry::selectRaw('tipe_pembayaran, SUM(nominal) as total')
            ->whereMonth('tanggal_memo', $bulan)
            ->whereYear('tanggal_memo', $tahun)
            ->groupBy('tipe_pembayaran')
            ->pluck('total', 'tipe_pembayaran');

        foreach ($entries as $tipe => $nominal) {
            $k = MemoEntry::normalisasiTipe((string) $tipe) ?: 'ls';
            $perTipe[$k] = ($perTipe[$k] ?? 0) + (int) $nominal;
        }

        // Urutan tetap (LS, UP, GUP, TUP, PTUP); tipe yang diketik manual ditaruh di bawahnya
        $urutan = array_keys(MemoEntry::TIPE_PEMBAYARAN);
        $tipe = [];
        foreach ($urutan as $k) {
            $tipe[] = [
                'key' => $k,
                'kode' => strtoupper($k),
                'nama' => self::NAMA_TIPE[$k] ?? '',
                'nominal' => (int) ($perTipe[$k] ?? 0),
            ];
        }
        foreach ($perTipe as $k => $nominal) {
            if (!in_array($k, $urutan, true)) {
                $tipe[] = [
                    'key' => (string) $k,
                    'kode' => mb_strimwidth((string) $k, 0, 12, '…'),
                    'nama' => 'Tipe lain',
                    'nominal' => (int) $nominal,
                ];
            }
        }

        return [
            'tipe' => $tipe,
            'total' => array_sum(array_column($tipe, 'nominal')),
        ];
    }

    private function dataKalender(Request $request): array
    {
        $now = now();
        $bulanParam = (string) $request->query('bulan', '');

        // Validasi format YYYY-MM; kalau salah/kosong, pakai bulan berjalan
        $bulan = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulanParam)
            ? Carbon::createFromFormat('!Y-m', $bulanParam)
            : $now->copy()->startOfMonth();

        $startOfMonth = $bulan->copy()->startOfMonth();
        $endOfMonth = $bulan->copy()->endOfMonth();

        // Agenda yang periodenya overlap dengan bulan ini
        $agendaList = Agenda::where('tanggal_mulai', '<=', $endOfMonth)
            ->where('tanggal_selesai', '>=', $startOfMonth)
            ->with('pegawai')
            ->orderBy('tanggal_mulai')
            ->get();

        // marks[tanggal] = [ {id, uraian, lokasi, pegawai[], periode}, ... ]
        $marks = [];
        foreach ($agendaList as $agenda) {
            $periodeMulai = $agenda->tanggal_mulai->greaterThan($startOfMonth)
                ? $agenda->tanggal_mulai
                : $startOfMonth;
            $periodeSelesai = $agenda->tanggal_selesai->lessThan($endOfMonth)
                ? $agenda->tanggal_selesai
                : $endOfMonth;

            $item = [
                'id' => $agenda->id,
                'uraian' => $agenda->uraian_kegiatan,
                'lokasi' => collect([$agenda->kota, $agenda->provinsi])->filter()->implode(', ')
                    ?: $agenda->tujuan,
                'pegawai' => $agenda->pegawai->pluck('nama')->values()->all(),
                'periode' => $agenda->tanggal_mulai->translatedFormat('d M')
                    . ($agenda->tanggal_mulai->isSameDay($agenda->tanggal_selesai)
                        ? ''
                        : ' – ' . $agenda->tanggal_selesai->translatedFormat('d M Y')),
            ];

            for ($d = $periodeMulai->copy(); $d->lte($periodeSelesai); $d->addDay()) {
                $marks[$d->day][] = $item;
            }
        }

        $isBulanIni = $startOfMonth->isSameMonth($now);

        return [
            'value' => $startOfMonth->format('Y-m'),
            'tahun' => $startOfMonth->year,
            'bulan' => $startOfMonth->month,
            'namaBulan' => $startOfMonth->translatedFormat('F Y'),
            'offset' => $startOfMonth->dayOfWeekIso - 1, // Senin = 0
            'jumlahHari' => $startOfMonth->daysInMonth,
            'hariIni' => $isBulanIni ? $now->day : null,
            'isBulanIni' => $isBulanIni,
            'prev' => $startOfMonth->copy()->subMonth()->format('Y-m'),
            'next' => $startOfMonth->copy()->addMonth()->format('Y-m'),
            'marks' => $marks,
        ];
    }
}