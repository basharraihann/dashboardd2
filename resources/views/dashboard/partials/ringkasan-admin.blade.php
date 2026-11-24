{{-- Ringkasan admin: user per role, upload realisasi aktif, RO belum dipetakan --}}
@php
    $totalUser = (int) $userPerRole->sum();
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    {{-- User per role --}}
    <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Pengguna sistem</p>
            <a href="{{ route('users.index') }}" class="text-xs text-blue-600 hover:underline">Kelola</a>
        </div>
        <p class="mt-1 text-2xl font-bold text-gray-800 tabular-nums">{{ $totalUser }}</p>
        <ul class="mt-2 space-y-1 text-xs">
            @foreach (\App\Models\User::ROLE_LABELS as $nilai => $label)
                <li class="flex items-center justify-between gap-3">
                    <span class="text-gray-500">{{ $label }}</span>
                    <span class="font-semibold tabular-nums {{ ($userPerRole[$nilai] ?? 0) > 0 ? 'text-gray-800' : 'text-gray-300' }}">
                        {{ $userPerRole[$nilai] ?? 0 }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Upload realisasi aktif --}}
    <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Data realisasi aktif</p>
            <a href="{{ route('anggaran.upload') }}" class="text-xs text-blue-600 hover:underline">Upload</a>
        </div>
        @if ($uploadAktif)
            <p class="mt-1 text-lg font-bold text-gray-800">
                {{ $uploadAktif->created_at?->translatedFormat('d F Y') }}
            </p>
            <p class="mt-1 text-xs text-gray-400">
                Diunggah {{ $uploadAktif->created_at?->diffForHumans() }}
            </p>
        @else
            <p class="mt-2 text-sm text-amber-700">Belum ada data realisasi yang diunggah.</p>
        @endif
    </div>

    {{-- RO belum dipetakan --}}
    <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500">RO belum dipetakan ke unit</p>
            <a href="{{ route('anggaran.pemetaan') }}" class="text-xs text-blue-600 hover:underline">Pemetaan</a>
        </div>
        @if (!$uploadAktif)
            <p class="mt-2 text-sm text-gray-400">Menunggu data realisasi.</p>
        @elseif ($roBelumDipetakan === 0)
            <p class="mt-1 text-2xl font-bold text-emerald-600 tabular-nums">0</p>
            <p class="mt-1 text-xs text-gray-400">Semua RO sudah dipetakan.</p>
        @else
            <p class="mt-1 text-2xl font-bold text-amber-600 tabular-nums">{{ $roBelumDipetakan }}</p>
            <p class="mt-1 text-xs text-gray-400">Muncul sebagai "Belum dipetakan" di tabel serapan.</p>
        @endif
    </div>
</div>
