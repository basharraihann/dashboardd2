<x-app-layout title="Monitoring Memo">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Monitoring Memo & Anggaran') }}
        </h2>
    </x-slot>

    @php
        $rp = fn($n) => ($n < 0 ? '-Rp ' : 'Rp ') . number_format(abs((int) $n), 0, ',', '.');

        // Kelas dasar header kolom tabel
        $th = 'px-2 py-2.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500 bg-gray-50 border-b border-gray-200';

        $statusLabel = ['outstanding' => 'Outstanding', 'sakti' => 'SAKTI', 'sp2d' => 'SP2D', 'batal' => 'Batal'];
        $statusBadge = fn($s) => match ($s) {
            'sakti' => 'bg-blue-50 text-blue-700 border-blue-200',
            'sp2d' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'batal' => 'bg-gray-100 text-gray-500 border-gray-200',
            default => 'bg-amber-50 text-amber-700 border-amber-200',
        };
        $statusDot = fn($s) => match ($s) {
            'sakti' => 'bg-blue-500',
            'sp2d' => 'bg-emerald-500',
            'batal' => 'bg-gray-400',
            default => 'bg-amber-500',
        };

        $bisaKelola = auth()->user()?->can('kelola-anggaran');

        // Tahap alur memo (dari tabel memo_progres)
        $tahapLabel = [
            'draft' => 'Draft, belum dikirim',
            'dikirim' => 'Menunggu K2',
            'lolos_spk' => 'Lolos K2, menunggu input SAKTI',
            'sakti' => 'Menunggu K3',
            'otp_ppk' => 'OTP PPK',
            'ppspm' => 'PPSPM, menunggu pencairan',
            'dikembalikan' => 'Dikembalikan ke unit',
        ];
        $tahapDot = fn($t) => match ($t) {
            'dikirim' => 'bg-blue-500',
            'lolos_spk' => 'bg-indigo-500',
            'sakti' => 'bg-sky-500',
            'otp_ppk' => 'bg-violet-500',
            'ppspm' => 'bg-emerald-500',
            'dikembalikan' => 'bg-red-500',
            default => 'bg-gray-400',
        };

        // Berkas fisik (tampilan saja): posisi berkas menurut tahap memo.
        // Tim Keuangan = sedang di keuangan, PPSPM = di tahap PPSPM, Unit = masih/kembali di unit kerja.
        $berkasFisik = fn($t) => match ($t) {
            'dikirim', 'lolos_spk', 'sakti', 'otp_ppk' => ['Tim Keuangan', 'bg-blue-50 text-blue-700 border-blue-200'],
            'ppspm' => ['PPSPM', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            default => ['Unit', 'bg-gray-50 text-gray-700 border-gray-200'],
        };

        $antreanSp2d = $antreanSp2d ?? collect();
        $unitRingkasan = $unitRingkasan ?? collect();
        $rankStatus = ['outstanding' => 0, 'sakti' => 1, 'sp2d' => 2];

        // Riwayat lengkap per memo datang dari controller ($logPerMemo), jangan dibuat ulang di sini.
        $logPerMemo = $logPerMemo ?? collect();

        // Gabungkan semua memo, lalu kelompokkan per unit untuk panel "Show"
        $semuaMemo = collect();
        foreach ($antreanOutstanding as $m) {
            $semuaMemo->push($m + ['_status' => 'outstanding']);
        }
        foreach ($antreanSakti as $m) {
            $semuaMemo->push($m + ['_status' => 'sakti']);
        }
        foreach ($antreanSp2d as $m) {
            $semuaMemo->push($m + ['_status' => 'sp2d']);
        }
        $memoPerUnit = $semuaMemo
            ->groupBy(fn($m) => $m['unit'] ?: '__none')
            ->map(fn($g) => $g->sort(fn($a, $b) => [$rankStatus[$a['_status']], -($a['umur'] ?? 0)] <=> [$rankStatus[$b['_status']], -($b['umur'] ?? 0)])->values());
    @endphp

    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-[96rem] mx-auto space-y-5">

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- ============ Progres per unit ============ --}}
        @if ($unitRingkasan->isNotEmpty())
            <section aria-labelledby="judul-unit" class="bg-white shadow-sm rounded-2xl border border-gray-100">
                <div class="px-4 pt-4 sm:px-6 sm:pt-6 pb-4">
                    <h3 id="judul-unit" class="text-lg font-bold text-gray-800">Progres per unit</h3>
                    <p class="mt-0.5 text-sm text-gray-500 max-w-3xl">
                        Klik Show untuk daftar memo unit. SP2D bulan ini saja.
                    </p>

                    {{-- Legenda dua baris. Labelnya sama dengan judul kolom tabel di bawah. --}}
                    <div class="mt-3 space-y-1.5 text-xs text-gray-500">
                        {{-- Status memo --}}
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5">
                            <span
                                class="w-full sm:w-28 shrink-0 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Status
                                memo</span>
                            <span class="inline-flex items-center gap-1.5"><span
                                    class="h-2 w-2 rounded-full bg-amber-500"></span><b
                                    class="font-semibold text-gray-700">Outstanding</b> belum diinput di SAKTI</span>
                            <span class="inline-flex items-center gap-1.5"><span
                                    class="h-2 w-2 rounded-full bg-blue-500"></span><b
                                    class="font-semibold text-gray-700">SAKTI</b> sudah diinput, uang belum cair</span>
                            <span class="inline-flex items-center gap-1.5"><span
                                    class="h-2 w-2 rounded-full bg-emerald-500"></span><b
                                    class="font-semibold text-gray-700">SP2D</b> uang sudah cair</span>
                        </div>

                        {{-- Perlu tindakan --}}
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5">
                            <span
                                class="w-full sm:w-28 shrink-0 text-[10px] font-semibold uppercase tracking-wide text-gray-400">Perlu
                                tindakan</span>
                            <span class="inline-flex items-center gap-1.5"><b
                                    class="font-semibold text-red-600">merah</b> pengajuan menunggu keputusan</span>
                            <span class="inline-flex items-center gap-1.5"><b
                                    class="font-semibold text-blue-600">biru</b> memo perlu ditindaklanjuti</span>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 rounded-b-2xl overflow-hidden">
                    {{-- Header kolom --}}
                    <div
                        class="pu-grid pu-head px-4 sm:px-6 py-2.5 bg-gray-50 border-b border-gray-100 text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                        <span>Unit</span>
                        <span>Memo</span>
                        <span>Status memo</span>
                        <span>Perlu tindakan</span>
                        <span class="text-right">Nominal Outstanding</span>
                        <span class="text-right">Detail</span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach ($unitRingkasan as $u)
                            @php
                                $daftar = $memoPerUnit->get($u['unit'], collect());
                                $jmlPengajuan = $daftar->filter(fn($m) => !empty($m['pengajuan']))->count();
                                $jmlTindakan = $daftar->filter(
                                    fn($m) => in_array($m['tahap'] ?? 'draft', ['dikirim', 'lolos_spk', 'sakti', 'otp_ppk', 'ppspm'], true)
                                        && ($m['_status'] ?? '') !== 'sp2d'
                                )->count();
                                $jo = (int) $u['outstanding_jumlah'];
                                $js = (int) $u['sakti_jumlah'];
                                $jp = (int) $u['sp2d_jumlah'];
                            @endphp
                            <div>
                                <div class="pu-grid px-4 sm:px-6 py-3.5 hover:bg-gray-50 transition">
                                    {{-- Unit --}}
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 break-words">{{ $u['label'] }}</p>
                                        @if ($u['lama'] > 0)
                                            <p class="mt-0.5 text-[11px] font-medium text-red-600">{{ $u['lama'] }} memo &gt; 30
                                                hari</p>
                                        @endif
                                    </div>

                                    {{-- Total memo --}}
                                    <div class="text-base font-semibold tabular-nums text-gray-900">
                                        {{ $u['jumlah'] }}
                                        <span class="ml-1 text-xs font-normal text-gray-400 md:hidden">memo</span>
                                    </div>

                                    {{-- Status: hanya yang ada isinya --}}
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-700 tabular-nums"
                                        role="img" aria-label="{{ $jo }} outstanding, {{ $js }} SAKTI, {{ $jp }} SP2D">
                                        @if ($jo > 0)
                                            <span class="inline-flex items-center gap-1.5"><span
                                                    class="h-2 w-2 rounded-full bg-amber-500"></span>{{ $jo }}
                                                Outstanding</span>
                                        @endif
                                        @if ($js > 0)
                                            <span class="inline-flex items-center gap-1.5"><span
                                                    class="h-2 w-2 rounded-full bg-blue-500"></span>{{ $js }}
                                                SAKTI</span>
                                        @endif
                                        @if ($jp > 0)
                                            <span class="inline-flex items-center gap-1.5"><span
                                                    class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ $jp }}
                                                SP2D</span>
                                        @endif
                                        @if ($jo + $js + $jp === 0)
                                            <span class="text-gray-300">–</span>
                                        @endif
                                    </div>

                                    {{-- Perlu tindakan --}}
                                    <div class="text-xs font-semibold tabular-nums">
                                        @if ($jmlPengajuan > 0)
                                            <span class="text-red-600">{{ $jmlPengajuan }} pengajuan</span>
                                        @elseif ($jmlTindakan > 0)
                                            <span class="text-blue-600">{{ $jmlTindakan }} perlu tindakan</span>
                                        @else
                                            <span class="font-normal text-gray-300">–</span>
                                        @endif
                                    </div>

                                    {{-- Nominal outstanding --}}
                                    <div
                                        class="md:text-right text-xs font-semibold tabular-nums whitespace-nowrap {{ $u['outstanding_total'] > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                        {{ $rp($u['outstanding_total']) }}
                                    </div>

                                    {{-- Detail --}}
                                    <div class="md:text-right">
                                        <button type="button" aria-expanded="false" data-target="unit-detail-{{ $loop->index }}"
                                            class="js-unit-toggle rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-gray-700 hover:bg-gray-100 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                            <span class="js-label">Show</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Detail memo unit (tersembunyi sampai Show diklik) --}}
                                <div id="unit-detail-{{ $loop->index }}"
                                    class="hidden border-t border-gray-200 bg-gray-50 px-4 py-4 sm:px-6">
                                    @if ($daftar->isEmpty())
                                        <p class="text-xs text-gray-500">Belum ada memo.</p>
                                    @else
                                        <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                                            <table class="w-full table-fixed text-sm">
                                                <colgroup>
                                                    <col style="width:13%"> {{-- Memo --}}
                                                    <col style="width:10%"> {{-- Status --}}
                                                    <col style="width:10%"> {{-- Jenis --}}
                                                    <col style="width:12%"> {{-- Kegiatan --}}
                                                    <col style="width:11%"> {{-- MAK --}}
                                                    <col style="width:10%"> {{-- Nominal --}}
                                                    <col style="width:16%"> {{-- Progres --}}
                                                    <col style="width:11%"> {{-- Berkas Fisik --}}
                                                    <col style="width:7%"> {{-- Log --}}
                                                </colgroup>
                                                <thead>
                                                    <tr class="text-left">
                                                        <th class="{{ $th }} pl-4">Memo</th>
                                                        <th class="{{ $th }}">Status</th>
                                                        <th class="{{ $th }}">Jenis</th>
                                                        <th class="{{ $th }}">Kegiatan</th>
                                                        <th class="{{ $th }}">MAK</th>
                                                        <th class="{{ $th }} text-right">Nominal</th>
                                                        <th class="{{ $th }}">Progres</th>
                                                        <th class="{{ $th }}">Berkas Fisik</th>
                                                        <th class="{{ $th }} pr-4 text-right">Log</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @foreach ($daftar as $m)
                                                        @php
                                                            $st = $m['_status'];
                                                            $tahap = $m['tahap'] ?? 'draft';
                                                            $pengajuan = $m['pengajuan'] ?? null;
                                                            $logs = $logPerMemo->get($m['nomor_memo'], collect());
                                                            $logId = 'log-' . $loop->parent->index . '-' . $loop->index;
                                                        @endphp
                                                        <tr class="hover:bg-blue-50/30 transition">
                                                            <td class="pl-4 pr-2 py-2.5 align-top">
                                                                <p class="text-xs font-semibold text-gray-900 tabular-nums break-all">
                                                                    {{ $m['nomor_memo'] }}
                                                                </p>
                                                                @if (!empty($m['label']))
                                                                    <span
                                                                        class="mt-1 inline-flex px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ $m['label'] === 'PNS' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-orange-50 text-orange-700 border-orange-200' }}">{{ $m['label'] }}</span>
                                                                @endif
                                                            </td>
                                                            <td class="px-2 py-2.5 align-top">
                                                                <span
                                                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md border text-[11px] font-semibold {{ $statusBadge($st) }}">
                                                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDot($st) }}"></span>
                                                                    {{ $statusLabel[$st] }}
                                                                </span>
                                                            </td>
                                                            <td class="px-2 py-2.5 align-top">
                                                                <span
                                                                    class="inline-flex whitespace-nowrap px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ ($m['jenis'] ?? null) === 'perdin' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-gray-50 text-gray-700 border-gray-200' }}">{{ $m['jenis_label'] ?? '-' }}</span>
                                                            </td>

                                                            {{-- Kegiatan: sempit, bisa dibuka selengkapnya --}}
                                                            <td class="px-2 py-2.5 align-top">
                                                                <p class="js-kegiatan text-xs text-gray-800 truncate"
                                                                    title="{{ $m['uraian'] ?? '' }}">
                                                                    {{ $m['uraian'] ?? '-' }}
                                                                </p>
                                                                <button type="button"
                                                                    class="js-kegiatan-toggle mt-0.5 hidden text-[11px] font-semibold text-blue-700 hover:text-blue-900 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded">
                                                                    Selengkapnya
                                                                </button>
                                                                @if (!empty($m['pic']))
                                                                    <p class="mt-0.5 text-[11px] text-gray-500">
                                                                        {{ $m['pic'] }}
                                                                    </p>
                                                                @endif
                                                            </td>

                                                            {{-- MAK: boleh turun baris, tidak melebarkan tabel --}}
                                                            <td class="px-2 py-2.5 align-top">
                                                                {{-- Turun baris hanya di titik antar segmen (7457.AAG.E01.051.0A.521211), bukan di tengah angka --}}
                                                                <p class="font-mono text-[11px] text-gray-700 break-words">
                                                                    {!! str_replace('.', '.<wbr>', e($m['mak'] ?: '-')) !!}
                                                                </p>
                                                            </td>
                                                            <td
                                                                class="px-2 py-2.5 align-top text-right text-xs font-semibold text-gray-800 tabular-nums">
                                                                {{ $rp($m['nominal']) }}
                                                            </td>

                                                            {{-- Progres + aksi --}}
                                                            <td class="px-2 py-2.5 align-top">
                                                                <p
                                                                    class="inline-flex items-start gap-1.5 text-[11px] font-medium leading-snug {{ $tahap === 'dikembalikan' ? 'text-red-600' : 'text-gray-600' }}">
                                                                    <span
                                                                        class="mt-[5px] h-1.5 w-1.5 shrink-0 rounded-full {{ $tahapDot($tahap) }}"></span>
                                                                    <span>{{ $tahapLabel[$tahap] ?? $tahap }}</span>
                                                                </p>

                                                                {{-- Pengajuan pengembalian yang menunggu keputusan --}}
                                                                @if ($pengajuan)
                                                                    <div
                                                                        class="mt-2 rounded-md border border-red-200 bg-red-50 px-2.5 py-1.5 text-[11px] leading-snug text-red-700 break-words">
                                                                        <span class="font-semibold">Pengajuan pengembalian menunggu
                                                                            keputusan:</span>
                                                                        {{ $pengajuan['alasan'] }}
                                                                    </div>
                                                                @endif

                                                                @if ($bisaKelola)
                                                                    {{-- Keputusan pengajuan --}}
                                                                    @if ($pengajuan)
                                                                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                                            <x-aksi-button varian="hijau" :url="route('memo.pengajuan.putusan', $pengajuan['id'])" :fields="['setuju' => 1]"
                                                                                :konfirmasi="'Setujui pengajuan? Memo akan dikembalikan ke unit kerja' . (in_array($tahap, ['sakti', 'otp_ppk'], true) ? ' dan SAKTI dilepas' : '') . '.'">
                                                                                Setujui pengajuan
                                                                            </x-aksi-button>
                                                                            <x-aksi-button varian="merah" :url="route('memo.pengajuan.putusan', $pengajuan['id'])" :fields="['setuju' => 0]" prompt="catatan"
                                                                                :wajib="true" prompt-label="Catatan penolakan (wajib diisi):">
                                                                                Tolak pengajuan
                                                                            </x-aksi-button>
                                                                        </div>
                                                                    @endif

                                                                    {{-- Aksi tahap (tetap tampil, juga saat ada pengajuan) --}}
                                                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                                                        @if ($tahap === 'dikirim' || $tahap === 'sakti')
                                                                            @php $gerbang = $tahap === 'dikirim' ? 'K2' : 'K3'; @endphp
                                                                            <x-aksi-button varian="hijau" :url="route('memo.verifikasi', $m['id'])" :fields="['gerbang' => $gerbang, 'lolos' => 1]"
                                                                                :konfirmasi="'Verifikasi ' . $gerbang . ': lengkap dan sesuai?'">
                                                                                {{ $gerbang }} lolos
                                                                            </x-aksi-button>
                                                                            <x-aksi-button varian="merah" :url="route('memo.verifikasi', $m['id'])" :fields="['gerbang' => $gerbang, 'lolos' => 0]"
                                                                                prompt="alasan" :wajib="true" :prompt-label="'Alasan tidak lolos ' . $gerbang . ' (wajib diisi). Memo akan dikembalikan ke unit kerja:'">
                                                                                {{ $gerbang }} tidak lolos
                                                                            </x-aksi-button>
                                                                        @endif

                                                                        @if ($tahap === 'lolos_spk')
                                                                            <x-aksi-button varian="biru" :url="route('memo.input-sakti', $m['id'])" :konfirmasi="'Catat memo ' . $m['nomor_memo'] . ' sudah diinput di SAKTI?'">
                                                                                Input SAKTI
                                                                            </x-aksi-button>
                                                                        @endif

                                                                        @if ($tahap === 'otp_ppk')
                                                                            <x-aksi-button varian="biru" :url="route('memo.selesai-otp', $m['id'])"
                                                                                konfirmasi="OTP PPK selesai? Setelah ini memo tidak bisa dikembalikan lagi.">
                                                                                Selesai OTP
                                                                            </x-aksi-button>
                                                                        @endif

                                                                        @if ($tahap === 'ppspm' && $st === 'sakti')
                                                                            <form method="POST" action="{{ route('monitoring.status-massal') }}"
                                                                                class="js-aksi-form m-0 flex flex-wrap items-center gap-1.5"
                                                                                data-konfirmasi="Tandai memo {{ $m['nomor_memo'] }} sudah cair (SP2D)?">
                                                                                @csrf
                                                                                <input type="hidden" name="status" value="sp2d">
                                                                                <input type="hidden" name="ids[]" value="{{ $m['id'] }}">
                                                                                <input type="date" name="tanggal"
                                                                                    value="{{ now()->toDateString() }}" title="Tanggal cair"
                                                                                    aria-label="Tanggal cair"
                                                                                    class="w-full min-w-0 border border-gray-200 rounded-md px-1.5 py-1 text-[11px] text-gray-600 focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none">
                                                                                <button type="submit"
                                                                                    class="btn-utama inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300">
                                                                                    Tandai cair (SP2D)
                                                                                </button>
                                                                            </form>
                                                                        @endif
                                                                    </div>
                                                                @endif

                                                                @if ($st === 'sp2d')
                                                                    <p class="mt-2 text-[11px] font-semibold text-emerald-700">
                                                                        Selesai
                                                                        @if (!empty($m['tgl_sp2d']))
                                                                            <span class="font-normal text-gray-500 tabular-nums">&middot;
                                                                                {{ $m['tgl_sp2d']->translatedFormat('d M Y') }}</span>
                                                                        @endif
                                                                    </p>
                                                                @endif
                                                            </td>

                                                            {{-- Berkas Fisik: hanya tampilan, belum terhubung ke data/aksi apa pun --}}
                                                            <td class="px-2 py-2.5 align-top">
                                                                @php
                                                                    $berkas = $berkasFisik($tahap);
                                                                @endphp
                                                                {{-- Pilihan saja: tanpa name/form, tidak ada yang dikirim atau disimpan --}}
                                                                <select aria-label="Berkas fisik memo {{ $m['nomor_memo'] }}"
                                                                    class="js-berkas w-full min-w-0 rounded-md border px-1.5 py-1 text-[11px] font-semibold cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 {{ $berkas[1] }}">
                                                                    @foreach (['Tim Keuangan', 'PPSPM', 'Unit'] as $opsiBerkas)
                                                                        <option value="{{ $opsiBerkas }}" @selected($opsiBerkas === $berkas[0])>{{ $opsiBerkas }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>

                                                            {{-- Log --}}
                                                            <td class="pl-2 pr-4 py-2.5 align-top text-right">
                                                                <button type="button" data-target="{{ $logId }}" aria-expanded="false"
                                                                    @disabled($logs->isEmpty())
                                                                    class="js-log-toggle rounded-md border border-gray-200 bg-white px-2 py-1 text-[11px] font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer disabled:opacity-40 disabled:cursor-default focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                                                    Log <span
                                                                        class="text-gray-400 tabular-nums">({{ $logs->count() }})</span>
                                                                </button>
                                                            </td>
                                                        </tr>

                                                        {{-- Riwayat memo (tersembunyi sampai Log diklik) --}}
                                                        <tr id="{{ $logId }}" class="hidden">
                                                            <td colspan="9" class="bg-white px-4 py-4 sm:px-6">
                                                                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3">
                                                                    <p
                                                                        class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                                                        Riwayat</p>
                                                                    <ul class="mt-3 space-y-3">
                                                                        @foreach ($logs as $log)
                                                                            <li class="flex items-start gap-3">
                                                                                <span
                                                                                    class="mt-2 h-2 w-2 shrink-0 rounded-full bg-gray-300"></span>
                                                                                <div class="min-w-0">
                                                                                    <p class="text-sm font-medium text-gray-800">
                                                                                        {{ $log['judul'] }}
                                                                                    </p>
                                                                                    @if (!empty($log['catatan']))
                                                                                        <p class="mt-0.5 text-xs text-gray-600 break-words">
                                                                                            {{ $log['catatan'] }}
                                                                                        </p>
                                                                                    @endif
                                                                                    <p class="mt-0.5 text-xs text-gray-500 tabular-nums">
                                                                                        {{ $log['waktu']->translatedFormat('d M Y, H:i') }}
                                                                                        @if (!empty($log['oleh']))
                                                                                            &middot; {{ $log['oleh'] }}
                                                                                        @endif
                                                                                    </p>
                                                                                </div>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @else
            {{-- Belum ada memo: tampilkan keterangan, jangan biarkan halaman kosong --}}
            <section class="bg-white shadow-sm rounded-2xl border border-gray-100 px-4 py-10 sm:px-6 text-center">
                <h3 class="text-base font-bold text-gray-800">Belum ada memo untuk dipantau</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Progres per unit muncul setelah ada memo yang dibuat di menu Memorandum.
                </p>
            </section>
        @endif

    </div>

    <script>
        (function () {
            // Show / Hide daftar memo per unit
            document.querySelectorAll('.js-unit-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const panel = document.getElementById(btn.dataset.target);
                    if (!panel) return;
                    const terbuka = panel.classList.toggle('hidden') === false;
                    btn.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
                    const label = btn.querySelector('.js-label');
                    if (label) label.textContent = terbuka ? 'Hide' : 'Show';

                    // Tombol "Selengkapnya" hanya perlu kalau teksnya benar-benar terpotong.
                    // Diukur saat panel terbuka, karena saat tersembunyi lebarnya 0.
                    if (terbuka) {
                        panel.querySelectorAll('.js-kegiatan').forEach(function (p) {
                            const t = p.parentElement.querySelector('.js-kegiatan-toggle');
                            if (!t) return;
                            const terpotong = p.classList.contains('truncate') && p.scrollWidth > p.clientWidth;
                            const sedangBuka = !p.classList.contains('truncate');
                            t.classList.toggle('hidden', !(terpotong || sedangBuka));
                        });
                    }
                });
            });

            // Kegiatan: Selengkapnya / Tutup
            document.querySelectorAll('.js-kegiatan-toggle').forEach(function (t) {
                t.addEventListener('click', function () {
                    const p = t.parentElement.querySelector('.js-kegiatan');
                    const ringkas = p.classList.toggle('truncate');
                    p.classList.toggle('whitespace-normal', !ringkas);
                    p.classList.toggle('break-words', !ringkas);
                    t.textContent = ringkas ? 'Selengkapnya' : 'Tutup';
                });
            });

            // Log: buka / tutup riwayat memo
            document.querySelectorAll('.js-log-toggle').forEach(function (b) {
                b.addEventListener('click', function () {
                    const row = document.getElementById(b.dataset.target);
                    if (!row) return;
                    const terbuka = row.classList.toggle('hidden') === false;
                    b.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
                    b.classList.toggle('bg-gray-100', terbuka);
                });
            });

            // Berkas Fisik: ganti warna sesuai pilihan (hanya tampilan)
            const warnaBerkas = {
                'Tim Keuangan': ['bg-blue-50', 'text-blue-700', 'border-blue-200'],
                'PPSPM': ['bg-emerald-50', 'text-emerald-700', 'border-emerald-200'],
                'Unit': ['bg-gray-50', 'text-gray-700', 'border-gray-200'],
            };
            const semuaWarna = [].concat.apply([], Object.values(warnaBerkas));
            document.querySelectorAll('.js-berkas').forEach(function (sel) {
                sel.addEventListener('change', function () {
                    sel.classList.remove.apply(sel.classList, semuaWarna);
                    sel.classList.add.apply(sel.classList, warnaBerkas[sel.value] || warnaBerkas['Unit']);
                });
            });

            // Form "Tandai cair": konfirmasi sebelum kirim
            document.querySelectorAll('.js-aksi-form').forEach(function (f) {
                f.addEventListener('submit', function (e) {
                    if (!window.confirm(f.dataset.konfirmasi || 'Ubah status memo ini?')) {
                        e.preventDefault();
                    }
                });
            });
        })();
    </script>

    <style>
        /* Tombol utama: gradasi biru yang sama dengan "Kirim ke Keuangan" di halaman memo */
        .btn-utama {
            background-image: linear-gradient(to bottom, #2563eb, #1d4ed8);
            background-color: #1d4ed8;
            border: 1px solid #1e40af;
            box-shadow: 0 1px 2px rgba(30, 64, 175, .35), inset 0 1px 0 rgba(255, 255, 255, .15);
            transition: background .15s, box-shadow .15s, transform .05s;
            cursor: pointer;
        }

        .btn-utama:hover {
            background-image: linear-gradient(to bottom, #1d4ed8, #1e40af);
            box-shadow: 0 2px 6px rgba(30, 64, 175, .4), inset 0 1px 0 rgba(255, 255, 255, .15);
        }

        .btn-utama:active {
            transform: translateY(1px);
        }

        /* ===== Progres per unit: baris unit dengan 6 kolom (di layar kecil menumpuk) ===== */
        .pu-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: .5rem;
        }

        @media (min-width: 768px) {
            .pu-grid {
                grid-template-columns: minmax(0, 2fr) 4rem minmax(0, 2.5fr) minmax(0, 1.6fr) minmax(0, 1.5fr) 5rem;
                gap: 1rem;
                align-items: center;
            }
        }

        .pu-head {
            display: none;
        }

        @media (min-width: 768px) {
            .pu-head {
                display: grid;
            }
        }

    </style>
</x-app-layout>