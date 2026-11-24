{{-- Serapan anggaran per unit kerja (dipakai semua role, termasuk Eselon II).
     Menerima event 'fokus-serapan' ({kode, depth}) dari chart untuk membuka & menyorot baris. --}}
@php
    $rp ??= fn($n) => number_format($n, 0, ',', '.');
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
    $barisCari = $pohon->map(fn ($t) => [
        'id' => $t['id'],
        'anc' => $t['anc'],
        'kode' => $t['kode'],
        'depth' => $t['depth'],
        'teks' => mb_strtolower($t['kode'] . ' ' . $t['uraian'] . ' ' . $t['unit']),
    ])->values();
    $barisInduk = $pohon->where('anak', true)->map(fn ($t) => ['id' => $t['id'], 'depth' => $t['depth']])->values();
    $bukaAwal = $pohon->where('depth', '<=', 1)->where('anak', true)->pluck('id')->values();
    $semuaInduk = $pohon->where('anak', true)->pluck('id')->values();
    $tglData = ($uploadAktif ?? null)?->created_at;
@endphp

<div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h4 class="font-semibold text-gray-800">Serapan Anggaran</h4>
            <p class="text-sm text-gray-400">
                {{ $serapan['bisa_pilih'] ? 'Pagu dan realisasi per unit kerja.' : 'Pagu dan realisasi unit Anda.' }}
            </p>
            @if ($tglData)
                <p class="text-xs text-gray-400 mt-1">
                    Data realisasi per
                    <span class="font-semibold text-gray-700">{{ $tglData->translatedFormat('d F Y') }}</span>
                    ({{ $tglData->diffForHumans() }})
                </p>
            @endif
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
            <div id="tabel-serapan" class="mt-6 rounded-2xl border border-gray-200 overflow-hidden"
                @fokus-serapan.window="fokus($event.detail)"
                x-data="{
                    sorot: null,
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
                    tutupSemua() { this.buka = []; },
                    fokus(d) {
                        const r = this.rows.find(x => x.kode === d.kode && x.depth === d.depth);
                        if (!r) return;
                        this.q = ''; this.cocok = {}; this.level = 99;
                        this.buka = [...new Set([...this.buka, ...r.anc, r.id])];
                        this.sorot = r.id;
                        this.$nextTick(() => document.getElementById('baris-' + r.id)
                            ?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                    }
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
                                <tr id="baris-{{ $t['id'] }}"
:class="sorot === {{ $t['id'] }} ? '!bg-amber-50 shadow-[inset_3px_0_0_#f59e0b]' : ''"                                    x-show="tampil({{ $t['id'] }}, @js($t['anc']), {{ $t['depth'] }})" x-cloak
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