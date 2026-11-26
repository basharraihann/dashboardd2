{{--
Pemilih item anggaran (opsional) untuk form memo.
Muncul setelah MAK dipilih; daftar item diambil dari FA aktif lewat route memo.item-mak.
Kalau ada item terpilih, kolom Nominal otomatis = total jumlah item (dikunci).
Dipakai di: memo/_form.blade.php. Variabel: $isEdit, $memo (edit).
--}}
@php
    $itemAwal = old('item_mak', $isEdit ? ($memo->item_mak ?? []) : []);
    $itemAwal = is_array($itemAwal) ? array_values($itemAwal) : [];
@endphp

<div id="item-mak-card"
    class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow  p-5 sm:p-6">
    <div class="flex items-center gap-2.5 mb-5">
        <div class="h-8 w-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-gray-800">Item Anggaran</h3>
            <p class="text-xs text-gray-400">Opsional. Pilih satu atau lebih item dari MAK di atas, Nominal otomatis
                mengikuti totalnya.</p>
        </div>
    </div>

    <p id="item-mak-status" class="text-xs text-gray-400">Pilih MAK terlebih dahulu.</p>

    <div id="item-mak-rows" class="space-y-3"></div>

    <button type="button" id="item-mak-add"
        class="hidden mt-3 text-sm text-blue-600 font-medium hover:text-blue-700 transition">+ Tambah item</button>

    <div id="item-mak-total"
        class="hidden mt-4 flex items-center justify-between border-t border-gray-100 pt-4 text-sm">
        <span class="text-gray-500">Total item</span>
        <span class="font-semibold text-gray-800">Rp <span id="item-mak-total-val">0</span></span>
    </div>

    @error('item_mak')
        <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
    @enderror
</div>

<script>
    (function () {
        const makInput = document.getElementById('mak-input');
        const nominal = document.getElementById('nominal-input');
        const rowsEl = document.getElementById('item-mak-rows');
        const addBtn = document.getElementById('item-mak-add');
        const statusEl = document.getElementById('item-mak-status');
        const totalBox = document.getElementById('item-mak-total');
        const totalVal = document.getElementById('item-mak-total-val');
        if (!makInput || !nominal || !rowsEl) return;

        const URL_ITEM = @json(route('memo.item-mak'));
        const awal = @json($itemAwal);
        const FORMAT_MAK = /^[^.\s]+(\.[^.\s]+){5}$/;
        const cls = 'w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition';

        let items = [];          // daftar item FA untuk MAK aktif
        let makAktif = '';
        let timer = null;

        function fmt(v) { return String(v).replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
        function angka(v) { return parseInt(String(v).replace(/\D/g, ''), 10) || 0; }

        function opsiTeks(it) {
            return it.kode + '. ' + it.uraian + (typeof it.sisa === 'number' ? ' (sisa FA Rp' + fmt(Math.max(it.sisa, 0)) + ')' : '');
        }

        function isiOpsi(select, terpilih) {
            const dipakaiLain = new Set(
                Array.from(rowsEl.querySelectorAll('select')).filter(s => s !== select && s.value).map(s => s.value)
            );
            select.innerHTML = '';
            const kosong = document.createElement('option');
            kosong.value = '';
            kosong.textContent = 'Pilih item...';
            select.appendChild(kosong);

            let ada = false;
            items.forEach(function (it) {
                if (dipakaiLain.has(it.kode)) return;
                const o = document.createElement('option');
                o.value = it.kode;
                o.textContent = opsiTeks(it);
                if (it.kode === terpilih) { o.selected = true; ada = true; }
                select.appendChild(o);
            });
            // Item tersimpan yang sudah tidak ada di FA aktif tetap tampil, supaya edit tidak menghilangkannya.
            if (terpilih && !ada) {
                const o = document.createElement('option');
                o.value = terpilih;
                o.textContent = terpilih;
                o.selected = true;
                select.appendChild(o);
            }
        }

        function refreshSemuaOpsi() {
            rowsEl.querySelectorAll('select').forEach(function (s) { isiOpsi(s, s.value); });
        }

        function beri_nama() {
            Array.from(rowsEl.children).forEach(function (row, i) {
                row.querySelector('select').name = 'item_mak[' + i + '][kode]';
                row.querySelector('input[data-uraian]').name = 'item_mak[' + i + '][uraian]';
                row.querySelector('input[data-jumlah]').name = 'item_mak[' + i + '][jumlah]';
            });
        }

        function sinkron() {
            const jml = rowsEl.children.length;
            const total = Array.from(rowsEl.querySelectorAll('input[data-jumlah]'))
                .reduce(function (a, el) { return a + angka(el.value); }, 0);

            totalBox.classList.toggle('hidden', jml === 0);
            totalVal.textContent = fmt(total);

            if (jml > 0) {
                nominal.value = fmt(total);
                nominal.readOnly = true;
                nominal.classList.add('bg-gray-50');
            } else {
                nominal.readOnly = false;
                nominal.classList.remove('bg-gray-50');
            }
            nominal.dispatchEvent(new Event('input', { bubbles: true }));  // supaya cek sisa MAK ikut terhitung
        }

        function tambahBaris(data) {
            data = data || {};
            const row = document.createElement('div');
            row.className = 'flex flex-col sm:flex-row gap-2 sm:items-center';

            const select = document.createElement('select');
            select.required = true;
            select.className = cls + ' sm:flex-1';
            isiOpsi(select, data.kode || '');

            const uraian = document.createElement('input');
            uraian.type = 'hidden';
            uraian.setAttribute('data-uraian', '1');
            uraian.value = data.uraian || '';

            const wrap = document.createElement('div');
            wrap.className = 'relative sm:w-48';
            const rp = document.createElement('span');
            rp.className = 'absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none';
            rp.textContent = 'Rp';
            const jumlah = document.createElement('input');
            jumlah.type = 'text';
            jumlah.inputMode = 'numeric';
            jumlah.required = true;
            jumlah.placeholder = 'Jumlah';
            jumlah.setAttribute('data-jumlah', '1');
            jumlah.className = 'w-full border border-gray-200 rounded-xl pl-9 pr-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition';
            jumlah.value = data.jumlah ? fmt(data.jumlah) : '';
            wrap.appendChild(rp);
            wrap.appendChild(jumlah);

            const hapus = document.createElement('button');
            hapus.type = 'button';
            hapus.className = 'text-sm text-red-500 hover:text-red-600 px-2 py-2.5 transition';
            hapus.textContent = 'Hapus';

            select.addEventListener('change', function () {
                const it = items.find(function (x) { return x.kode === select.value; });
                uraian.value = it ? it.uraian : '';
                refreshSemuaOpsi();
            });
            jumlah.addEventListener('input', function () { jumlah.value = fmt(jumlah.value); sinkron(); });
            hapus.addEventListener('click', function () {
                row.remove(); beri_nama(); refreshSemuaOpsi(); sinkron();
            });

            row.appendChild(select);
            row.appendChild(uraian);
            row.appendChild(wrap);
            row.appendChild(hapus);
            rowsEl.appendChild(row);
            beri_nama();
        }

        function muatItem(mak) {
            if (!FORMAT_MAK.test(mak)) {
                items = [];
                addBtn.classList.add('hidden');
                statusEl.textContent = 'Pilih MAK terlebih dahulu.';
                statusEl.classList.remove('hidden');
                refreshSemuaOpsi();
                return;
            }
            statusEl.textContent = 'Memuat item...';
            statusEl.classList.remove('hidden');

            fetch(URL_ITEM + '?mak=' + encodeURIComponent(mak), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (makInput.value.trim() !== mak) return;   // MAK sudah berganti lagi
                    items = d.items || [];
                    refreshSemuaOpsi();
                    if (items.length === 0) {
                        statusEl.textContent = 'Tidak ada data item untuk MAK ini. Memo tetap bisa disimpan tanpa item.';
                        addBtn.classList.add('hidden');
                    } else {
                        statusEl.classList.add('hidden');
                        addBtn.classList.remove('hidden');
                    }
                })
                .catch(function () {
                    statusEl.textContent = 'Gagal memuat item. Memo tetap bisa disimpan tanpa item.';
                });
        }

        function kosongkanBaris() {
            rowsEl.innerHTML = '';
            sinkron();
        }

        // Ganti MAK -> daftar item dimuat ulang, pilihan item sebelumnya dibuang.
        makInput.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                const mak = makInput.value.trim();
                if (mak === makAktif) return;
                makAktif = mak;
                kosongkanBaris();
                muatItem(mak);
            }, 350);
        });

        addBtn.addEventListener('click', function () { tambahBaris(); refreshSemuaOpsi(); sinkron(); });

        const form = nominal.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                rowsEl.querySelectorAll('input[data-jumlah]').forEach(function (el) {
                    el.value = el.value.replace(/\./g, '');
                });
            });
        }

        // Keadaan awal (halaman edit, atau kembali dari error validasi)
        makAktif = makInput.value.trim();
        awal.forEach(function (d) { tambahBaris(d); });
        if (awal.length) sinkron();
        muatItem(makAktif);
    })();
</script>

