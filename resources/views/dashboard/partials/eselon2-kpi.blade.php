@if (empty($kpi))
    <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6 text-sm text-gray-500">
        Belum ada data realisasi aktif untuk unit Anda.
    </div>
@else
    @php
        $rp = fn($v) => 'Rp ' . number_format($v, 0, ',', '.');
        $pc = fn($v) => number_format($v, 2, ',', '.') . '%';

        [$labelStatus, $badge, $bar] = [
            'hijau' => ['Sesuai target', 'bg-green-50 text-green-700', 'bg-green-500'],
            'kuning' => ['Sedikit di bawah target', 'bg-yellow-50 text-yellow-700', 'bg-yellow-500'],
            'merah' => ['Di bawah target', 'bg-red-50 text-red-700', 'bg-red-500'],
        ][$kpi['status']];

        $lebarBar = min(100, max(0, (float) $kpi['persen']));
        $negatif = $kpi['sisa_efektif'] < 0;
    @endphp

    <section>
        <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-1 mb-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Ringkasan Serapan Anggaran</h3>
                <p class="text-sm text-gray-400">{{ $serapan['unit'] }}</p>
            </div>
            @if ($uploadAktif)
                <p class="text-xs text-gray-400">
                    Data realisasi per
                    <span class="font-semibold text-gray-700">{{ $uploadAktif->created_at->translatedFormat('d F Y') }}</span>
                    ({{ $uploadAktif->created_at->diffForHumans() }})
                </p>
            @endif
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
            {{-- Pagu --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Pagu</p>
                <p class="mt-1.5 text-lg xl:text-xl font-semibold text-blue-900 break-words">{{ $rp($kpi['pagu']) }}</p>
                <p class="mt-1 text-xs text-gray-400">Total anggaran</p>
            </div>

            {{-- Realisasi --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Realisasi</p>
                <p class="mt-1.5 text-lg xl:text-xl font-semibold text-teal-700 break-words">{{ $rp($kpi['realisasi']) }}
                </p>
                <p class="mt-1 text-xs text-gray-400">{{ $pc($kpi['persen']) }} dari pagu</p>
            </div>

            {{-- Serapan --}}
            <div class="col-span-2 lg:col-span-1 bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Serapan</p>
                <p class="mt-1.5 text-2xl font-bold text-gray-900">{{ $pc($kpi['persen']) }}</p>

                <div class="relative mt-2.5 h-2 rounded-full bg-gray-100">
                    <div class="h-2 rounded-full {{ $bar }}" style="width: {{ $lebarBar }}%"></div>
                    <span class="absolute -top-1 h-4 w-0.5 bg-gray-800 rounded" style="left: {{ min(100, $target) }}%"
                        title="Target {{ $pc($target) }}"></span>
                </div>

                <div class="mt-2.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-md {{ $badge }}">{{ $labelStatus }}</span>
                    <span class="text-xs text-gray-400">target {{ $pc($target) }}</span>
                </div>
            </div>

            {{-- Outstanding --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Outstanding</p>
                <p class="mt-1.5 text-lg xl:text-xl font-semibold text-purple-600 break-words">
                    {{ $rp($kpi['outstanding']) }}</p>
                <p class="mt-1 text-xs text-gray-400">Pengajuan yang belum terealisasi</p>
            </div>

            {{-- Sisa efektif --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Sisa Efektif</p>
                <p
                    class="mt-1.5 text-lg xl:text-xl font-semibold break-words {{ $negatif ? 'text-red-600' : 'text-green-700' }}">
                    {{ $rp($kpi['sisa_efektif']) }}
                </p>
                <p class="mt-1 text-xs text-gray-400">Sisa pagu dikurangi outstanding</p>
            </div>
        </div>
    </section>
@endif