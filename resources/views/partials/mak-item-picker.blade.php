{{--
    Pemilih MAK sampai level ITEM + peringatan MAK bukan milik unit.
    Dipasang SETELAH #mak-combo dan SEBELUM skrip combo/sinkron di form (agenda & memo),
    karena skrip ini menambah <option> item ke <select id="mak-select"> dan tombol item ke #mak-panel.

    Cara kerja (tanpa mengubah alur lama):
    - Akun (MAK 6 bagian) yang punya item di FA aktif jadi judul; yang dipilih item-nya.
      Nilai option item = "MAK|kodeItem", dengan data-mak (MAK 6 bagian) & data-item (kode item).
    - Skrip sinkron di form mengisi input "mak" dari data-mak, partial ini mengisi input "item_kode".
    - Akun tanpa item (MAK manual / belum ada di FA) tetap bisa dipilih seperti biasa.

    Variabel:
    - $makItems        : MakItemService::semua()  [mak => [{kode, uraian, pagu, realisasi}]]
    - $itemOutstanding : MakSisaService::outstandingItemDetail()  ["MAK|item" => {sendiri, lain}]
    - $currentItem     : kode item tersimpan (edit) / old()
    - $varian          : 'memo' | 'agenda' (format label)
    - $kebutuhan       : total biaya (agenda) untuk tanda "Tidak cukup"; 0 = pakai sisa <= 0
--}}
@php
    $makItems = $makItems ?? [];
    $itemOutstanding = $itemOutstanding ?? [];
    $currentItem = $currentItem ?? null;
    $varian = $varian ?? 'memo';
    $kebutuhanItem = (int) ($kebutuhan ?? 0);
    $unitUser = auth()->user()?->unit?->kode;

    // Data ringkas: [kode, uraian, pagu, realisasi, outstanding unit sendiri, outstanding unit lain]
    $dataItem = [];
    foreach ($makItems as $mak => $daftarItem) {
        foreach ($daftarItem as $it) {
            $o = $itemOutstanding[$mak . '|' . $it['kode']] ?? ['sendiri' => 0, 'lain' => 0];
            $dataItem[$mak][] = [
                $it['kode'],
                $it['uraian'],
                (int) $it['pagu'],
                (int) $it['realisasi'],
                (int) $o['sendiri'],
                (int) $o['lain'],
            ];
        }
    }

    $payloadItem = [
        'items' => (object) $dataItem,
        'current' => $currentItem,
        'varian' => $varian,
        'kebutuhan' => $kebutuhanItem,
        'unit' => $unitUser,
    ];
@endphp

<input type="hidden" name="item_kode" id="mak-item-input" value="{{ $currentItem }}">
@error('item_kode')
    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
@enderror

{{-- Peringatan: MAK yang dipilih bukan milik anggaran unit user --}}
<div id="mak-unit-warning" role="alert"
    class="hidden mt-2 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0 mt-px" fill="none" viewBox="0 0 24 24"
        stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
    </svg>
    <span id="mak-unit-warning-teks"></span>
</div>

<script type="application/json" id="mak-item-data">@json($payloadItem)</script>

<script>
    (function () {
        const select = document.getElementById('mak-select');
        const dataEl = document.getElementById('mak-item-data');
        const itemInput = document.getElementById('mak-item-input');
        if (!select || !dataEl) return;

        const D = JSON.parse(dataEl.textContent || '{}');
        const ITEMS = D.items || {};
        const panel = document.getElementById('mak-panel');
        const search = document.getElementById('mak-search');
        const warn = document.getElementById('mak-unit-warning');
        const warnTeks = document.getElementById('mak-unit-warning-teks');

        const rp = (n) => (n < 0 ? '-Rp ' : 'Rp ') + Math.abs(Math.round(n)).toLocaleString('id-ID');
        const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const akunDari = (mak) => String(mak).split('.').pop();

        // ---------- 1. Option item di <select id="mak-select"> ----------
        const optAkun = {};
        Array.from(select.options).forEach(function (o) {
            if (o.value && o.value !== '__manual__') optAkun[o.value] = o;
        });

        Object.keys(ITEMS).forEach(function (mak) {
            const base = optAkun[mak];
            if (!base || !ITEMS[mak].length) return;
            base.dataset.punyaItem = '1';
            let setelah = base;

            ITEMS[mak].forEach(function (r) {
                const [kode, uraian, pagu, realisasi, outSendiri, outLain] = r;
                const o = document.createElement('option');
                Object.keys(base.dataset).forEach(function (k) { o.dataset[k] = base.dataset[k]; });
                delete o.dataset.punyaItem;
                o.value = mak + '|' + kode;
                o.textContent = mak + '.' + kode;
                o.dataset.mak = mak;
                o.dataset.item = kode;
                o.dataset.itemUraian = uraian;
                o.dataset.kodeTampil = mak + '.' + kode;
                o.dataset.label = (D.varian === 'agenda' ? mak : akunDari(mak)) + '.' + kode + ' — ' + uraian;
                o.dataset.paguAkun = base.dataset.pagu || '';
                o.dataset.pagu = pagu;
                o.dataset.realisasi = realisasi;
                o.dataset.sisa = pagu - realisasi;
                o.dataset.outstandingSendiri = outSendiri;
                o.dataset.outstandingLain = outLain;
                o.dataset.outstanding = outSendiri + outLain;
                setelah.after(o);
                setelah = o;
            });
        });

        // Item tersimpan (edit / old input): pilih option item-nya
        const awal = select.selectedOptions[0];
        if (D.current && awal && awal.value && awal.value !== '__manual__') {
            const target = awal.value.indexOf('|') === -1 ? awal.value + '|' + D.current : awal.value;
            if (Array.from(select.options).some((o) => o.value === target)) select.value = target;
        }
        if (search) {
            const o = select.selectedOptions[0];
            if (o && o.dataset.item) search.value = o.dataset.label;
        }

        // ---------- 2. Tombol item di panel dropdown ----------
        if (panel) {
            panel.querySelectorAll('.mak-item[data-mak]').forEach(function (btn) {
                const mak = btn.dataset.mak;
                const daftar = ITEMS[mak];
                if (!daftar || !daftar.length) return;

                // Akun jadi judul: tidak bisa dipilih langsung, yang dipilih item di bawahnya.
                btn.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                });
                btn.classList.add('cursor-default');
                btn.classList.remove('hover:bg-blue-50');
                btn.setAttribute('aria-disabled', 'true');
                const judul = btn.querySelector('.flex-1');
                if (judul) {
                    const tag = document.createElement('span');
                    tag.className = 'inline-block mt-1 rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700';
                    tag.textContent = daftar.length + ' item · pilih salah satu di bawah';
                    judul.appendChild(tag);
                }

                const cariAkun = btn.dataset.search || '';
                const wadah = document.createElement('div');
                wadah.className = 'mak-subitems ml-4 mb-1 border-l border-dashed border-gray-200';
                let cariSemua = cariAkun;

                daftar.forEach(function (r) {
                    const [kode, uraian, pagu, realisasi, outSendiri, outLain] = r;
                    const efektif = pagu - realisasi - outSendiri - outLain;
                    const kurang = D.kebutuhan > 0 ? efektif < D.kebutuhan : efektif <= 0;
                    const cari = (mak + '.' + kode + ' ' + kode + ' ' + uraian).toLowerCase();
                    cariSemua += ' ' + cari;

                    const b = document.createElement('button');
                    b.type = 'button';
                    b.dataset.mak = mak + '|' + kode;
                    b.dataset.search = cari + ' ' + cariAkun;
                    b.className = 'mak-item w-full text-left flex items-center gap-3 -ml-px pl-4 pr-3.5 py-1.5 border-l-2 border-transparent hover:bg-blue-50 transition';
                    b.innerHTML =
                        '<span class="flex-1 min-w-0">' +
                        '<span class="block font-mono text-[11px] text-gray-400">' + esc(akunDari(mak)) + '.<span class="font-bold text-gray-800">' + esc(kode) + '</span></span>' +
                        '<span class="block text-xs text-gray-700 leading-snug">' + esc(uraian || '-') + '</span>' +
                        '</span>' +
                        '<span class="text-right flex-shrink-0">' +
                        '<span class="block text-[10px] uppercase tracking-wide text-gray-400">Sisa item</span>' +
                        '<span class="block text-xs font-semibold tabular-nums ' + (kurang ? 'text-red-600' : 'text-emerald-600') + '">' + rp(efektif) + '</span>' +
                        '</span>' +
                        '<svg xmlns="http://www.w3.org/2000/svg" class="mak-check hidden h-4 w-4 text-blue-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>';
                    wadah.appendChild(b);
                });

                // Judul akun ikut tampil kalau salah satu item-nya cocok dengan pencarian
                btn.dataset.search = cariSemua;
                btn.after(wadah);
            });
        }

        // ---------- 3. Sinkron input item + peringatan unit ----------
        function cekUnit() {
            if (!warn) return false;
            const o = select.selectedOptions[0];
            const aktif = !!(D.unit && o && o.value && o.value !== '__manual__' && o.dataset.rekom !== '1');
            if (aktif) {
                const pemilik = o.dataset.unitPemilik || '';
                warnTeks.textContent = pemilik
                    ? 'MAK ini bukan milik anggaran unit Anda (' + D.unit + '). Anggaran ini milik ' + pemilik + '. Pastikan memang boleh dibebankan ke MAK ini.'
                    : 'MAK ini belum dipetakan ke unit Anda (' + D.unit + '). Pastikan memang boleh dibebankan ke MAK ini.';
            }
            warn.classList.toggle('hidden', !aktif);
            return aktif;
        }

        function sinkron() {
            const o = select.selectedOptions[0];
            if (itemInput) itemInput.value = (o && o.dataset.item) || '';
            cekUnit();
        }
        select.addEventListener('change', sinkron);
        sinkron();

        // ---------- 4. Cek sebelum simpan ----------
        const form = select.closest('form') || (itemInput && itemInput.closest('form'));
        if (form) {
            form.addEventListener('submit', function (e) {
                const o = select.selectedOptions[0];
                if (o && o.dataset.punyaItem === '1' && !o.dataset.item) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    alert('MAK ini punya rincian item. Pilih item-nya dulu (klik salah satu item di bawah akun belanja).');
                    if (search) search.focus();
                    return;
                }
                if (cekUnit() && !window.confirm(warnTeks.textContent + '\n\nTetap simpan?')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            }, true);
        }
    })();
</script>
