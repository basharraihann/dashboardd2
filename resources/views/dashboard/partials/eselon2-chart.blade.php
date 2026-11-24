@if (!empty($kpi))
    @php
        $program = collect($serapan['tree'])->where('depth', 0)->values();
        $chartProgram = [
            'labels' => $program->map(fn($t) => trim($t['kode'] . ' ' . $t['uraian']))->all(),
            'kode' => $program->pluck('kode')->all(),
            'realisasi' => $program->pluck('sd')->all(),
            'outstanding' => $program->map(fn($t) => $t['out'] ?? 0)->all(),
            'sisa' => $program->map(fn($t) => max(0, $t['efektif'] ?? $t['sisa']))->all(),
        ];
        $jumlahRo = count($ro);
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Komposisi per program --}}
        <div class="min-w-0 bg-white shadow-sm rounded-2xl border border-gray-100 p-4">
            <h4 class="font-semibold text-gray-800 mb-0.5">Komposisi pagu per program</h4>
            <p class="text-sm text-gray-500">Realisasi, outstanding, dan sisa efektif (Rp).</p>
            <p class="text-xs text-gray-400 mb-2">Klik bar untuk melihat rinciannya di tabel.</p>
            <div class="relative h-[260px]">
                <canvas id="chartProgram"></canvas>
            </div>
        </div>

        {{-- Serapan per RO --}}
        <div class="min-w-0 bg-white shadow-sm rounded-2xl border border-gray-100 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
                <div>
                    <h4 class="font-semibold text-gray-800 mb-0.5">Serapan per RO</h4>
                    <p class="text-sm text-gray-500">
                        Persen terhadap pagu RO. Garis putus-putus = target
                        {{ number_format($target, 1, ',', '.') }}%.
                    </p>
                    <p class="text-xs text-gray-400">Klik bar untuk melihat rinciannya di tabel.</p>
                </div>
                @if ($jumlahRo > 0)
                    <label class="flex items-center gap-2 text-xs text-gray-500">
                        Urutkan
                        <select id="roUrut"
                            class="border-gray-200 rounded-lg text-xs py-1 pl-2 pr-7 focus:ring-0 focus:border-gray-300">
                            <option value="tertinggal">Serapan terendah</option>
                            <option value="pagu">Pagu terbesar</option>
                            <option value="kode">Kode RO</option>
                        </select>
                    </label>
                @endif
            </div>

            @if ($jumlahRo === 0)
                <div class="h-[260px] flex items-center justify-center text-sm text-gray-400">Belum ada RO dengan pagu.</div>
            @else
                {{-- Tinggi tetap; kalau RO banyak, daftar discroll di dalam kartu --}}
                <div class="h-[260px] overflow-y-auto">
                    <div class="relative" style="height: {{ max(240, $jumlahRo * 26 + 60) }}px">
                        <canvas id="chartRo"></canvas>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    @endonce
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rp = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
            const singkat = v => {
                const a = Math.abs(v);
                if (a >= 1e9) return (v / 1e9).toFixed(1).replace('.0', '') + ' M';
                if (a >= 1e6) return (v / 1e6).toFixed(0) + ' jt';
                return new Intl.NumberFormat('id-ID').format(v);
            };
            const isMobile = window.innerWidth < 640;
            const font = { size: isMobile ? 10 : 12 };
            const legend = { position: 'bottom', labels: { boxWidth: 10, padding: 10, font } };
            const TARGET = {{ (float) $target }};

            // Warna disamakan dengan kartu KPI
            const WARNA = { realisasi: '#0f766e', outstanding: '#9333ea', sisa: '#4ade80' };

            // Klik bar -> tabel Serapan Anggaran membuka dan menyorot baris terkait
            const fokus = (kode, depth) =>
                window.dispatchEvent(new CustomEvent('fokus-serapan', { detail: { kode, depth } }));
            const pointer = (e, els) => { e.native.target.style.cursor = els.length ? 'pointer' : 'default'; };

            // ---- Komposisi per program (Rp) ----
            const p = @json($chartProgram);
            new Chart(document.getElementById('chartProgram'), {
                type: 'bar',
                data: {
                    labels: p.labels,
                    datasets: [
                        { label: 'Realisasi', data: p.realisasi, backgroundColor: WARNA.realisasi, maxBarThickness: 28 },
                        { label: 'Outstanding', data: p.outstanding, backgroundColor: WARNA.outstanding, maxBarThickness: 28 },
                        { label: 'Sisa efektif', data: p.sisa, backgroundColor: WARNA.sisa, maxBarThickness: 28 },
                    ]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    onClick: (e, els) => { if (els.length) fokus(p.kode[els[0].index], 0); },
                    onHover: pointer,
                    plugins: {
                        legend,
                        tooltip: { callbacks: { label: c => c.dataset.label + ': ' + rp(c.parsed.x) } }
                    },
                    scales: {
                        x: { stacked: true, ticks: { font, callback: singkat } },
                        y: {
                            stacked: true, grid: { display: false },
                            ticks: {
                                font,
                                callback: function (v) {
                                    const t = this.getLabelForValue(v);
                                    return t.length > 16 ? t.slice(0, 16) + '…' : t;
                                }
                            }
                        }
                    }
                }
            });

            // ---- Serapan per RO (100% stacked + garis target) ----
            const ro = @json($ro);
            const el = document.getElementById('chartRo');
            if (!el || !ro.length) return;

            const pct = r => r.pagu > 0 ? r.realisasi / r.pagu * 100 : 0;
            const urut = {
                tertinggal: (a, b) => pct(a) - pct(b),
                pagu: (a, b) => b.pagu - a.pagu,
                kode: (a, b) => String(a.kode).localeCompare(String(b.kode)),
            };
            let baris = [];

            const garisTarget = {
                id: 'garisTarget',
                afterDatasetsDraw(chart) {
                    const { ctx, chartArea: { top, bottom }, scales: { x } } = chart;
                    const px = x.getPixelForValue(TARGET);
                    ctx.save();
                    ctx.setLineDash([5, 4]);
                    ctx.strokeStyle = '#111827';
                    ctx.lineWidth = 1.5;
                    ctx.beginPath();
                    ctx.moveTo(px, top);
                    ctx.lineTo(px, bottom);
                    ctx.stroke();
                    ctx.restore();
                }
            };

            const chart = new Chart(el, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        { label: 'Realisasi', data: [], backgroundColor: WARNA.realisasi, maxBarThickness: 16 },
                        { label: 'Outstanding', data: [], backgroundColor: WARNA.outstanding, maxBarThickness: 16 },
                        { label: 'Sisa efektif', data: [], backgroundColor: WARNA.sisa, maxBarThickness: 16 },
                    ]
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', axis: 'y', intersect: false },
                    onClick: (e, els) => { if (els.length) fokus(baris[els[0].index].kode, 2); },
                    onHover: pointer,
                    plugins: {
                        legend,
                        tooltip: {
                            callbacks: {
                                title: c => {
                                    const r = baris[c[0].dataIndex];
                                    return r.kode + ' · ' + r.uraian;
                                },
                                label: c => c.dataset.label + ': ' + c.parsed.x.toFixed(1).replace('.', ',') + '%',
                                footer: c => {
                                    const r = baris[c[0].dataIndex];
                                    return [
                                        '',
                                        'Unit: ' + r.unit,
                                        'Pagu: ' + rp(r.pagu),
                                        'Realisasi: ' + rp(r.realisasi),
                                        'Outstanding: ' + rp(r.outstanding),
                                        'Sisa efektif: ' + rp(r.sisa_efektif),
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: { stacked: true, min: 0, max: 100, ticks: { font, callback: v => v + '%' } },
                        y: { stacked: true, grid: { display: false }, ticks: { font, autoSkip: false } }
                    }
                },
                plugins: [garisTarget]
            });

            function isi(mode) {
                baris = [...ro].sort(urut[mode] || urut.tertinggal);
                const r = baris.map(x => Math.min(100, pct(x)));
                const o = baris.map((x, i) => Math.min(100 - r[i], Math.max(0, x.outstanding / x.pagu * 100)));
                const s = baris.map((x, i) => Math.max(0, 100 - r[i] - o[i]));
                chart.data.labels = baris.map(x => x.kode);
                chart.data.datasets[0].data = r;
                chart.data.datasets[1].data = o;
                chart.data.datasets[2].data = s;
                chart.update();
            }

            document.getElementById('roUrut')?.addEventListener('change', e => isi(e.target.value));
            isi('tertinggal');
        });
    </script>
@endif