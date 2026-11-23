<x-app-layout title="Unduh Massal">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Unduh Massal Dokumen</h2>
    </x-slot>

    @php
        $input = 'w-full h-10 rounded-lg border-gray-300 text-sm text-gray-800 focus:border-blue-600 focus:ring-blue-600';
        $label = 'block text-xs font-medium text-gray-600 mb-1';
        $btn = 'inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-xl text-sm font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition';
        $btnPrimary = 'inline-flex items-center justify-center gap-1.5 h-9 px-4 rounded-xl text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed';
        $card = 'bg-white border border-gray-200 rounded-xl';
        $dotStatus = [
            'outstanding' => 'bg-amber-500',
            'sakti' => 'bg-blue-600',
            'sp2d' => 'bg-green-500',
            'batal' => 'bg-gray-400',
        ];
        $semuaKeys = $grup->flatMap(fn($g) => $g['docs']->pluck('key'))->values()->all();
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}</div>
            @endif

            {{-- Ringkasan --}}
            <div class="{{ $card }} px-5 py-3.5 flex flex-wrap gap-x-8 gap-y-2 text-sm text-gray-500">
                <div><b class="text-gray-900 text-base mr-1">{{ $totalMemo }}</b>memo</div>
                <div><b class="text-gray-900 text-base mr-1">{{ $totalDok }}</b>dokumen</div>
                <div>Batas per unduhan: <b class="text-gray-900 text-base mx-1">{{ $maks }}</b>dokumen</div>
            </div>

            {{-- Filter --}}
            <form method="GET" class="{{ $card }} p-4 sm:p-5"
                x-data="{ lanjut: {{ request()->hasAny(['jenis', 'tahap', 'pic', 'mak', 'min', 'maks', 'dari', 'sampai', 'unit']) ? 'true' : 'false' }} }">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label class="{{ $label }}">Pencarian</label>
                        <input type="text" name="q" value="{{ request('q') }}"
                            placeholder="Nomor, uraian, PIC, pegawai, MAK" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Jenis dokumen</label>
                        <select name="dok" class="{{ $input }}">
                            <option value="">Semua</option>
                            @foreach ($opsiJenisDok as $d)
                                <option value="{{ $d }}" @selected(request('dok') === $d)>{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Status</label>
                        <select name="status" class="{{ $input }}">
                            <option value="">Semua</option>
                            @foreach (['outstanding' => 'Outstanding', 'sakti' => 'SAKTI', 'sp2d' => 'SP2D', 'batal' => 'Batal'] as $k => $v)
                                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div x-show="lanjut" x-cloak class="mt-4 pt-4 border-t border-gray-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="{{ $label }}">Jenis memo</label>
                            <select name="jenis" class="{{ $input }}">
                                <option value="">Semua</option>
                                @foreach ($opsiJenis as $j)
                                    <option value="{{ $j }}" @selected(request('jenis') === $j)>{{ $j }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Tahap</label>
                            <select name="tahap" class="{{ $input }}">
                                <option value="">Semua</option>
                                @foreach ($opsiTahap as $t)
                                    <option value="{{ $t }}" @selected(request('tahap') === $t)>
                                        {{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($lintasUnit)
                            <div>
                                <label class="{{ $label }}">Unit</label>
                                <select name="unit" class="{{ $input }}">
                                    <option value="">Semua</option>
                                    @foreach ($opsiUnit as $u)
                                        <option value="{{ $u }}" @selected(request('unit') === $u)>{{ $u }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label class="{{ $label }}">PIC</label>
                            <select name="pic" class="{{ $input }}">
                                <option value="">Semua</option>
                                @foreach ($opsiPic as $p)
                                    <option value="{{ $p }}" @selected(request('pic') === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">MAK</label>
                            <select name="mak" class="{{ $input }}">
                                <option value="">Semua</option>
                                @foreach ($opsiMak as $m)
                                    <option value="{{ $m }}" @selected(request('mak') === $m)>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="{{ $label }}">Tanggal dari</label>
                            <input type="date" name="dari" value="{{ request('dari') }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Tanggal sampai</label>
                            <input type="date" name="sampai" value="{{ request('sampai') }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Nominal minimum</label>
                            <input type="text" inputmode="numeric" name="min" value="{{ request('min') }}"
                                placeholder="0" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Nominal maksimum</label>
                            <input type="text" inputmode="numeric" name="maks" value="{{ request('maks') }}"
                                placeholder="Tanpa batas" class="{{ $input }}">
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button class="{{ $btnPrimary }}">Terapkan</button>
                    <a href="{{ route('unduh.index') }}" class="{{ $btn }}">Reset</a>
                    <button type="button" @click="lanjut = !lanjut"
                        class="h-9 px-3 rounded-xl text-sm font-medium text-blue-600 hover:bg-blue-50 transition"
                        x-text="lanjut ? 'Sembunyikan filter lanjutan' : 'Filter lanjutan'"></button>
                    <a href="{{ route('unduh.excel', request()->query()) }}" class="{{ $btn }} sm:ml-auto">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Ekspor Excel ({{ $totalDok }})
                    </a>
                </div>
            </form>

            {{-- Daftar memo --}}
            <form method="POST" action="{{ route('unduh.zip') }}" x-ref="form" x-data="{
                    dipilih: [],
                    maks: {{ $maks }},
                    semua: @js($semuaKeys),
                    toggleMemo(keys, on) {
                        if (on) { keys.forEach(k => { if (!this.dipilih.includes(k) && this.dipilih.length < this.maks) this.dipilih.push(k) }) }
                        else { this.dipilih = this.dipilih.filter(k => !keys.includes(k)) }
                    },
                    penuh(keys) { return keys.length > 0 && keys.every(k => this.dipilih.includes(k)) },
                    unduhSatu(keys) { this.dipilih = keys.slice(0, this.maks); this.$nextTick(() => this.$refs.form.submit()) },
                    semuaTerpilih() { return this.semua.length > 0 && this.dipilih.length === Math.min(this.semua.length, this.maks) },
                    toggleSemua(on) { this.dipilih = on ? this.semua.slice(0, this.maks) : [] }
                }" class="space-y-3">
                @csrf

                {{-- Bar aksi --}}
                <div
                    class="{{ $card }} sticky top-0 z-20 shadow-sm px-4 py-2.5 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer">
                        <input type="checkbox" :checked="semuaTerpilih()" @change="toggleSemua($event.target.checked)"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                        Pilih semua
                        @if (count($semuaKeys) > $maks)
                            <span class="text-xs font-normal text-gray-400">(maks. {{ $maks }} dari
                                {{ count($semuaKeys) }})</span>
                        @endif
                    </label>
                    <div class="text-sm text-gray-500">Dipilih <b class="text-gray-900" x-text="dipilih.length"></b>
                        dari {{ $maks }} dokumen</div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" name="sertakan_excel" value="1" checked
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                        Sertakan rekap Excel
                    </label>
                    <div class="ml-auto flex gap-2">
                        <button type="button" x-show="dipilih.length" x-cloak @click="dipilih = []"
                            class="{{ $btn }} !h-8 !px-3 !text-xs">Kosongkan</button>
                        <button type="submit" :disabled="dipilih.length === 0"
                            class="{{ $btnPrimary }} !h-8 !px-3 !text-xs">Unduh ZIP terpilih</button>
                    </div>
                </div>

                @forelse ($grup as $g)
                    @php
                        $m = $g['memo'];
                        $keys = $g['docs']->pluck('key')->values()->all();
                    @endphp
                    <div class="{{ $card }} overflow-hidden" x-data="{ buka: false }">
                        <div class="p-4 sm:p-5 flex gap-3.5 items-start">
                            <input type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600 shrink-0"
                                :checked="penuh(@js($keys))" @change="toggleMemo(@js($keys), $event.target.checked)">

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <span class="font-semibold text-gray-900 break-all">{{ $m['nomor_memo'] }}</span>
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600">
                                        <span
                                            class="h-2 w-2 rounded-full {{ $dotStatus[$m['status']] ?? 'bg-gray-400' }}"></span>
                                        {{ ucfirst($m['status']) }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ $m['jenis_label'] }}@if ($m['label']) · {{ $m['label'] }}@endif
                                    </span>
                                </div>

                                <p class="mt-1.5 text-sm leading-relaxed text-gray-700 break-words">{{ $m['uraian'] }}</p>

                                <dl
                                    class="mt-3 pt-3 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-x-4 gap-y-3">
                                    <div>
                                        <dt class="text-[11px] text-gray-400">Tanggal</dt>
                                        <dd class="text-sm text-gray-900">
                                            {{ $m['tgl_memo']?->translatedFormat('d M Y') ?? '-' }}</dd>
                                    </div>
                                    @if ($lintasUnit)
                                        <div>
                                            <dt class="text-[11px] text-gray-400">Unit</dt>
                                            <dd class="text-sm text-gray-900">{{ $m['unit'] ?? '-' }}</dd>
                                        </div>
                                    @endif
                                    <div>
                                        <dt class="text-[11px] text-gray-400">PIC</dt>
                                        <dd class="text-sm text-gray-900 break-words">{{ $m['pic'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-gray-400">MAK</dt>
                                        <dd class="text-sm text-gray-900 break-all">{{ $m['mak'] ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-[11px] text-gray-400">Nominal</dt>
                                        <dd class="text-sm font-semibold text-gray-900">Rp
                                            {{ number_format((int) $m['nominal_memo'], 0, ',', '.') }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button type="button" @click="unduhSatu(@js($keys))"
                                        class="{{ $btnPrimary }} !h-8 !px-3 !text-xs">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Unduh memo ini ({{ count($keys) }})
                                    </button>
                                    <button type="button" @click="buka = !buka" class="{{ $btn }} !h-8 !px-3 !text-xs">
                                        <span x-text="buka ? 'Sembunyikan dokumen' : 'Lihat dokumen'"></span>
                                        <svg class="h-3.5 w-3.5 transition-transform" :class="buka ? 'rotate-180' : ''"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="buka" x-cloak class="border-t border-gray-200 bg-gray-50 px-4 sm:px-5 py-4">
                            <h4 class="text-xs font-semibold text-gray-500 mb-2">Dokumen dalam memo ini</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach ($g['docs'] as $d)
                                    <label
                                        class="flex items-center gap-2.5 bg-white border border-gray-200 rounded-lg px-3 py-2 cursor-pointer hover:border-gray-400 transition">
                                        <input type="checkbox" name="keys[]" value="{{ $d['key'] }}" x-model="dipilih"
                                            :disabled="dipilih.length >= maks && !dipilih.includes('{{ $d['key'] }}')"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600 shrink-0">
                                        <span class="min-w-0">
                                            <span
                                                class="block text-[13px] font-semibold text-gray-800">{{ $d['jenis_dok'] }}</span>
                                            @if ($d['pegawai'])
                                                <span class="block text-xs text-gray-500 break-words">{{ $d['pegawai'] }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="{{ $card }} px-6 py-14 text-center text-sm text-gray-400">
                        Tidak ada dokumen yang cocok dengan filter.
                    </div>
                @endforelse

                @if ($totalMemo > $grup->count())
                    <p class="text-center text-xs text-gray-400">Menampilkan {{ $grup->count() }} memo teratas dari
                        {{ $totalMemo }}. Persempit dengan filter.</p>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>