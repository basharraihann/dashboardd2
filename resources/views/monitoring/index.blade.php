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

        // Tahap alur memo (dari tabel memo_progres). 'sp2d' bukan tahap di tabel,
        // melainkan tampilan untuk memo yang statusnya sudah SP2D.
        $tahapLabel = [
            'draft' => 'Draft, belum dikirim',
            'dikirim' => 'Menunggu K2',
            'lolos_spk' => 'Lolos K2, menunggu input SAKTI',
            'sakti' => 'Menunggu K3',
            'otp_ppk' => 'OTP PPK',
            'ppspm' => 'PPSPM, menunggu pencairan',
            'sp2d' => 'SP2D, uang sudah cair',
            'dikembalikan' => 'Dikembalikan ke unit',
        ];
        $tahapDot = fn($t) => match ($t) {
            'dikirim' => 'bg-blue-500',
            'lolos_spk' => 'bg-indigo-500',
            'sakti' => 'bg-sky-500',
            'otp_ppk' => 'bg-violet-500',
            'ppspm' => 'bg-emerald-500',
            'sp2d' => 'bg-emerald-500',
            'dikembalikan' => 'bg-red-500',
            default => 'bg-gray-400',
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

        // Jenis dokumen (SPP/SPBy) + nomornya, satu query untuk semua memo (kunci = $m['id']).
        $sppPerMemo = \Illuminate\Support\Facades\DB::table('memo_progres')
            ->whereIn('memo_key', $semuaMemo->pluck('id')->filter()->all())
            ->get(['memo_key', 'jenis_dokumen', 'nomor_spp_spby'])
            ->keyBy('memo_key');

        // Posisi berkas fisik terakhir per memo. Tabel terpisah, tidak terkait tahap memo.
        $berkasPerMemo = \Illuminate\Support\Facades\DB::table('memo_berkas_fisik')
            ->whereIn('memo_key', $semuaMemo->pluck('id')->filter()->all())
            ->orderBy('id')->get()->groupBy('memo_key')
            ->map(fn($g) => $g->last()->posisi);

        // Kelas untuk bar filter
        $fl = 'block mb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500';
        $fi = 'rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none';
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

                {{-- Filter & pencarian --}}
                <div class="px-4 pb-4 sm:px-6">
                    <div id="filter-bar" class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3">
                        <div class="grow basis-64">
                            <label for="f-cari" class="{{ $fl }}">Cari</label>
                            <input id="f-cari" type="search" autocomplete="off"
                                placeholder="Nomor memo, kegiatan, MAK, PIC, nomor SPP/SPBy, unit"
                                class="w-full {{ $fi }}">
                        </div>
                        <div>
                            <label for="f-status" class="{{ $fl }}">Status</label>
                            <select id="f-status" class="{{ $fi }}">
                                <option value="">Semua</option>
                                @foreach ($statusLabel as $k => $v)
                                    @continue($k === 'batal')
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="f-tahap" class="{{ $fl }}">Progres</label>
                            <select id="f-tahap" class="{{ $fi }}">
                                <option value="">Semua</option>
                                @foreach ($tahapLabel as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="f-dok" class="{{ $fl }}">SPP/SPBy</label>
                            <select id="f-dok" class="{{ $fi }}">
                                <option value="">Semua</option>
                                <option value="SPP">SPP</option>
                                <option value="SPBy">SPBy</option>
                                <option value="kosong">Nomor belum diisi</option>
                            </select>
                        </div>
                        <label class="inline-flex items-center gap-1.5 pb-1.5 text-xs text-gray-700 cursor-pointer">
                            <input id="f-pengajuan" type="checkbox" class="rounded border-gray-300"> Ada pengajuan
                        </label>
                        <label class="inline-flex items-center gap-1.5 pb-1.5 text-xs text-gray-700 cursor-pointer">
                            <input id="f-lama" type="checkbox" class="rounded border-gray-300"> &gt; 30 hari
                        </label>
                        <button type="button" id="f-reset"
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">Reset</button>
                    </div>
                    <p id="f-info" class="mt-2 text-xs text-gray-500" aria-live="polite"></p>
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
                            <div class="js-unit">
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
                                        <button type="button" aria-expanded="false"
                                            data-key="{{ $u['unit'] ?: '__none' }}"
                                            data-target="unit-detail-{{ $loop->index }}"
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
                                                    <col style="width:15%"> {{-- Memo (+ jenis) --}}
                                                    <col style="width:10%"> {{-- Status --}}
                                                    <col style="width:17%"> {{-- Kegiatan --}}
                                                    <col style="width:11%"> {{-- MAK --}}
                                                    <col style="width:10%"> {{-- Nominal --}}
                                                    <col style="width:19%"> {{-- Progres (+ SPP/SPBy) --}}
                                                    <col style="width:11%"> {{-- Berkas Fisik --}}
                                                    <col style="width:7%"> {{-- Log --}}
                                                </colgroup>
                                                <thead>
                                                    <tr class="text-left">
                                                        <th class="{{ $th }} pl-4">Memo</th>
                                                        <th class="{{ $th }}">Status</th>
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
                                                            $spp = $sppPerMemo->get($m['id']);
                                                            $adaSpp = $spp && filled($spp->nomor_spp_spby);

                                                            // Memo yang statusnya sudah SP2D ditampilkan sebagai SP2D di kolom Progres,
                                                            // walau memo_progres.tahap masih 'ppspm'. Logika tombol tetap pakai $tahap.
                                                            $tahapTampil = $st === 'sp2d' ? 'sp2d' : $tahap;

                                                            // Untuk filter SPP/SPBy: jenis dokumen, atau 'kosong' bila sudah lewat SAKTI tapi nomor belum ada
                                                            $dok = $adaSpp
                                                                ? $spp->jenis_dokumen
                                                                : (in_array($tahapTampil, ['sakti', 'otp_ppk', 'ppspm', 'sp2d'], true) ? 'kosong' : '-');

                                                            // Teks pencarian (huruf kecil)
                                                            $cari = \Illuminate\Support\Str::lower(implode(' ', array_filter([
                                                                $m['nomor_memo'],
                                                                $m['uraian'] ?? '',
                                                                $m['mak'] ?? '',
                                                                $m['pic'] ?? '',
                                                                $m['jenis_label'] ?? '',
                                                                $spp->nomor_spp_spby ?? '',
                                                                $u['label'],
                                                            ])));
                                                        @endphp
                                                        <tr class="js-memo-row hover:bg-blue-50/30 transition"
                                                            data-cari="{{ $cari }}" data-status="{{ $st }}"
                                                            data-tahap="{{ $tahapTampil }}" data-dok="{{ $dok }}"
                                                            data-pengajuan="{{ $pengajuan ? 1 : 0 }}"
                                                            data-umur="{{ (int) ($m['umur'] ?? 0) }}"
                                                            data-log="{{ $logId }}">
                                                            <td class="pl-4 pr-2 py-2.5 align-top">
                                                                <p class="text-xs font-semibold text-gray-900 tabular-nums break-words">
                                                                    {!! str_replace(['.', '/'], ['.<wbr>', '/<wbr>'], e($m['nomor_memo'])) !!}
                                                                </p>
                                                                @if (!empty($m['label']))
                                                                    <span
                                                                        class="mt-1 inline-flex px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ $m['label'] === 'PNS' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-orange-50 text-orange-700 border-orange-200' }}">{{ $m['label'] }}</span>
                                                                @endif
                                                                <div class="mt-1">
                                                                    <span
                                                                        class="inline-flex whitespace-nowrap px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ ($m['jenis'] ?? null) === 'perdin' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-gray-50 text-gray-700 border-gray-200' }}">{{ $m['jenis_label'] ?? '-' }}</span>
                                                                </div>
                                                            </td>
                                                            <td class="px-2 py-2.5 align-top">
                                                                <span
                                                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md border text-[11px] font-semibold {{ $statusBadge($st) }}">
                                                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDot($st) }}"></span>
                                                                    {{ $statusLabel[$st] }}
                                                                </span>
                                                            </td>
                                                            {{-- Kegiatan: sempit, bisa dibuka selengkapnya --}}
                                                            <td class="px-2 py-2.5 align-top">
                                                                <p class="js-kegiatan kegiatan-clamp text-xs text-gray-800 break-words"
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
                                                                    class="inline-flex items-start gap-1.5 text-[11px] font-medium leading-snug {{ $tahapTampil === 'dikembalikan' ? 'text-red-600' : 'text-gray-600' }}">
                                                                    <span
                                                                        class="mt-[5px] h-1.5 w-1.5 shrink-0 rounded-full {{ $tahapDot($tahapTampil) }}"></span>
                                                                    <span>{{ $tahapLabel[$tahapTampil] ?? $tahapTampil }}</span>
                                                                </p>

                                                                {{-- SPP / SPBy yang diinput saat Input SAKTI --}}
                                                                @if ($adaSpp)
                                                                    <p class="mt-1.5 flex items-start gap-1.5 text-[11px] leading-snug">
                                                                        <span
                                                                            class="shrink-0 rounded border px-1.5 py-px text-[10px] font-semibold {{ ($spp->jenis_dokumen ?? '') === 'SPBy' ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-sky-50 text-sky-700 border-sky-200' }}">{{ $spp->jenis_dokumen ?? '-' }}</span>
                                                                        <span class="font-mono text-gray-700 break-all">{{ $spp->nomor_spp_spby }}</span>
                                                                    </p>
                                                                @endif

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

                                                                        {{-- Input SAKTI: pilih SPP/SPBy + nomor --}}
                                                                        @if ($tahap === 'lolos_spk')
                                                                            <x-sakti-nomor-button :url="route('memo.input-sakti', $m['id'])" :memo="$m['nomor_memo']" />
                                                                        @endif

                                                                        {{-- Koreksi jenis/nomor setelah SAKTI --}}
                                                                        @if (in_array($tahap, ['sakti', 'otp_ppk', 'ppspm'], true))
                                                                            <x-sakti-nomor-button :ubah="true" label="Ubah nomor"
                                                                                :url="route('memo.ubah-nomor-sakti', $m['id'])"
                                                                                :memo="$m['nomor_memo']"
                                                                                :jenis="$spp->jenis_dokumen ?? 'SPP'"
                                                                                :nomor="$spp->nomor_spp_spby ?? ''" />
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
                                                                                data-memo="{{ $m['nomor_memo'] }}"
                                                                                data-nominal="{{ $rp($m['nominal']) }}">
                                                                                @csrf
                                                                                <input type="hidden" name="status" value="sp2d">
                                                                                <input type="hidden" name="ids[]" value="{{ $m['id'] }}">
                                                                                <input type="date" name="tanggal" required
                                                                                    max="{{ now()->toDateString() }}"
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

                                                           {{-- Berkas Fisik: posisi disimpan sendiri, tidak terkait tahap memo --}}
<td class="px-2 py-2.5 align-top">
    @php $berkas = $berkasPerMemo->get($m['id'], ''); @endphp
    <select aria-label="Berkas fisik memo {{ $m['nomor_memo'] }}"
        data-url="{{ route('memo.berkas-fisik', $m['id']) }}"
        data-awal="{{ $berkas }}"
        @disabled(!$bisaKelola)
        class="js-berkas w-full min-w-0 rounded-md border py-1 pl-1.5 pr-6 text-[11px] font-semibold cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 {{ match ($berkas) {
            'Tim Keuangan' => 'bg-blue-50 text-blue-700 border-blue-200',
            'PPSPM' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            default => 'bg-gray-50 text-gray-700 border-gray-200',
        } }}">
        <option value="" disabled @selected($berkas === '')>Belum diatur</option>
        @foreach (['Tim Keuangan', 'PPSPM', 'Unit'] as $o)
            <option value="{{ $o }}" @selected($o === $berkas)>{{ $o }}</option>
        @endforeach
    </select>
</td>
                                                            {{-- Log --}}
                                                            <td class="pl-2 pr-4 py-2.5 align-top text-right">
                                                                <button type="button" data-key="{{ $m['nomor_memo'] }}"
                                                                    data-target="{{ $logId }}" aria-expanded="false"
                                                                    @disabled($logs->isEmpty())
                                                                    class="js-log-toggle rounded-md border border-gray-200 bg-white px-2 py-1 text-[11px] font-semibold text-gray-600 hover:bg-gray-50 cursor-pointer disabled:opacity-40 disabled:cursor-default focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                                                    Log <span
                                                                        class="text-gray-400 tabular-nums">({{ $logs->count() }})</span>
                                                                </button>
                                                            </td>
                                                        </tr>

                                                        {{-- Riwayat memo (tersembunyi sampai Log diklik) --}}
                                                        <tr id="{{ $logId }}" class="hidden">
                                                            <td colspan="8" class="bg-white px-4 py-4 sm:px-6">
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

    {{-- Dialog konfirmasi pencairan (SP2D) --}}
    <dialog id="dlg-sp2d" aria-labelledby="dlg-sp2d-judul"
        class="m-auto w-[min(92vw,26rem)] rounded-2xl border border-gray-200 bg-white p-0 shadow-xl backdrop:bg-gray-900/50">
        <div class="px-5 pt-5">
            <h3 id="dlg-sp2d-judul" class="text-base font-bold text-gray-900">Konfirmasi pencairan (SP2D)</h3>
            <dl class="mt-3 space-y-1.5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Memo</dt>
                    <dd id="dlg-sp2d-memo" class="font-semibold text-gray-900 text-right break-all"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Nominal</dt>
                    <dd id="dlg-sp2d-nominal" class="font-semibold text-gray-900 tabular-nums"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500">Tanggal cair</dt>
                    <dd id="dlg-sp2d-tgl" class="font-semibold text-gray-900"></dd>
                </div>
            </dl>
            <p class="mt-3 text-xs text-gray-500">Status memo akan berubah menjadi SP2D. Pastikan uang sudah benar-benar
                cair.</p>
        </div>
        <div class="mt-4 flex justify-end gap-2 border-t border-gray-100 px-5 py-3">
            <button type="button" id="dlg-sp2d-batal"
                class="rounded-lg border border-gray-300 bg-white px-3.5 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">Batal</button>
            <button type="button" id="dlg-sp2d-ok"
                class="btn-utama rounded-lg px-3.5 py-1.5 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300">Ya,
                tandai cair</button>
        </div>
    </dialog>

    <script>
        (function () {
            const S = window.sessionStorage;
            const K = { unit: 'mon.unit', log: 'mon.log', filter: 'mon.filter', scroll: 'mon.scroll' };
            const baca = (k, d) => { try { const v = JSON.parse(S.getItem(k)); return v === null ? d : v; } catch (e) { return d; } };
            const simpan = (k, v) => { try { S.setItem(k, JSON.stringify(v)); } catch (e) { } };
            const $ = (id) => document.getElementById(id);

            const unitBuka = new Set(baca(K.unit, []));
            const logBuka = new Set(baca(K.log, []));

            // ---------- Tombol "Selengkapnya" hanya bila teks terpotong (diukur saat panel terbuka)
            function ukurKegiatan(panel) {
                panel.querySelectorAll('.js-kegiatan').forEach(function (p) {
                    const t = p.parentElement.querySelector('.js-kegiatan-toggle');
                    if (!t) return;
                    const terpotong = p.classList.contains('kegiatan-clamp') && p.scrollHeight > p.clientHeight + 1;
                    const sedangBuka = !p.classList.contains('kegiatan-clamp');
                    t.classList.toggle('hidden', !(terpotong || sedangBuka));
                });
            }

            // ---------- Show / Hide unit
            function setUnit(btn, buka, catat) {
                const panel = document.getElementById(btn.dataset.target);
                if (!panel) return;
                panel.classList.toggle('hidden', !buka);
                btn.setAttribute('aria-expanded', buka ? 'true' : 'false');
                const label = btn.querySelector('.js-label');
                if (label) label.textContent = buka ? 'Hide' : 'Show';
                if (buka) ukurKegiatan(panel);
                if (catat) {
                    buka ? unitBuka.add(btn.dataset.key) : unitBuka.delete(btn.dataset.key);
                    simpan(K.unit, Array.from(unitBuka));
                }
            }
            document.querySelectorAll('.js-unit-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    setUnit(btn, document.getElementById(btn.dataset.target).classList.contains('hidden'), true);
                });
            });

            // ---------- Log memo
            function setLog(btn, buka, catat) {
                const row = document.getElementById(btn.dataset.target);
                if (!row) return;
                row.classList.toggle('hidden', !buka);
                btn.setAttribute('aria-expanded', buka ? 'true' : 'false');
                btn.classList.toggle('bg-gray-100', buka);
                if (catat) {
                    buka ? logBuka.add(btn.dataset.key) : logBuka.delete(btn.dataset.key);
                    simpan(K.log, Array.from(logBuka));
                }
            }
            document.querySelectorAll('.js-log-toggle').forEach(function (b) {
                b.addEventListener('click', function () {
                    setLog(b, document.getElementById(b.dataset.target).classList.contains('hidden'), true);
                });
            });

            // ---------- Kegiatan: Selengkapnya / Tutup
            document.querySelectorAll('.js-kegiatan-toggle').forEach(function (t) {
                t.addEventListener('click', function () {
                    const p = t.parentElement.querySelector('.js-kegiatan');
                    const ringkas = p.classList.toggle('kegiatan-clamp');
                    t.textContent = ringkas ? 'Selengkapnya' : 'Tutup';
                });
            });

           // ---------- Berkas Fisik: simpan posisi ke server
const warnaBerkas = {
    '': ['bg-gray-50', 'text-gray-700', 'border-gray-200'],
    'Tim Keuangan': ['bg-blue-50', 'text-blue-700', 'border-blue-200'],
    'PPSPM': ['bg-emerald-50', 'text-emerald-700', 'border-emerald-200'],
    'Unit': ['bg-gray-50', 'text-gray-700', 'border-gray-200'],
};
const semuaWarna = [].concat.apply([], Object.values(warnaBerkas));
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

document.querySelectorAll('.js-berkas').forEach(function (sel) {
    sel.addEventListener('change', function () {
        sel.classList.remove.apply(sel.classList, semuaWarna);
        sel.classList.add.apply(sel.classList, warnaBerkas[sel.value] || warnaBerkas['']);
        fetch(sel.dataset.url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ posisi: sel.value }),
        }).then(function (r) {
            if (!r.ok) throw new Error();
            sel.dataset.awal = sel.value;
        }).catch(function () {
            sel.value = sel.dataset.awal;
            sel.classList.remove.apply(sel.classList, semuaWarna);
            sel.classList.add.apply(sel.classList, warnaBerkas[sel.value] || warnaBerkas['']);
            alert('Gagal menyimpan posisi berkas fisik.');
        });
    });
});

            // ---------- Filter
            const F = {
                cari: $('f-cari'), status: $('f-status'), tahap: $('f-tahap'),
                dok: $('f-dok'), pengajuan: $('f-pengajuan'), lama: $('f-lama'),
            };
            const adaFilter = !!F.cari;

            function nilaiFilter() {
                return {
                    cari: F.cari.value, status: F.status.value, tahap: F.tahap.value, dok: F.dok.value,
                    pengajuan: F.pengajuan.checked, lama: F.lama.checked,
                };
            }

            function terapkan() {
                const v = nilaiFilter();
                const q = v.cari.trim().toLowerCase();
                const aktif = !!(q || v.status || v.tahap || v.dok || v.pengajuan || v.lama);
                let total = 0, cocok = 0;

                document.querySelectorAll('.js-unit').forEach(function (unit) {
                    let n = 0;
                    unit.querySelectorAll('.js-memo-row').forEach(function (tr) {
                        const d = tr.dataset;
                        const ok = (!q || d.cari.indexOf(q) !== -1)
                            && (!v.status || d.status === v.status)
                            && (!v.tahap || d.tahap === v.tahap)
                            && (!v.dok || d.dok === v.dok)
                            && (!v.pengajuan || d.pengajuan === '1')
                            && (!v.lama || Number(d.umur) > 30);
                        tr.hidden = !ok;
                        const log = document.getElementById(d.log);
                        if (log) log.hidden = !ok;
                        total++;
                        if (ok) { n++; cocok++; }
                    });
                    unit.hidden = aktif && n === 0;
                    // Saat memfilter, buka unit yang punya hasil supaya langsung terlihat
                    if (aktif && n > 0) {
                        const btn = unit.querySelector('.js-unit-toggle');
                        if (btn) setUnit(btn, true, false);
                    }
                });

                $('f-info').textContent = !aktif
                    ? total + ' memo'
                    : (cocok === 0 ? 'Tidak ada memo yang cocok dengan filter.' : 'Menampilkan ' + cocok + ' dari ' + total + ' memo.');
                simpan(K.filter, v);
            }

            if (adaFilter) {
                const awal = baca(K.filter, null);
                if (awal) {
                    F.cari.value = awal.cari || ''; F.status.value = awal.status || ''; F.tahap.value = awal.tahap || '';
                    F.dok.value = awal.dok || ''; F.pengajuan.checked = !!awal.pengajuan; F.lama.checked = !!awal.lama;
                }
                let timer;
                F.cari.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(terapkan, 150); });
                [F.status, F.tahap, F.dok, F.pengajuan, F.lama].forEach(function (el) { el.addEventListener('change', terapkan); });
                $('f-reset').addEventListener('click', function () {
                    F.cari.value = ''; F.status.value = ''; F.tahap.value = ''; F.dok.value = '';
                    F.pengajuan.checked = false; F.lama.checked = false;
                    terapkan();
                });
            }

            // ---------- Simpan posisi scroll supaya halaman tidak lompat ke atas setelah aksi
            function simpanKonteks() { simpan(K.scroll, { y: window.scrollY, t: Date.now() }); }
            window.addEventListener('pagehide', simpanKonteks);

            // ---------- Dialog konfirmasi SP2D
            const dlg = $('dlg-sp2d');
            let formTertunda = null;
            const tglPanjang = (iso) => new Date(iso + 'T00:00:00')
                .toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

            function kirim() {
                if (!formTertunda) return;
                simpanKonteks();
                formTertunda.submit(); // submit() tidak memicu event submit lagi, jadi tidak looping
            }

            document.querySelectorAll('.js-aksi-form').forEach(function (f) {
                f.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const tgl = f.querySelector('[name="tanggal"]');
                    if (tgl && !tgl.value) { tgl.focus(); return; }
                    formTertunda = f;
                    if (!dlg || typeof dlg.showModal !== 'function') {
                        if (window.confirm('Tandai memo ' + (f.dataset.memo || '') + ' sudah cair (SP2D)?')) kirim();
                        return;
                    }
                    $('dlg-sp2d-memo').textContent = f.dataset.memo || '-';
                    $('dlg-sp2d-nominal').textContent = f.dataset.nominal || '-';
                    $('dlg-sp2d-tgl').textContent = tgl && tgl.value ? tglPanjang(tgl.value) : '-';
                    $('dlg-sp2d-ok').disabled = false;
                    dlg.showModal();
                });
            });

            if (dlg) {
                $('dlg-sp2d-ok').addEventListener('click', function () {
                    this.disabled = true; // cegah klik ganda
                    kirim();
                });
                $('dlg-sp2d-batal').addEventListener('click', function () { formTertunda = null; dlg.close(); });
                dlg.addEventListener('cancel', function () { formTertunda = null; });
            }

            // ---------- Pulihkan keadaan setelah halaman dimuat ulang
            document.querySelectorAll('.js-unit-toggle').forEach(function (b) { if (unitBuka.has(b.dataset.key)) setUnit(b, true, false); });
            document.querySelectorAll('.js-log-toggle').forEach(function (b) { if (logBuka.has(b.dataset.key)) setLog(b, true, false); });
            if (adaFilter) terapkan();

            const sc = baca(K.scroll, null);
            if (sc && Date.now() - sc.t < 15000) {
                requestAnimationFrame(function () { window.scrollTo(0, sc.y); });
            }
            try { S.removeItem(K.scroll); } catch (e) { }
        })();
    </script>

    <style>
        /* Kegiatan: tampil 2 baris, sisanya lewat "Selengkapnya" */
        .kegiatan-clamp {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

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

        .btn-utama:disabled {
            opacity: .6;
            cursor: default;
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