@php
    $pw = 1123; // A4 landscape @ 96dpi
    $ph = 794;
@endphp

<aside id="nom-preview" class="nom-aside min-w-0" x-data="honorPreview({{ $pw }})">

    <div class="flex items-center justify-between gap-3 mb-3 px-1">
        <div>
            <h3 class="text-sm font-semibold text-gray-800">Preview Honorarium</h3>
            <p class="text-[11px] text-gray-400 flex items-center gap-1.5">
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                Update otomatis saat mengisi
            </p>
        </div>
        <span class="text-[11px] text-gray-400" x-text="(rows.length + 1) + ' halaman'"></span>
    </div>

    <div class="bg-gradient-to-b from-gray-100 to-gray-200/70 rounded-2xl border border-gray-200/70 p-3"
        style="max-height: calc(100vh - 6.5rem); overflow-y: auto; scrollbar-gutter: stable;">
        <div x-ref="stage" class="w-full relative">
            <div x-ref="pages" style="width: {{ $pw }}px; transform-origin: top left;">

                {{-- ===== HALAMAN 1: rekap kolektif ===== --}}
                <div class="pv-paper" style="min-height: {{ $ph }}px;">
                    <div class="pv-judul">DAFTAR HONORARIUM NARASUMBER</div>
                    <div class="pv-judul" x-text="v(meta.judul)" :class="meta.judul ? '' : 'pv-empty'"></div>
                    <div class="pv-judul" x-text="tglKota(false)"></div>

                    <table class="pv-data">
                        <thead>
                            <tr>
                                <th style="width:3%">No</th>
                                <th style="width:31%">Nama</th>
                                <th style="width:8%">Sebagai</th>
                                <th style="width:11%">Tarif</th>
                                <th style="width:4%">Jam</th>
                                <th style="width:14%">Bruto</th>
                                <th style="width:14%">Pajak</th>
                                <th style="width:14%">Netto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in rows" :key="'a' + row.k">
                                <tr style="height:32px">
                                    <td class="c" x-text="i + 1"></td>
                                    <td class="l">
                                        <span x-text="row.nama || 'Nama narasumber...'"
                                            :class="row.nama ? '' : 'pv-empty'"></span>
                                        <span x-show="row.npwp"><br>NPWP: <span x-text="row.npwp"></span></span>
                                    </td>
                                    <td class="c">Narasumber</td>
                                    <td>
                                        <div class="acc"><span>Rp</span><span x-text="f(row.honor)"></span></div>
                                    </td>
                                    <td class="c" x-text="row.oj"></td>
                                    <td>
                                        <div class="acc"><span>Rp</span><span x-text="f(bruto(row))"></span></div>
                                    </td>
                                    <td>
                                        <div class="acc"><span>Rp</span><span x-text="f(pajak(row))"></span></div>
                                    </td>
                                    <td>
                                        <div class="acc"><span>Rp</span><span x-text="f(netto(row))"></span></div>
                                    </td>
                                </tr>
                            </template>
                            <tr class="b">
                                <td colspan="5" class="c">Jumlah</td>
                                <td>
                                    <div class="acc"><span>Rp</span><span x-text="f(sum('bruto'))"></span></div>
                                </td>
                                <td>
                                    <div class="acc"><span>Rp</span><span x-text="f(sum('pajak'))"></span></div>
                                </td>
                                <td>
                                    <div class="acc"><span>Rp</span><span x-text="f(sum('netto'))"></span></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="pv-ttd" style="margin-top:20px">
                        <tr>
                            <td style="width:70%"></td>
                            <td style="width:30%">
                                Jakarta, <span x-text="tglTtd()"></span><br>
                                Pejabat Pembuat Komitmen
                                <div style="height:70px"></div>
                                <b x-text="v(meta.ppk.nama)"></b><br>
                                NIP <span x-text="nipSpasi(meta.ppk.nip)"></span>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- ===== HALAMAN INDIVIDUAL: satu per narasumber ===== --}}
                <template x-for="(row, i) in rows" :key="'p' + row.k">
                    <div class="pv-paper" style="min-height: {{ $ph }}px; margin-top: 24px;">
                        <div class="pv-judul up">DAFTAR HONORARIUM NARASUMBER</div>
                        <div class="pv-judul up" x-text="v(meta.judul).toUpperCase()"
                            :class="meta.judul ? '' : 'pv-empty'"></div>
                        <div class="pv-judul up" x-text="tglKota(true)"></div>

                        <table class="pv-data">
                            <thead>
                                <tr>
                                    <th style="width:3%">No</th>
                                    <th style="width:26%">Nama</th>
                                    <th style="width:8%">Sebagai</th>
                                    <th style="width:4%">OJ</th>
                                    <th style="width:11%">Honor</th>
                                    <th style="width:11%">Bruto</th>
                                    <th style="width:10%">Pajak</th>
                                    <th style="width:11%">Netto</th>
                                    <th style="width:16%">Tanda Tangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="height:40px">
                                    <td class="c" x-text="i + 1"></td>
                                    <td class="l">
                                        <span x-text="row.nama || 'Nama narasumber...'"
                                            :class="row.nama ? '' : 'pv-empty'"></span>
                                        <span x-show="row.npwp"><br>NPWP: <span x-text="row.npwp"></span></span>
                                    </td>
                                    <td class="c">Narasumber</td>
                                    <td class="c" x-text="row.oj"></td>
                                    <td>
                                        <div class="acc"><span>Rp</span><span x-text="f(row.honor)"></span></div>
                                    </td>
                                    <td>
                                        <div class="acc"><span>Rp</span><span x-text="f(bruto(row))"></span></div>
                                    </td>
                                    <td>
                                        <div class="acc"><span>Rp</span><span x-text="f(pajak(row))"></span></div>
                                    </td>
                                    <td>
                                        <div class="acc"><span>Rp</span><span x-text="f(netto(row))"></span></div>
                                    </td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>

                        <table class="pv-ttd" style="margin-top:8px">
                            <tr>
                                <td style="width:35%; vertical-align:bottom">&nbsp;<br>Bendahara Pengeluaran</td>
                                <td style="width:30%"></td>
                                <td style="width:35%; vertical-align:bottom">
                                    Jakarta, <span x-text="tglTtd()"></span><br>Pejabat Pembuat Komitmen
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="height:70px"></div>
                                    <b x-text="v(meta.bendahara.nama)"></b><br>
                                    NIP. <span x-text="nipPolos(meta.bendahara.nip)"></span>
                                </td>
                                <td></td>
                                <td>
                                    <div style="height:70px"></div>
                                    <b x-text="v(meta.ppk.nama)"></b><br>
                                    NIP. <span x-text="nipPolos(meta.ppk.nip)"></span>
                                </td>
                            </tr>
                        </table>

                        <div style="margin-top:24px"></div>
                        <div class="pv-judul up">DAFTAR HADIR NARASUMBER</div>
                        <table class="pv-data">
                            <thead>
                                <tr>
                                    <th style="width:3%">No</th>
                                    <th style="width:27%">Nama</th>
                                    <th style="width:18%">Instansi</th>
                                    <th style="width:5%">Gol</th>
                                    <th style="width:20%">Jabatan</th>
                                    <th style="width:27%">Tanda Tangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="height:40px">
                                    <td class="c" x-text="i + 1"></td>
                                    <td class="l" x-text="row.nama"></td>
                                    <td class="c" x-text="row.instansi || '-'"></td>
                                    <td class="c" x-text="row.golongan || '-'"></td>
                                    <td class="c" x-text="row.jabatan || '-'"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

            </div>
        </div>
    </div>
</aside>

<style>
    #nom-preview.nom-aside {
        display: none;
    }

    @media (min-width: 1180px) {
        #nom-preview.nom-aside {
            display: block;
            position: sticky;
            top: 1rem;
            align-self: start;
        }
    }

    /* Margin sama dengan @page PDF (12mm 14mm ~ 45px 53px) */
    #nom-preview .pv-paper {
        width:
            {{ $pw }}
            px;
        box-sizing: border-box;
        padding: 45px 53px;
        background: #fff;
        color: #000;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 13px;
        line-height: normal;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .08), 0 10px 30px rgba(0, 0, 0, .15);
    }

    #nom-preview .pv-judul {
        text-align: center;
        font-weight: bold;
        line-height: 1.6;
    }

    #nom-preview .pv-judul.up {
        text-transform: uppercase;
    }

    #nom-preview table.pv-data {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin-top: 10px;
    }

    #nom-preview table.pv-data th,
    #nom-preview table.pv-data td {
        border: 1px solid #000;
        padding: 4px 5px;
        vertical-align: middle;
        font-size: 13px;
    }

    #nom-preview table.pv-data th {
        text-align: center;
    }

    #nom-preview table.pv-ttd {
        width: 100%;
        border-collapse: collapse;
    }

    #nom-preview table.pv-ttd td {
        vertical-align: top;
        line-height: 1.6;
        padding: 0;
        font-size: 13px;
        border: none;
    }

    #nom-preview .acc {
        display: flex;
        justify-content: space-between;
        white-space: nowrap;
    }

    #nom-preview .c {
        text-align: center;
    }

    #nom-preview .l {
        text-align: left;
    }

    #nom-preview .b td {
        font-weight: bold;
    }

    #nom-preview .pv-empty {
        color: #9ca3af;
    }
</style>

<script>
    document.addEventListener('alpine:init', function () {
        Alpine.data('honorPreview', function (PW) {
            const DOTS = '.....................';
            const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
                'September', 'Oktober', 'November', 'Desember'];

            return {
                meta: {
                    judul: '', kota: '', iso: '',
                    ppk: { nama: '', nip: '' },
                    bendahara: { nama: '', nip: '' },
                },

                init() {
                    const form = this.$root.closest('.nom-layout').querySelector('form');

                    const field = (n) => (form.elements[n] ? form.elements[n].value : '');
                    const pegawai = (n) => {
                        const el = form.elements[n];
                        const opt = el && el.value && el.selectedOptions && el.selectedOptions[0];
                        return opt ? { nama: opt.textContent.trim(), nip: opt.dataset.nip || '' } : { nama: '', nip: '' };
                    };
                    const baca = () => {
                        this.meta.judul = (field('uraian_kegiatan') || '').trim();
                        this.meta.kota = (field('kota') || '').trim();
                        this.meta.iso = field('tanggal');
                        this.meta.ppk = pegawai('ppk_id');
                        this.meta.bendahara = pegawai('bendahara_id');
                    };
                    form.addEventListener('input', baca);
                    form.addEventListener('change', baca);
                    baca();

                    // Skala kertas ke lebar kolom; tinggi mengikuti jumlah halaman
                    const stage = this.$refs.stage, pages = this.$refs.pages;
                    const fit = () => {
                        const w = stage.clientWidth;
                        if (!w) return;
                        const s = w / PW;
                        pages.style.transform = 'scale(' + s + ')';
                        stage.style.height = Math.ceil(pages.offsetHeight * s) + 'px';
                    };
                    if (window.ResizeObserver) {
                        const ro = new ResizeObserver(fit);
                        ro.observe(stage);
                        ro.observe(pages);
                    }
                    window.addEventListener('resize', fit);
                    this.$nextTick(fit);
                },

                v(s) { return s || DOTS; },

                // Format ribuan gaya PDF: 1,800,000
                f(n) { return (Math.round(Number(n) || 0)).toLocaleString('en-US'); },

                tglIndo(pad) {
                    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(this.meta.iso || '');
                    if (!m) return '';
                    const d = pad ? m[3] : String(parseInt(m[2] ? m[3] : 0, 10));
                    return d + ' ' + BULAN[parseInt(m[2], 10) - 1] + ' ' + m[1];
                },

                // Judul: "Jakarta, 5 Mei 2026" (huruf kecil) / "JAKARTA, 05 MEI 2026" (upper)
                tglKota(upper) {
                    const kota = this.meta.kota || 'Jakarta';
                    const t = this.tglIndo(upper);
                    const s = kota + ', ' + t;
                    return upper ? s.toUpperCase() : s;
                },

                tglTtd() { return this.tglIndo(true); },

                // 19870212 201402 1 001
                nipSpasi(nip) {
                    const d = String(nip || '').replace(/\D/g, '');
                    return d.length === 18
                        ? d.slice(0, 8) + ' ' + d.slice(8, 14) + ' ' + d.slice(14, 15) + ' ' + d.slice(15, 18)
                        : (nip || '-');
                },

                // 198702122014021001
                nipPolos(nip) { return nip ? String(nip).replace(/\D/g, '') : '-'; },
            };
        });
    });
</script>