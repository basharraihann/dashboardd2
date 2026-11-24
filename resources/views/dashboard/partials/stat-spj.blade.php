{{-- Ringkasan statistik SPJ: agenda, dana per tipe pembayaran, total dana diajukan --}}
@php
    $rp ??= fn($n) => number_format($n, 0, ',', '.');
    $tipeBayar = collect($danaBulanIni['tipe'])->where('nominal', '>', 0)->values();
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Agenda bulan ini</p>
            <div class="h-9 w-9 shrink-0 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>
        <p class="mt-2 text-2xl font-bold text-gray-800 tabular-nums">{{ $agendaBulanIni }}</p>
    </div>

    <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Rincian dana per tipe pembayaran</p>
        @if ($tipeBayar->isEmpty())
            <p class="mt-2 text-sm text-gray-400">Belum ada pengajuan bulan ini.</p>
        @else
            <ul class="mt-2 space-y-1.5 text-sm">
                @foreach ($tipeBayar as $it)
                    <li class="flex items-center justify-between gap-3">
                        <span class="min-w-0 truncate" title="{{ $it['nama'] }}">
                            <span class="font-semibold text-gray-800">{{ $it['kode'] }}</span>
                            <span class="text-xs text-gray-400">{{ $it['nama'] }}</span>
                        </span>
                        <span class="font-semibold text-gray-800 tabular-nums">Rp {{ $rp($it['nominal']) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Dana diajukan bulan ini</p>
            <div class="h-9 w-9 shrink-0 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
        <p class="mt-2 text-2xl font-bold text-gray-800 tabular-nums break-words">Rp {{ $rp($danaBulanIni['total']) }}</p>
    </div>
</div>
