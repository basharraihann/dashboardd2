<x-app-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $btnOutline = 'inline-flex flex-1 sm:flex-none justify-center items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold border border-blue-500 text-blue-600 bg-white hover:bg-blue-50 transition';
        $rp = fn($n) => number_format($n, 0, ',', '.');
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            {{-- Welcome card + aksi cepat --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-gray-800">
                            Halo, {{ auth()->user()->name ?? 'Pengguna' }} 👋
                        </h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            Selamat datang kembali. Berikut ringkasan aktivitas perjalanan dinas Biro Anda.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2 w-full lg:w-auto">
                        <a href="{{ route('agendas.create') }}"
                            class="inline-flex flex-1 sm:flex-none justify-center items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2.5 rounded-xl text-sm shadow-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Agenda Baru
                        </a>
                        @can('buat-memo')
                            <a href="{{ route('memo.create') }}" class="{{ $btnOutline }}">Buat Nomor Memo</a>
                        @endcan
                        <a href="{{ route('unduh.index') }}" class="{{ $btnOutline }}">Unduh Massal</a>
                        <a href="{{ route('monitoring.index') }}" class="{{ $btnOutline }}">Monitoring Memo</a>
                    </div>
                </div>
            </div>

            {{-- Ringkasan statistik --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 flex items-center gap-4">
                    <div class="h-11 w-11 shrink-0 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-500">Agenda bulan ini</p>
                        <p class="text-xl font-bold text-gray-800 tabular-nums">{{ $agendaBulanIni }}</p>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
                    <p class="text-sm text-gray-500 mb-3">Rincian dana LS bulan ini</p>
                    <div class="grid grid-cols-3 gap-3 divide-x divide-gray-100">
                        <div class="min-w-0">
                            <p class="text-xs text-gray-400">Perdin</p>
                            <p class="text-sm font-semibold text-gray-800 tabular-nums break-words">{{ $rp($danaLsBulanIni['perdin']) }}</p>
                        </div>
                        <div class="min-w-0 pl-3">
                            <p class="text-xs text-gray-400">Konsumsi</p>
                            <p class="text-sm font-semibold text-gray-800 tabular-nums break-words">{{ $rp($danaLsBulanIni['konsumsi']) }}</p>
                        </div>
                        <div class="min-w-0 pl-3">
                            <p class="text-xs text-gray-400">Honor</p>
                            <p class="text-sm font-semibold text-gray-800 tabular-nums break-words">{{ $rp($danaLsBulanIni['honorarium']) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 flex items-center gap-4">
                    <div class="h-11 w-11 shrink-0 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-gray-500">Dana diajukan (LS) bulan ini</p>
                        <p class="text-xl font-bold text-gray-800 tabular-nums break-words">Rp {{ $rp($danaLsBulanIni['total']) }}</p>
                    </div>
                </div>
            </div>

            {{-- Serapan anggaran per unit kerja --}}
            @php
                $r = 52;
                $keliling = 2 * M_PI * $r;
                $persen = max(0, min(100, $serapan['persen']));
                $isi = $keliling * $persen / 100;
                $baris = [
                    ['Pagu', $serapan['pagu'], 'bg-blue-600', 'text-blue-900'],
                    ['Realisasi', $serapan['realisasi'], 'bg-teal-600', 'text-teal-800'],
                    ['Sisa', $serapan['sisa'], 'bg-amber-600', 'text-amber-800'],
                    ['Outstanding', $serapan['outstanding'], 'bg-purple-600', 'text-purple-700'],
                    ['Sisa Efektif', $serapan['sisa_efektif'], 'bg-green-600', 'text-green-800'],
                ];
                $pohon = collect($serapan['tree']);
                $barisCari = $pohon->map(fn($t) => [
                    'id' => $t['id'],
                    'anc' => $t['anc'],
                    'teks' => mb_strtolower($t['kode'] . ' ' . $t['uraian'] . ' ' . $t['unit']),
                ])->values();
                $barisInduk = $pohon->where('anak', true)->map(fn($t) => ['id' => $t['id'], 'depth' => $t['depth']])->values();
                $bukaAwal = $pohon->where('depth', '<=', 1)->where('anak', true)->pluck('id')->values();
                $semuaInduk = $pohon->where('anak', true)->pluck('id')->values();
            @endphp

            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                    <div>
                        <h4 class="font-semibold text-gray-800">Serapan Anggaran</h4>
                        <p class="text-sm text-gray-400">
                            {{ $serapan['bisa_pilih'] ? 'Pagu dan realisasi per unit kerja.' : 'Pagu dan realisasi unit Anda.' }}
                        </p>
                    </div>

                    @if ($serapan['bisa_pilih'])
                        <form method="GET" action="{{ route('dashboard') }}">
                            <select name="unit" onchange="this.form.submit()"
                                class="h-10 rounded-lg border border-gray-300 text-sm text-gray-800 focus:border-blue-600 focus:ring-blue-600">
                                @foreach ($opsiUnit as $u)
                                    <option value="{{ $u }}" @selected($serapan['unit'] === $u)>{{ $u }}</option>
                                @endforeach
                            </select>
                        </form>
                    @elseif ($serapan['ada'])
                        <span class="inline-flex items-center h-9 px-3 rounded-lg bg-gray-100 text-sm font-semibold text-gray-700">
                            {{ $serapan['unit'] }}
                        </span>
                    @endif
                </div>

                @if (!$serapan['ada'])
                    <div class="py-10 text-center text-sm text-gray-400">
                        Data anggaran untuk unit Anda belum tersedia atau unit akun belum dipetakan.
                    </div>
                @else
                    <div class="flex flex-wrap items-center gap-6">
                        {{-- Donut --}}
                        <div class="flex items-center gap-4">
                            <div class="relative h-32 w-32 shrink-0">
                                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                                    <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="#e5e7eb" stroke-width="12" />
                                    <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="#2563eb" stroke-width="12"
                                        stroke-linecap="round" stroke-dasharray="{{ $isi }} {{ $keliling }}" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center text-sm font-semibold text-gray-900">
                                    {{ number_format($serapan['persen'], 2, ',', '.') }}%
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Unit terpilih</p>
                                <p class="text-2xl font-bold text-gray-900">{{ $serapan['unit'] }}</p>
                                <p class="text-xs text-gray-400">Penyerapan anggaran</p>
                            </div>
                        </div>

                        {{-- Ringkasan --}}
                        <div class="flex-1 min-w-[260px] rounded-xl bg-gray-50 divide-y divide-gray-100 px-4">
                            @foreach ($baris as [$nama, $nilai, $dot, $teks])
                                <div class="flex items-center justify-between gap-3 py-2.5 text-sm">
                                    <span class="inline-flex items-center gap-2 text-gray-500">
                                        <span class="h-2 w-2 rounded-full {{ $dot }}"></span>{{ $nama }}
                                    </span>
                                    <span class="font-medium {{ $teks }}">Rp {{ $rp($nilai) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tabel bertingkat --}}
                    @if ($pohon->count() > 0)
                        <div class="mt-6 rounded-2xl border border-gray-200 overflow-hidden"
                            x-data="{
                                buka: @js($bukaAwal),
                                semua: @js($semuaInduk),
                                induk: @js($barisInduk),
                                rows: @js($barisCari),
                                level: 99,
                                q: '',
                                cocok: {},
                                hitung() {
                                    const k = this.q.trim().toLowerCase();
                                    const o = {};
                                    if (k) {
                                        this.rows.forEach(r => {
                                            if (r.teks.includes(k)) { o[r.id] = true; r.anc.forEach(a => o[a] = true); }
                                        });
                                    }
                                    this.cocok = o;
                                },
                                tampil(id, anc, depth) {
                                    if (depth > this.level) return false;
                                    if (this.q.trim()) return !!this.cocok[id];
                                    return anc.every(a => this.buka.includes(a));
                                },
                                toggle(id) { this.buka = this.buka.includes(id) ? this.buka.filter(x => x !== id) : [...this.buka, id] },
                                pakaiLevel() {
                                    this.buka = this.level >= 99 ? [...this.semua] : this.induk.filter(r => this.level > r.depth).map(r => r.id);
                                },
                                bukaSemua() { this.level = 99; this.buka = [...this.semua]; },
                                tutupSemua() { this.buka = []; }
                            }"
                            x-init="$watch('q', () => hitung())">

                            {{-- Toolbar --}}
                            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-2.5 bg-white border-b border-gray-200">
                                <p class="text-sm text-gray-500">Klik baris untuk melihat induk &amp; turunannya. Klik panah untuk membuka atau menutup cabang.</p>

                                <div class="flex flex-wrap items-center gap-2">
                                    <input type="search" x-model.debounce.200ms="q" placeholder="Cari kode atau uraian"
                                        class="h-9 w-full sm:w-52 rounded-xl border border-gray-300 text-sm text-gray-800 placeholder-gray-400 focus:border-blue-600 focus:ring-blue-600">

                                    <button type="button" @click="bukaSemua()"
                                        class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4" /></svg>
                                        Buka semua
                                    </button>
                                    <button type="button" @click="tutupSemua()"
                                        class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 4v4H4M16 4v4h4M8 20v-4H4M16 20v-4h4" /></svg>
                                        Tutup semua
                                    </button>

                                    <label class="inline-flex items-center gap-2 text-sm text-gray-500">
                                        Level
                                        <select x-model.number="level" @change="pakaiLevel()"
                                            class="h-9 rounded-xl border border-gray-300 text-sm text-gray-800 focus:border-blue-600 focus:ring-blue-600">
                                            <option value="99">Semua (sampai item)</option>
                                            <option value="0">Program</option>
                                            <option value="1">Kegiatan</option>
                                            <option value="2">RO</option>
                                            <option value="3">Komponen</option>
                                            <option value="4">Sub komponen</option>
                                            <option value="5">Akun</option>
                                        </select>
                                    </label>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-[11px] leading-snug tabular-nums border-collapse">
                                    <thead>
                                        <tr class="bg-blue-600 text-white">
                                            <th class="text-left px-2 py-2 font-semibold border-r border-blue-500/60">Kode</th>
                                            <th class="text-left px-2 py-2 font-semibold border-r border-blue-500/60">Uraian</th>
                                            <th class="text-left px-2 py-2 font-semibold border-r border-blue-500/60">Unit</th>
                                            <th class="text-right px-2 py-2 font-semibold border-r border-blue-500/60">Pagu Revisi</th>
                                            <th class="text-right px-2 py-2 font-semibold border-r border-blue-500/60">Real. Lalu</th>
                                            <th class="text-right px-2 py-2 font-semibold border-r border-blue-500/60">Real. Ini</th>
                                            <th class="text-right px-2 py-2 font-semibold border-r border-blue-500/60">Real. s.d.</th>
                                            <th class="text-right px-2 py-2 font-semibold border-r border-blue-500/60">%</th>
                                            <th class="text-right px-2 py-2 font-semibold border-r border-blue-500/60">Sisa</th>
                                            <th class="text-right px-2 py-2 font-semibold border-r border-blue-500/60">Outstanding</th>
                                            <th class="text-right px-2 py-2 font-semibold">Sisa Efektif</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($serapan['tree'] as $t)
                                            <tr x-show="tampil({{ $t['id'] }}, @js($t['anc']), {{ $t['depth'] }})" x-cloak
                                                @if ($t['anak']) @click="toggle({{ $t['id'] }})" @endif
                                                class="{{ $t['anak'] ? 'cursor-pointer' : '' }} {{ $t['depth'] < 2 ? 'bg-gray-50 font-semibold text-gray-900' : 'bg-white text-gray-700' }} hover:bg-blue-50/50 transition-colors">
                                                <td class="px-2 py-2 whitespace-nowrap border-b border-r border-gray-200">
                                                    <span class="inline-flex items-center gap-1" style="padding-left: {{ $t['depth'] * 10 }}px">
                                                        @if ($t['anak'])
                                                            <svg class="h-3 w-3 shrink-0 text-gray-500 transition-transform"
                                                                :class="buka.includes({{ $t['id'] }}) ? 'rotate-90' : ''"
                                                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                        @else
                                                            <span class="inline-block w-3 shrink-0"></span>
                                                        @endif
                                                        {{ $t['kode'] }}
                                                    </span>
                                                </td>
                                                <td class="px-2 py-2 border-b border-r border-gray-200 break-words">{{ $t['uraian'] }}</td>
                                                <td class="px-2 py-2 whitespace-nowrap border-b border-r border-gray-200 {{ $t['unit_tunggal'] ? 'text-blue-600 font-normal' : 'text-gray-400 font-normal' }}">{{ $t['unit'] }}</td>
                                                <td class="px-2 py-2 text-right border-b border-r border-gray-200">{{ $rp($t['pagu']) }}</td>
                                                <td class="px-2 py-2 text-right border-b border-r border-gray-200">{{ $rp($t['lalu']) }}</td>
                                                <td class="px-2 py-2 text-right border-b border-r border-gray-200">{{ $rp($t['ini']) }}</td>
                                                <td class="px-2 py-2 text-right border-b border-r border-gray-200">{{ $rp($t['sd']) }}</td>
                                                <td class="px-2 py-2 text-right whitespace-nowrap border-b border-r border-gray-200 font-normal">{{ number_format($t['persen'], 2, ',', '.') }}%</td>
                                                <td class="px-2 py-2 text-right border-b border-r border-gray-200">{{ $rp($t['sisa']) }}</td>
                                                <td class="px-2 py-2 text-right border-b border-r border-gray-200 font-normal {{ ($t['out'] ?? 0) > 0 ? 'text-amber-700' : 'text-gray-300' }}">
                                                    {{ ($t['out'] ?? 0) > 0 ? $rp($t['out']) : '–' }}
                                                </td>
                                                <td class="px-2 py-2 text-right border-b border-gray-200 {{ ($t['efektif'] ?? 0) < 0 ? 'text-red-600' : '' }}">
                                                    {{ $t['efektif'] === null ? '–' : $rp($t['efektif']) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <p x-cloak x-show="q.trim() && Object.keys(cocok).length === 0"
                                    class="py-8 text-center text-sm text-gray-400">Tidak ada baris yang cocok dengan pencarian.</p>
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            
{{-- Chart pengeluaran & kalender agenda --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="min-w-0 lg:col-span-2 bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
                    <h4 class="font-semibold text-gray-800 mb-0.5">Tren pengeluaran perjalanan dinas</h4>
                    <p class="text-sm text-gray-500 mb-4">Total belanja per bulan, tahun {{ now()->year }}.</p>
                    @if ($chartKosong)
                        <div class="py-16 text-center text-sm text-gray-400">
                            Belum ada pengeluaran yang tercatat pada tahun {{ now()->year }}.
                        </div>
                    @else
                        <canvas id="chartPengeluaran" height="110"></canvas>
                    @endif
                </div>

                {{-- Kalender (dirender oleh JS dari data JSON) --}}
                <div class="min-w-0 bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                        <div class="flex items-center gap-1 min-w-0">
                            <select id="kal-bulan" aria-label="Pilih bulan"
                                class="border-0 bg-transparent py-0 pl-0 pr-6 text-sm font-semibold text-gray-800 focus:ring-0 cursor-pointer"></select>
                            <select id="kal-tahun" aria-label="Pilih tahun"
                                class="border-0 bg-transparent py-0 pl-0 pr-6 text-sm font-semibold text-gray-800 focus:ring-0 cursor-pointer"></select>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" id="kal-hariini"
                                class="hidden text-xs text-blue-600 hover:bg-blue-50 px-2 py-1.5 sm:py-1 rounded-lg transition">Hari
                                ini</button>
                            <button type="button" id="kal-prev" aria-label="Bulan sebelumnya"
                                class="h-9 w-9 sm:h-7 sm:w-7 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition">‹</button>
                            <button type="button" id="kal-next" aria-label="Bulan berikutnya"
                                class="h-9 w-9 sm:h-7 sm:w-7 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition">›</button>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">Tanggal bertanda ada agenda SPJ.</p>

                    <div class="grid grid-cols-7 gap-1 text-center text-xs text-gray-400 mb-1.5">
                        <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span
                            class="text-red-400">Min</span>
                    </div>

                    <div id="kal-grid" class="grid grid-cols-7 gap-1 transition-opacity"></div>

                    <p id="kal-error" class="hidden mt-3 text-xs text-red-500">Gagal memuat kalender. Coba lagi.</p>

                    <div id="kalender-detail" class="hidden mt-4 pt-4 border-t border-gray-100 space-y-2.5"></div>
                </div>
            </div>

            {{-- Chart.js untuk grafik pengeluaran --}}
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // ==================== Chart ====================
                    const ctx = document.getElementById('chartPengeluaran');
                    const isMobile = window.innerWidth < 640;
                    if (ctx) {
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: @json($chartLabels),
                                datasets: [{
                                    label: 'Total Belanja (Rp)',
                                    data: @json($chartData),
                                    backgroundColor: '#2563eb',
                                    borderRadius: 6,
                                    maxBarThickness: 36,
                                }]
                            },
                            options: {
                                responsive: true,
                                aspectRatio: isMobile ? 1.5 : 300 / 110,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: { display: false },
                                        ticks: { font: { size: isMobile ? 10 : 12 } }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            font: { size: isMobile ? 10 : 12 },
                                            callback: function (value) {
                                                if (value >= 1000000) {
                                                    return 'Rp ' + (value / 1000000) + 'jt';
                                                }
                                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // ==================== Kalender (tanpa refresh) ====================
                    const KALENDER_URL = @json(route('dashboard.kalender'));
                    const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

                    const grid = document.getElementById('kal-grid');
                    const detailBox = document.getElementById('kalender-detail');
                    const selBulan = document.getElementById('kal-bulan');
                    const selTahun = document.getElementById('kal-tahun');
                    const btnPrev = document.getElementById('kal-prev');
                    const btnNext = document.getElementById('kal-next');
                    const btnHariIni = document.getElementById('kal-hariini');
                    const errBox = document.getElementById('kal-error');

                    let kal = @json($kalender);
                    let requestId = 0;

                    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({
                        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                    }[c]));

                    const icon = d => `<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="${d}"/></svg>`;
                    const ICON_LOKASI = icon('M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z');
                    const ICON_PEGAWAI = icon('M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z');
                    const ICON_TANGGAL = icon('M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z');

                    function renderControls() {
                        selBulan.innerHTML = NAMA_BULAN.map((n, i) =>
                            `<option value="${i + 1}" ${i + 1 === kal.bulan ? 'selected' : ''}>${n}</option>`).join('');

                        let opsiTahun = '';
                        for (let y = kal.tahun - 5; y <= kal.tahun + 5; y++) {
                            opsiTahun += `<option value="${y}" ${y === kal.tahun ? 'selected' : ''}>${y}</option>`;
                        }
                        selTahun.innerHTML = opsiTahun;

                        btnHariIni.classList.toggle('hidden', kal.isBulanIni);
                    }

                    function renderGrid() {
                        let html = '';
                        for (let i = 0; i < kal.offset; i++) html += '<div></div>';

                        for (let tgl = 1; tgl <= kal.jumlahHari; tgl++) {
                            const list = kal.marks[tgl] || [];
                            const ada = list.length > 0;
                            const isHariIni = tgl === kal.hariIni;
                            const kolom = (kal.offset + tgl - 1) % 7; // 5 = Sabtu, 6 = Minggu

                            let warna;
                            if (isHariIni) warna = 'ring-2 ring-blue-400 font-bold text-blue-700';
                            else if (kolom === 6) warna = 'text-red-400';
                            else if (kolom === 5) warna = 'text-gray-400';
                            else warna = 'text-gray-600';

                            const bg = ada ? 'bg-blue-50 hover:bg-blue-100 cursor-pointer font-semibold' : '';
                            const dots = ada
                                ? `<span class="absolute bottom-1 flex gap-0.5">${list.slice(0, 3).map(() => '<span class="h-1 w-1 rounded-full bg-blue-500"></span>').join('')}</span>`
                                : '';

                            html += `<button type="button" ${ada ? `data-tgl="${tgl}"` : ''}
                                class="kalender-hari relative aspect-square flex items-center justify-center rounded-lg text-xs transition ${warna} ${bg}">
                                ${tgl}${dots}</button>`;
                        }
                        grid.innerHTML = html;

                        detailBox.classList.add('hidden');
                        detailBox.innerHTML = '';

                        if (kal.hariIni) {
                            grid.querySelector(`[data-tgl="${kal.hariIni}"]`)?.click();
                        }
                    }

                    function render() {
                        renderControls();
                        renderGrid();
                    }

                    function showDetail(tgl) {
                        const list = kal.marks[tgl] || [];

                        grid.querySelectorAll('.kalender-hari').forEach(b =>
                            b.classList.remove('outline', 'outline-2', 'outline-blue-500'));
                        grid.querySelector(`[data-tgl="${tgl}"]`)?.classList.add('outline', 'outline-2', 'outline-blue-500');

                        detailBox.classList.remove('hidden');
                        detailBox.innerHTML = list.map(a => {
                            const pegawai = (a.pegawai || []).map(esc).join(', ') || '-';
                            return `<a href="/agendas/${a.id}" class="block text-xs break-words hover:bg-gray-50 -mx-2 px-2 py-2 rounded-lg transition">
                                <span class="block font-semibold text-gray-800">${esc(a.uraian)}</span>
                                <span class="flex gap-1.5 text-blue-500 mt-1">${ICON_LOKASI}<span class="min-w-0">${esc(a.lokasi)}</span></span>
                                <span class="flex gap-1.5 text-blue-500">${ICON_PEGAWAI}<span class="min-w-0">${pegawai}</span></span>
                                <span class="flex gap-1.5 text-blue-500">${ICON_TANGGAL}<span class="min-w-0">${esc(a.periode)}</span></span>
                            </a>`;
                        }).join('');
                    }

                    async function muatBulan(ym) {
                        const id = ++requestId;
                        errBox.classList.add('hidden');
                        grid.classList.add('opacity-50');

                        try {
                            const res = await fetch(`${KALENDER_URL}?bulan=${encodeURIComponent(ym)}`, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            const data = await res.json();
                            if (id !== requestId) return;
                            kal = data;
                            render();
                        } catch (e) {
                            if (id === requestId) errBox.classList.remove('hidden');
                            console.error('Kalender gagal dimuat:', e);
                        } finally {
                            if (id === requestId) grid.classList.remove('opacity-50');
                        }
                    }

                    const pad = n => String(n).padStart(2, '0');

                    grid.addEventListener('click', e => {
                        const btn = e.target.closest('[data-tgl]');
                        if (btn) showDetail(btn.dataset.tgl);
                    });
                    btnPrev.addEventListener('click', () => muatBulan(kal.prev));
                    btnNext.addEventListener('click', () => muatBulan(kal.next));
                    btnHariIni.addEventListener('click', () => muatBulan(''));
                    selBulan.addEventListener('change', () => muatBulan(`${selTahun.value}-${pad(selBulan.value)}`));
                    selTahun.addEventListener('change', () => muatBulan(`${selTahun.value}-${pad(selBulan.value)}`));

                    render();
                });
            </script>
        </div>
    </div>
</x-app-layout>