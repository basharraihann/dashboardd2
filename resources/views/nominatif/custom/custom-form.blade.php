<x-app-layout title="Nominatif Custom">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-blue-600 flex items-center justify-center shadow-sm shadow-blue-600/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
            </div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ ($nominatif ?? null) ? 'Edit Nominatif Custom' : 'Buat Nominatif Custom' }}
            </h2>
        </div>
    </x-slot>

    @php
        $nominatif = $nominatif ?? null;

        // ----- Konteks memo (opsional) -----
        $isEdit = $nominatif !== null;
        $memo = $memo ?? ($nominatif?->memo ?? null);
        $prefill = $prefill ?? [];

        // old() > data nominatif (edit) > prefill dari memo > default
        $awal = fn(string $field, $default = null) => old($field, $nominatif->{$field} ?? ($prefill[$field] ?? $default));

        $tglPrefill = $prefill['tanggal'] ?? null;
        $tanggalAwal = old(
            'tanggal',
            $nominatif?->tanggal
            ? $nominatif->tanggal->toDateString()
            : ($tglPrefill ? \Carbon\Carbon::parse($tglPrefill)->toDateString() : now()->toDateString()),
        );

        $ppkList = $pegawaiList->filter(fn($p) => str_contains($p->role_penandatangan ?? '', 'PPK'))->values();
        if ($ppkList->isEmpty()) {
            $ppkList = $pegawaiList;
        }

        $bendaharaList = $pegawaiList->filter(fn($p) => str_contains($p->role_penandatangan ?? '', 'Bendahara'))->values();
        if ($bendaharaList->isEmpty()) {
            $bendaharaList = $pegawaiList;
        }

        $penanggungJawabList = $pegawaiList->filter(fn($p) => str_contains($p->role_penandatangan ?? '', 'Penanggung Jawab'))->values();
        if ($penanggungJawabList->isEmpty()) {
            $penanggungJawabList = $pegawaiList;
        }

        // Item awal: dari old() kalau validasi gagal, dari database kalau edit, atau satu baris kosong
        $rows = old('items');
        if ($rows === null) {
            $rows = $nominatif
                ? $nominatif->items->map(fn($i) => [
                    'uraian' => $i->uraian,
                    'harga' => $i->harga,
                    'jumlah' => $i->jumlah,
                    'satuan' => $i->satuan,
                    'harga_termasuk_ppn' => (bool) ($i->harga_termasuk_ppn ?? false),
                    'ppn_persen' => $i->ppn_persen,
                    'pph22_persen' => $i->pph22_persen,
                    'pph23_persen' => $i->pph23_persen,
                ])->values()->all()
                : [];
        }
        if (empty($rows)) {
            $rows = [['jumlah' => 1, 'satuan' => 'PKT', 'ppn_persen' => 11]];
        }

        $inputClass = 'w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition placeholder:text-gray-300';
        $selectClass = 'w-full appearance-none bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition';
        $labelClass = 'block text-xs font-semibold text-gray-500 mb-1.5 tracking-wide uppercase';
        $subLabel = 'block text-xs text-gray-400 mb-1.5';
        $chevron = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>';
    @endphp

    {{-- Latar halaman --}}
    <div class="bg-gradient-to-b from-gray-50/70 to-white min-h-full">
        {{-- Wrapper konten: satu-satunya sumber padding vertikal --}}
        <div class="py-6 sm:py-8 px-4 sm:px-6 lg:px-8 max-w-screen-2xl mx-auto">

            @if ($errors->any())
                <div
                    class="flex gap-2.5 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl mb-5 animate-[fadeIn_.2s_ease-out]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 mt-0.5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Banner konteks memo --}}
            @if ($memo)
                <div
                    class="flex gap-2.5 bg-blue-50/70 border border-blue-100 text-blue-700 text-xs sm:text-sm px-4 py-3 rounded-xl mb-5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 mt-0.5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <p>
                        Nominatif untuk memo <span class="font-mono font-semibold">{{ $memo->nomor_memo }}</span>
                        @if ($memo->uraian_kegiatan)
                            &mdash; {{ $memo->uraian_kegiatan }}
                        @endif
                        @unless ($isEdit)
                            <span class="block text-blue-600/80 mt-0.5">Isian di bawah terisi otomatis dari memo, boleh
                                diubah.</span>
                        @endunless
                    </p>
                </div>
            @endif

            {{-- Grid 5 bagian: form (2) + preview (3). x-data di sini supaya preview bisa baca `rows`. --}}
            <div class="nom-grid" x-data="customForm(@js(array_values($rows)))">

                <form action="{{ $action }}" method="POST" class="nom-form space-y-5 min-w-0">
                    @csrf
                    @if ($method !== 'POST')
                        @method($method)
                    @endif

                    {{-- Hanya saat buat. Saat edit, memo tidak boleh dipindah. --}}
                    @if ($memo && !$isEdit)
                        <input type="hidden" name="memo_entry_id" value="{{ $memo->id }}">
                    @endif

                    {{-- ===== Section: Informasi Nominatif ===== --}}
                    <div
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-5 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-6">
                            <div class="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800">Informasi Nominatif</h3>
                                <p class="text-xs text-gray-400">Judul dan tanggal yang tampil di dokumen</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="{{ $labelClass }}">Judul Nominatif <span
                                        class="text-red-400">*</span></label>
                                <input type="text" name="uraian_kegiatan" value="{{ $awal('uraian_kegiatan', '') }}"
                                    required placeholder="Pembayaran Fullboard Hotel ... dalam rangka ..."
                                    class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Tanggal <span class="text-red-400">*</span></label>
                                <input type="date" name="tanggal" value="{{ $tanggalAwal }}" required
                                    class="{{ $inputClass }}">
                            </div>
                        </div>
                    </div>

                    {{-- ===== Section: Penandatangan ===== --}}
                    <div
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow p-5 sm:p-6">
                        <div class="flex items-center gap-2.5 mb-6">
                            <div class="h-8 w-8 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 3c0-1.657-3.134-3-7-3s-7 1.343-7 3v2h14v-2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800">Penandatangan</h3>
                                <p class="text-xs text-gray-400">Pegawai yang menandatangani dokumen</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="{{ $subLabel }}">PPK <span class="text-red-400">*</span></label>
                                <div class="relative">
                                    <select name="ppk_id" required data-placeholder="Cari PPK..."
                                        class="js-searchable {{ $selectClass }}">
                                        <option value="" disabled {{ $awal('ppk_id') ? '' : 'selected' }}>— Pilih —
                                        </option>
                                        @foreach ($ppkList as $p)
                                            <option value="{{ $p->id }}" data-nip="{{ $p->nip }}"
                                                @selected($awal('ppk_id') == $p->id)>
                                                {{ $p->nama_gelar ?? $p->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    {!! $chevron !!}
                                </div>
                            </div>
                            <div>
                                <label class="{{ $subLabel }}">Bendahara <span class="text-red-400">*</span></label>
                                <div class="relative">
                                    <select name="bendahara_id" required data-placeholder="Cari Bendahara..."
                                        class="js-searchable {{ $selectClass }}">
                                        <option value="" disabled {{ $awal('bendahara_id') ? '' : 'selected' }}>
                                            — Pilih —</option>
                                        @foreach ($bendaharaList as $p)
                                            <option value="{{ $p->id }}" data-nip="{{ $p->nip }}"
                                                @selected($awal('bendahara_id') == $p->id)>
                                                {{ $p->nama_gelar ?? $p->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    {!! $chevron !!}
                                </div>
                            </div>
                            <div>
                                <label class="{{ $subLabel }}">Penanggung Jawab Kegiatan <span
                                        class="text-red-400">*</span></label>
                                <div class="relative">
                                    <select name="penanggung_jawab_id" required data-placeholder="Cari pegawai..."
                                        class="js-searchable {{ $selectClass }}">
                                        <option value="" disabled {{ $awal('penanggung_jawab_id') ? '' : 'selected' }}>—
                                            Pilih —</option>
                                        @foreach ($penanggungJawabList as $p)
                                            <option value="{{ $p->id }}" data-nip="{{ $p->nip }}"
                                                @selected($awal('penanggung_jawab_id') == $p->id)>
                                                {{ $p->nama_gelar ?? $p->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    {!! $chevron !!}
                                </div>
                            </div>
                            <div>
                                <label class="{{ $subLabel }}">PIC <span class="text-red-400">*</span></label>
                                <div class="relative">
                                    <select name="pic_id" required data-placeholder="Cari PIC..."
                                        class="js-searchable {{ $selectClass }}">
                                        <option value="" disabled {{ $awal('pic_id') ? '' : 'selected' }}>
                                            — Pilih —</option>
                                        @foreach ($pegawaiList as $p)
                                            <option value="{{ $p->id }}" @selected($awal('pic_id') == $p->id)>
                                                {{ $p->nama_gelar ?? $p->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                    {!! $chevron !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ===== Section: Item (satu kolom + preset pajak) ===== --}}
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3 mb-5">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="h-8 w-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-600" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-800">Rincian Item</h3>
                                    <p class="text-xs text-gray-400">Isi harga sesuai faktur pajak (Harga Jual) atau
                                        nominal total. Pajak dihitung dari DPP.</p>
                                </div>
                            </div>
                            <span
                                class="flex-shrink-0 text-xs font-medium text-amber-700 bg-amber-50 rounded-full px-2.5 py-1"
                                x-text="rows.length + ' item'"></span>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(row, i) in rows" :key="row.k">
                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 space-y-4">

                                    {{-- Judul item + hapus --}}
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wide"
                                            x-text="'Item ' + (i + 1)"></span>
                                        <button type="button" @click="removeRow(i)" x-show="rows.length > 1"
                                            class="text-xs text-rose-500 hover:text-rose-700 font-medium">Hapus</button>
                                    </div>

                                    {{-- Uraian --}}
                                    <div>
                                        <label class="{{ $subLabel }}">Uraian <span
                                                class="text-red-400">*</span></label>
                                        <textarea rows="2" :name="'items[' + i + '][uraian]'" x-model="row.uraian"
                                            required placeholder="Paket Fullboard Meeting Ibis Styles Bogor Pajajaran"
                                            class="{{ $inputClass }} bg-white resize-y"></textarea>
                                    </div>

                                    {{-- Harga x Jumlah Satuan --}}
                                    <div class="flex flex-wrap items-end gap-3">
                                        <div class="flex-1 min-w-[160px]">
                                            <label class="{{ $subLabel }}">Harga <span
                                                    class="text-red-400">*</span></label>
                                            <div class="relative">
                                                <span
                                                    class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400">Rp</span>
                                                {{-- Input tampilan (berformat titik ribuan, tidak dikirim) --}}
                                                <input type="text" inputmode="numeric" :value="fmt(row.harga)"
                                                    @input="setHarga(row, $event)" autocomplete="off" required
                                                    placeholder="0"
                                                    class="w-full border border-gray-200 bg-white rounded-xl pl-9 pr-3 py-2.5 text-sm text-right tabular-nums focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none">
                                                {{-- Nilai mentah (angka saja) yang dikirim ke server --}}
                                                <input type="hidden" :name="'items[' + i + '][harga]'"
                                                    :value="row.harga">
                                            </div>
                                        </div>
                                        <span class="pb-3 text-gray-400 text-sm">&times;</span>
                                        <div class="w-24">
                                            <label class="{{ $subLabel }}">Jumlah</label>
                                            <input type="number" min="0.01" step="0.01" inputmode="decimal"
                                                :name="'items[' + i + '][jumlah]'" x-model="row.jumlah" required
                                                class="{{ $inputClass }} bg-white text-center">
                                        </div>
                                        <div class="w-28">
                                            <label class="{{ $subLabel }}">Satuan</label>
                                            <input type="text" :name="'items[' + i + '][satuan]'" x-model="row.satuan"
                                                autocomplete="off" placeholder="PKT" class="{{ $inputClass }} bg-white">
                                        </div>
                                    </div>

                                    {{-- Jenis harga: di luar PPN / sudah termasuk PPN --}}
                                    <div>
                                        <label class="{{ $subLabel }}">Jenis harga</label>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" @click="row.inklusif = false"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition"
                                                :class="!row.inklusif ?
                                                    'bg-blue-600 border-blue-600 text-white' :
                                                    'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                                                Di luar PPN (Harga Jual faktur)
                                            </button>
                                            <button type="button" @click="row.inklusif = true"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition"
                                                :class="row.inklusif ?
                                                    'bg-blue-600 border-blue-600 text-white' :
                                                    'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                                                Sudah termasuk PPN
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Preset pajak --}}
                                    <div class="space-y-3">
                                        <div>
                                            <label class="{{ $subLabel }}">PPN</label>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="p in [0, 11]" :key="'ppn' + p">
                                                    <button type="button" @click="row.ppn_persen = p"
                                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition"
                                                        :class="pct(row.ppn_persen) === p ?
                                                            'bg-blue-600 border-blue-600 text-white' :
                                                            'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                                                        x-text="p + '%'"></button>
                                                </template>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="{{ $subLabel }}">PPh 22 (barang/ATK)</label>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="p in [0, 1.5, 3]" :key="'p22' + p">
                                                    <button type="button" @click="row.pph22_persen = p"
                                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition"
                                                        :class="pct(row.pph22_persen) === p ?
                                                            'bg-blue-600 border-blue-600 text-white' :
                                                            'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                                                        x-text="p + '%'"></button>
                                                </template>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="{{ $subLabel }}">PPh 23 (konsumsi/jasa)</label>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="p in [0, 2, 4]" :key="'p23' + p">
                                                    <button type="button" @click="row.pph23_persen = p"
                                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition"
                                                        :class="pct(row.pph23_persen) === p ?
                                                            'bg-blue-600 border-blue-600 text-white' :
                                                            'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                                                        x-text="p + '%'"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Hidden: nilai pajak tetap terkirim --}}
                                    <input type="hidden" :name="'items[' + i + '][harga_termasuk_ppn]'"
                                        :value="row.inklusif ? 1 : 0">
                                    <input type="hidden" :name="'items[' + i + '][ppn_persen]'" :value="row.ppn_persen">
                                    <input type="hidden" :name="'items[' + i + '][pph22_persen]'"
                                        :value="row.pph22_persen">
                                    <input type="hidden" :name="'items[' + i + '][pph23_persen]'"
                                        :value="row.pph23_persen">

                                    {{-- Rincian per item (urutan kolom sama dengan dokumen nominatif) --}}
                                    <div class="pt-3 border-t border-gray-200 space-y-1 text-xs text-gray-500">
                                        <div class="flex justify-between">
                                            <span>Sub Total</span>
                                            <span class="tabular-nums" x-text="'Rp ' + rupiah(bruto(row))"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>PPh 22</span>
                                            <span class="tabular-nums" x-text="'- Rp ' + rupiah(pph22(row))"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>PPh 23</span>
                                            <span class="tabular-nums" x-text="'- Rp ' + rupiah(pph23(row))"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>PPN</span>
                                            <span class="tabular-nums" x-text="'- Rp ' + rupiah(ppn(row))"></span>
                                        </div>
                                        <div
                                            class="flex items-center justify-between pt-2 mt-1 border-t border-dashed border-gray-300">
                                            <span class="text-xs font-semibold text-gray-600">Total</span>
                                            <span class="text-base font-bold text-gray-900 tabular-nums"
                                                x-text="'Rp ' + rupiah(total(row))"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="addRow()"
                            class="mt-4 w-full inline-flex items-center justify-center gap-1.5 border border-dashed border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold px-3.5 py-3 rounded-xl text-xs transition">
                            + Tambah Item
                        </button>

                        {{-- Ringkasan total --}}
                        <div class="mt-8 rounded-2xl bg-gray-50/80 border border-gray-100 p-5 sm:p-6">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Ringkasan Total
                            </p>

                            <div class="bg-white border border-gray-100 rounded-xl overflow-hidden text-sm">
                                <div class="flex justify-between px-5 py-3">
                                    <span class="text-gray-500">Sub Total</span>
                                    <span class="tabular-nums font-medium text-gray-900"
                                        x-text="'Rp ' + rupiah(sum('bruto'))"></span>
                                </div>
                                <div class="flex justify-between px-5 py-3 border-t border-gray-100">
                                    <span class="text-gray-500">PPh 22</span>
                                    <span class="tabular-nums text-gray-900"
                                        x-text="'- Rp ' + rupiah(sum('pph22'))"></span>
                                </div>
                                <div class="flex justify-between px-5 py-3 border-t border-gray-100">
                                    <span class="text-gray-500">PPh 23</span>
                                    <span class="tabular-nums text-gray-900"
                                        x-text="'- Rp ' + rupiah(sum('pph23'))"></span>
                                </div>
                                <div class="flex justify-between px-5 py-3 border-t border-gray-100">
                                    <span class="text-gray-500">PPN</span>
                                    <span class="tabular-nums text-gray-900"
                                        x-text="'- Rp ' + rupiah(sum('ppn'))"></span>
                                </div>
                                <div class="flex justify-between px-5 py-3 border-t border-gray-100 bg-gray-50">
                                    <span class="font-medium text-gray-600">PPh 22 &amp; PPh 23</span>
                                    <span class="tabular-nums font-medium text-gray-900"
                                        x-text="'Rp ' + rupiah(sum('pph'))"></span>
                                </div>
                                <div class="flex justify-between px-5 py-4 border-t border-gray-200 bg-blue-50/60">
                                    <span class="font-bold text-gray-800">TOTAL</span>
                                    <span class="text-base font-bold text-gray-900 tabular-nums"
                                        x-text="'Rp ' + rupiah(sum('total'))"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div
                        class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 bg-white rounded-2xl border border-gray-100 shadow-sm px-5 sm:px-6 py-4">
                        <a href="{{ $memo && !$isEdit ? route('memo.index') : route('nominatif.index') }}"
                            class="w-full sm:w-auto text-center text-sm text-gray-400 hover:text-gray-600 transition">
                            &larr; Batal
                        </a>
                        <button type="submit"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white font-semibold px-5 py-2.5 rounded-xl text-sm shadow-md shadow-blue-600/20 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $submitLabel }}
                        </button>
                    </div>
                </form>

                {{-- Preview (kolom kanan, 3 bagian) --}}
                @include('nominatif.custom._preview-custom')
            </div>
        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Layout form + preview. CSS biasa (bukan Tailwind) supaya tidak tergantung build.
           Di bawah 1180px: satu kolom, preview disembunyikan.
           Mulai 1180px: 5 kolom (form 2, preview 3). */
        .nom-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.5rem;
            align-items: start;
        }

        @media (min-width: 1180px) {
            .nom-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }

            .nom-grid>.nom-form {
                grid-column: span 2 / span 2;
            }

            .nom-grid>.nom-aside {
                grid-column: span 3 / span 3;
            }
        }

        /* Matikan panah bawaan browser di semua <select>, cukup panah custom (svg) */
        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: none !important;
        }

        select::-ms-expand {
            display: none;
        }
    </style>

    <script>
        document.addEventListener('alpine:init', function () {
            Alpine.data('customForm', function (initial) {
                return {
                    seq: 0,
                    rows: [],

                    init() {
                        this.rows = initial.map((r) => this.baris(r));
                    },

                    // Bentuk seragam untuk satu baris item
                    baris(r) {
                        r = r || {};
                        return {
                            k: ++this.seq,
                            uraian: r.uraian || '',
                            harga: this.digits(r.harga),
                            jumlah: (r.jumlah === undefined || r.jumlah === null || r.jumlah === '') ? 1 : r.jumlah,
                            satuan: r.satuan || '',
                            // true = harga sudah termasuk PPN, false = harga di luar PPN
                            inklusif: r.harga_termasuk_ppn === true || r.harga_termasuk_ppn === 1 || r.harga_termasuk_ppn === '1',
                            ppn_persen: r.ppn_persen ?? 11,
                            pph22_persen: r.pph22_persen ?? 0,
                            pph23_persen: r.pph23_persen ?? 0,
                        };
                    },

                    addRow() {
                        this.rows.push(this.baris({ satuan: 'PKT', ppn_persen: 11 }));
                        this.$nextTick(() => {
                            const inputs = this.$root.querySelectorAll('textarea[name$="[uraian]"]');
                            if (inputs.length) inputs[inputs.length - 1].focus();
                        });
                    },

                    removeRow(i) {
                        if (this.rows.length > 1) this.rows.splice(i, 1);
                    },

                    digits(v) {
                        return String(v === null || v === undefined ? '' : v).replace(/\D/g, '');
                    },

                    // Angka dengan titik ribuan; kosong tetap kosong
                    fmt(v) {
                        const d = this.digits(v);
                        return d === '' ? '' : Number(d).toLocaleString('id-ID');
                    },

                    rupiah(n) {
                        return Number(n || 0).toLocaleString('id-ID');
                    },

                    setHarga(row, e) {
                        row.harga = this.digits(e.target.value);
                        e.target.value = this.fmt(row.harga);
                    },

                    pct(v) {
                        return parseFloat(String(v === null || v === undefined ? '' : v).replace(',', '.')) || 0;
                    },

                    // Nominal harga x jumlah, apa adanya (belum dipisah DPP/PPN)
                    sub(row) {
                        return Math.round((Number(this.digits(row.harga)) || 0) * (parseFloat(row.jumlah) || 0));
                    },

                    // DPP:
                    //  - harga di luar PPN   : DPP = sub
                    //  - harga sudah termasuk: DPP = sub / (1 + PPN%), mis. sub / 1,11
                    dpp(row) {
                        const s = this.sub(row);
                        const p = this.pct(row.ppn_persen);
                        if (row.inklusif && p > 0) {
                            return Math.round(s / (1 + p / 100));
                        }
                        return s;
                    },

                    // PPN:
                    //  - inklusif : sub - DPP (supaya DPP + PPN = sub, tanpa selisih pembulatan)
                    //  - eksklusif: DPP x PPN%
                    ppn(row) {
                        const p = this.pct(row.ppn_persen);
                        if (row.inklusif && p > 0) {
                            return this.sub(row) - this.dpp(row);
                        }
                        return Math.round(this.dpp(row) * p / 100);
                    },

                    // PPh selalu dari DPP
                    pph22(row) {
                        return Math.round(this.dpp(row) * this.pct(row.pph22_persen) / 100);
                    },
                    pph23(row) {
                        return Math.round(this.dpp(row) * this.pct(row.pph23_persen) / 100);
                    },

                    // Gabungan PPh 22 + PPh 23, untuk baris "PPh 22 & PPh 23"
                    pph(row) {
                        return this.pph22(row) + this.pph23(row);
                    },

                    // Total bersih diterima vendor = Sub total - PPN - PPh 22 - PPh 23
                    // (sama dengan DPP - PPh, sesuai format dokumen nominatif)
                    total(row) {
                        return this.bruto(row) - this.ppn(row) - this.pph22(row) - this.pph23(row);
                    },

                    // Nilai tagihan termasuk PPN (DPP + PPN). Dipakai untuk kolom
                    // Sub Total di preview dan cetak, supaya tabel terbaca:
                    // Sub Total - PPN - PPh = Total
                    bruto(row) {
                        return this.dpp(row) + this.ppn(row);
                    },

                    // Harga satuan termasuk PPN untuk kolom Harga di preview/cetak
                    hargaBruto(row) {
                        const j = parseFloat(row.jumlah) || 0;
                        return j > 0 ? Math.round(this.bruto(row) / j) : 0;
                    },

                    sum(fn) {
                        return this.rows.reduce((s, r) => s + this[fn](r), 0);
                    },
                };
            });
        });

    </script>
</x-app-layout>