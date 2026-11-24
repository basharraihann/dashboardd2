{{-- Eselon II: RO yang perlu perhatian --}}
@if ($kpi)
    @php
        $rp ??= fn($n) => number_format($n, 0, ',', '.');

        // Tiap kartu: judul, keterangan, daftar RO, dan cara menampilkan angka di kanan
        $kartu = [
            [
                'judul' => 'Sisa efektif negatif',
                'ket' => 'Outstanding melebihi sisa pagu.',
                'baris' => $perhatian['negatif'],
                'nilai' => fn($r) => 'Rp ' . $rp($r['sisa_efektif']),
                'sub' => fn($r) => 'Sisa Rp ' . $rp($r['sisa']) . ' · Outstanding Rp ' . $rp($r['outstanding']),
                'warna' => 'text-red-600',
                'kosong' => 'Tidak ada RO dengan sisa efektif negatif.',
            ],
            [
                'judul' => 'Paling tertinggal dari target',
                'ket' => 'Selisih rupiah terhadap target serapan ' . number_format($target, 0, ',', '.') . '%.',
                'baris' => $perhatian['tertinggal'],
                'nilai' => fn($r) => 'Rp ' . $rp($r['tertinggal']),
                'sub' => fn($r) => 'Serapan ' . number_format($r['persen'], 2, ',', '.') . '% dari pagu Rp ' . $rp($r['pagu']),
                'warna' => 'text-amber-700',
                'kosong' => 'Semua RO sudah sesuai target.',
            ],
            [
                'judul' => 'Belum ada realisasi',
                'ket' => $perhatian['nol_total'] > 5
                    ? 'Menampilkan 5 pagu terbesar dari ' . $perhatian['nol_total'] . ' RO.'
                    : 'RO dengan pagu tetapi realisasi masih nol.',
                'baris' => $perhatian['nol'],
                'nilai' => fn($r) => 'Rp ' . $rp($r['pagu']),
                'sub' => fn($r) => 'Pagu belum terserap',
                'warna' => 'text-gray-800',
                'kosong' => 'Semua RO sudah ada realisasi.',
            ],
        ];
    @endphp

    <div class="space-y-3">
        <div>
            <h4 class="font-semibold text-gray-800">Perlu Perhatian</h4>
            <p class="text-sm text-gray-400">Tingkat RO (rincian output) di unit Anda.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            @foreach ($kartu as $k)
                <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
                    <p class="text-sm font-semibold text-gray-800">{{ $k['judul'] }}</p>
                    <p class="text-xs text-gray-400 mb-3">{{ $k['ket'] }}</p>

                    @if (count($k['baris']) === 0)
                        <p class="py-4 text-center text-xs text-emerald-600">{{ $k['kosong'] }}</p>
                    @else
                        <ul class="divide-y divide-gray-100">
                            @foreach ($k['baris'] as $r)
                                <li class="py-2.5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-800">{{ $r['kode'] }}</p>
                                            <p class="text-xs text-gray-500 truncate" title="{{ $r['uraian'] }}">{{ $r['uraian'] }}</p>
                                            <p class="text-[11px] text-blue-600">{{ $r['unit'] }}</p>
                                        </div>
                                        <p class="shrink-0 text-xs font-semibold tabular-nums {{ $k['warna'] }}">
                                            {{ $k['nilai']($r) }}
                                        </p>
                                    </div>
                                    <p class="mt-0.5 text-[11px] text-gray-400 tabular-nums">{{ $k['sub']($r) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif