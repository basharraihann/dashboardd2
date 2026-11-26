{{--
Pemilih item anggaran (opsional) untuk step 3 agenda (Anggaran & Memo).
Satu agenda punya dua memo (PNS dan Non PNS) dengan total berbeda, jadi item dipilih per memo.
Jumlah semua item pada satu memo harus sama dengan total memo tersebut.
Daftar item diambil dari FA aktif lewat route memo.item-mak (role-nya sama dengan step ini).
Dipakai di: agendas/partials/administrasi-anggaran.blade.php
--}}
@php
    $statusItem = ($agenda ?? null) ? $agenda->pegawai->map(fn($p) => $p->status_kepegawaian ?? 'PNS') : collect();
    $totalPnsItem = (int) ($totalAsn ?? ($agenda->biaya_asn ?? 0));
    $totalNonPnsItem = (int) ($totalNonAsn ?? ($agenda->biaya_non_asn ?? 0));

    $seksiItem = [];
    if (($statusItem->isEmpty() || $statusItem->contains('PNS')) && $totalPnsItem > 0) {
        $seksiItem[] = [
            'field' => 'item_mak_pns',
            'judul' => 'Memo PNS',
            'total' => $totalPnsItem,
            'awal' => old('item_mak_pns', $agenda->item_mak_pns ?? []),
        ];
    }
    if (($statusItem->isEmpty() || $statusItem->contains('Non PNS')) && $totalNonPnsItem > 0) {
        $seksiItem[] = [
            'field' => 'item_mak_non_pns',
            'judul' => 'Memo Non PNS',
            'total' => $totalNonPnsItem,
            'awal' => old('item_mak_non_pns', $agenda->item_mak_non_pns ?? []),
        ];
    }
@endphp

@if (count($seksiItem))
    <section class="bg-white shadow-sm rounded-2xl border border-gray-100">
        <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100">
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
            </span>
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-gray-800">Item Anggaran</h3>
                <p class="text-xs text-gray-400">Opsional. Pilih satu atau lebih item dari MAK di atas. Jumlah item
                    harus sama dengan total memo.</p>
            </div>
        </div>

        <div class="p-5 space-y-6">
            @foreach ($seksiItem as $sk)
                <div data-item-picker data-field="{{ $sk['field'] }}" data-total="{{ $sk['total'] }}"
                    data-awal="{{ json_encode(is_array($sk['awal']) ? array_values($sk['awal']) : []) }}">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-medium text-gray-700">{{ $sk['judul'] }}</h4>
                        <span class="text-xs text-gray-400">Total memo Rp {{ number_format($sk['total'], 0, ',', '.') }}</span>
                    </div>

                    <p data-status class="text-xs text-gray-400">Pilih MAK terlebih dahulu.</p>
                    <div data-rows class="space-y-2"></div>
                    <button type="button" data-add
                        class="hidden mt-2 text-sm text-blue-600 font-medium hover:text-blue-700 transition">+ Tambah item</button>

                    <div data-summary class="hidden mt-3 flex items-center justify-between text-xs border-t border-gray-100 pt-3">
                        <span class="text-gray-500">Teralokasi Rp <span data-sum class="font-semibold">0</span> dari Rp
                            {{ number_format($sk['total'], 0, ',', '.') }}</span>
                        <span data-selisih class="font-medium"></span>
                    </div>

                    @error($sk['field'])
                        <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>
    </section>

    <script>
        (function () {
            const makInput = document.getElementById('mak-input');
            const pickers = Array.from(document.querySelectorAll('[data-item-picker]'));
            if (!makInput || !pickers.length) return;

            const URL_ITEM = @json(route('memo.item-mak'));
            const FORMAT_MAK = /^[^.\s]+(\.[^.\s]+){5}$/;
            const cls = 'w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none transition bg-white';

            let items = [];
            let makAktif = '';
            let timer = null;

            function fmt(v) { return String(v).replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
            function angka(v) { return parseInt(String(v).replace(/\D/g, ''), 10) || 0; }

            function Picker(root) {
                const field = root.dataset.field;
                const total = parseInt(root.dataset.total, 10) || 0;
                let awal = [];
                try { awal = JSON.parse(root.dataset.awal || '[]'); } catch (e) { awal = []; }
                const rowsEl = root.querySelector('[data-rows]');
                const addBtn = root.querySelector('[data-add]');
                const statusEl = root.querySelector('[data-status]');
                const summary = root.querySelector('[data-summary]');
                const sumEl = root.querySelector('[data-sum]');
                const selisihEl = root.querySelector('[data-selisih]');

                const api = {
                    root: root, total: total,
                    jumlahBaris: function () { return rowsEl.children.length; },
                    jumlahTotal: function () {
                        return Array.from(rowsEl.querySelectorAll('input[data-jumlah]'))
                            .reduce(function (a, el) { return a + angka(el.value); }, 0);
                    },
                };

                function opsiTeks(it) {
                    return it.kode + '. ' + it.uraian + (typeof it.sisa === 'number' ? ' (sisa FA Rp' + fmt(Math.max(it.sisa, 0)) + ')' : '');
                }

                function isiOpsi(select, terpilih) {
                    const dipakaiLain = new Set(
                        Array.from(rowsEl.querySelectorAll('select')).filter(function (s) { return s !== select && s.value; })
                            .map(function (s) { return s.value; })
                    );
                    select.innerHTML = '';
                    const kosong = document.createElement('option');
                    kosong.value = ''; kosong.textContent = 'Pilih item...';
                    select.appendChild(kosong);
                    let ada = false;
                    items.forEach(function (it) {
                        if (dipakaiLain.has(it.kode)) return;
                        const o = document.createElement('option');
                        o.value = it.kode; o.textContent = opsiTeks(it);
                        if (it.kode === terpilih) { o.selected = true; ada = true; }
                        select.appendChild(o);
                    });
                    if (terpilih && !ada) {  // item tersimpan yang sudah tidak ada di FA aktif tetap tampil
                        const o = document.createElement('option');
                        o.value = terpilih; o.textContent = terpilih; o.selected = true;
                        select.appendChild(o);
                    }
                }

                api.refreshOpsi = function () {
                    rowsEl.querySelectorAll('select').forEach(function (s) { isiOpsi(s, s.value); });
                };

                function beriNama() {
                    Array.from(rowsEl.children).forEach(function (row, i) {
                        row.querySelector('select').name = field + '[' + i + '][kode]';
                        row.querySelector('input[data-uraian]').name = field + '[' + i + '][uraian]';
                        row.querySelector('input[data-jumlah]').name = field + '[' + i + '][jumlah]';
                    });
                }

                function ringkas() {
                    const n = api.jumlahBaris();
                    summary.classList.toggle('hidden', n === 0);
                    if (n === 0) return;
                    const sum = api.jumlahTotal();
                    sumEl.textContent = fmt(sum);
                    const selisih = total - sum;
                    if (selisih === 0) {
                        selisihEl.textContent = 'Sudah sesuai';
                        selisihEl.className = 'font-medium text-emerald-600';
                    } else if (selisih > 0) {
                        selisihEl.textContent = 'Kurang Rp' + fmt(selisih);
                        selisihEl.className = 'font-medium text-red-500';
                    } else {
                        selisihEl.textContent = 'Lebih Rp' + fmt(-selisih);
                        selisihEl.className = 'font-medium text-red-500';
                    }
                }
                api.ringkas = ringkas;

                function tambahBaris(data, isiOtomatis) {
                    data = data || {};
                    const row = document.createElement('div');
                    row.className = 'flex flex-col sm:flex-row gap-2 sm:items-center';

                    const select = document.createElement('select');
                    select.required = true;
                    select.className = cls + ' sm:flex-1';
                    isiOpsi(select, data.kode || '');

                    const uraian = document.createElement('input');
                    uraian.type = 'hidden'; uraian.setAttribute('data-uraian', '1');
                    uraian.value = data.uraian || '';

                    const wrap = document.createElement('div');
                    wrap.className = 'relative sm:w-48';
                    const rp = document.createElement('span');
                    rp.className = 'absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none';
                    rp.textContent = 'Rp';
                    const jumlah = document.createElement('input');
                    jumlah.type = 'text'; jumlah.inputMode = 'numeric'; jumlah.required = true;
                    jumlah.placeholder = 'Jumlah'; jumlah.setAttribute('data-jumlah', '1');
                    jumlah.className = 'w-full border border-gray-200 rounded-lg pl-9 pr-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none transition bg-white';
                    jumlah.value = data.jumlah ? fmt(data.jumlah) : '';
                    wrap.appendChild(rp); wrap.appendChild(jumlah);

                    const hapus = document.createElement('button');
                    hapus.type = 'button';
                    hapus.className = 'text-sm text-red-500 hover:text-red-600 px-2 py-2.5 transition';
                    hapus.textContent = 'Hapus';

                    select.addEventListener('change', function () {
                        const it = items.find(function (x) { return x.kode === select.value; });
                        uraian.value = it ? it.uraian : '';
                        api.refreshOpsi();
                    });
                    jumlah.addEventListener('input', function () { jumlah.value = fmt(jumlah.value); ringkas(); });
                    hapus.addEventListener('click', function () { row.remove(); beriNama(); api.refreshOpsi(); ringkas(); });

                    row.appendChild(select); row.appendChild(uraian); row.appendChild(wrap); row.appendChild(hapus);
                    rowsEl.appendChild(row);
                    beriNama();

                    // Baris baru langsung diisi sisa yang belum teralokasi, tinggal pilih item.
                    if (isiOtomatis && !data.jumlah) {
                        const sisa = total - (api.jumlahTotal() - angka(jumlah.value));
                        if (sisa > 0) jumlah.value = fmt(sisa);
                    }
                }

                api.kosongkan = function () { rowsEl.innerHTML = ''; ringkas(); };

                api.pesan = function (teks, tampilTombol) {
                    statusEl.textContent = teks;
                    statusEl.classList.toggle('hidden', !teks);
                    addBtn.classList.toggle('hidden', !tampilTombol);
                };

                addBtn.addEventListener('click', function () { tambahBaris({}, true); api.refreshOpsi(); ringkas(); });

                api.muatAwal = function () { awal.forEach(function (d) { tambahBaris(d, false); }); ringkas(); };
                return api;
            }

            const daftarPicker = pickers.map(Picker);

            function muatItem(mak) {
                if (!FORMAT_MAK.test(mak)) {
                    items = [];
                    daftarPicker.forEach(function (p) { p.refreshOpsi(); p.pesan('Pilih MAK terlebih dahulu.', false); });
                    return;
                }
                daftarPicker.forEach(function (p) { p.pesan('Memuat item...', false); });

                fetch(URL_ITEM + '?mak=' + encodeURIComponent(mak), { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (makInput.value.trim() !== mak) return;   // MAK sudah berganti lagi
                        items = d.items || [];
                        daftarPicker.forEach(function (p) {
                            p.refreshOpsi();
                            if (items.length === 0) {
                                p.pesan('Tidak ada data item untuk MAK ini. Memo tetap bisa disimpan tanpa item.', false);
                            } else {
                                p.pesan('', true);
                            }
                        });
                    })
                    .catch(function () {
                        daftarPicker.forEach(function (p) { p.pesan('Gagal memuat item. Memo tetap bisa disimpan tanpa item.', false); });
                    });
            }

            // Ganti MAK -> daftar item dimuat ulang, pilihan item sebelumnya dibuang.
            makInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    const mak = makInput.value.trim();
                    if (mak === makAktif) return;
                    makAktif = mak;
                    daftarPicker.forEach(function (p) { p.kosongkan(); });
                    muatItem(mak);
                }, 350);
            });

            const form = makInput.closest('form');
            if (form) {
                form.addEventListener('submit', function (e) {
                    // Jumlah item harus sama dengan total memo (server juga memeriksa).
                    for (const p of daftarPicker) {
                        if (p.jumlahBaris() > 0 && p.jumlahTotal() !== p.total) {
                            e.preventDefault();
                            p.ringkas();
                            p.root.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                    }
                    form.querySelectorAll('[data-item-picker] input[data-jumlah]').forEach(function (el) {
                        el.value = el.value.replace(/\./g, '');   // kirim angka murni
                    });
                });
            }

            makAktif = makInput.value.trim();
            daftarPicker.forEach(function (p) { p.muatAwal(); });
            muatItem(makAktif);
        })();
    </script>
@endif
