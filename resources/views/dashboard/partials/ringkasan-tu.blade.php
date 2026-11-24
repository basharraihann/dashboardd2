{{-- Ringkasan TU Unit: data master dan pintasan --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <a href="{{ route('pegawais.index') }}"
        class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 hover:border-blue-200 hover:shadow transition">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Jumlah pegawai</p>
            <div class="h-9 w-9 shrink-0 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" />
                </svg>
            </div>
        </div>
        <p class="mt-2 text-2xl font-bold text-gray-800 tabular-nums">{{ number_format($jumlahPegawai, 0, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-400">Kelola data pegawai</p>
    </a>

    <a href="{{ route('mak-options.index') }}"
        class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 hover:border-blue-200 hover:shadow transition">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Jumlah Nomor MAK</p>
            <div class="h-9 w-9 shrink-0 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
                </svg>
            </div>
        </div>
        <p class="mt-2 text-2xl font-bold text-gray-800 tabular-nums">{{ number_format($jumlahMak, 0, ',', '.') }}</p>
        <p class="mt-1 text-xs text-gray-400">Kelola opsi Nomor MAK</p>
    </a>

    <a href="{{ route('monitoring.index') }}"
        class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 hover:border-blue-200 hover:shadow transition">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Monitoring Memo</p>
            <div class="h-9 w-9 shrink-0 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
        </div>
        <p class="mt-2 text-sm font-semibold text-gray-800">Pantau status memo unit Anda</p>
        <p class="mt-1 text-xs text-gray-400">Hanya lihat, tanpa approval</p>
    </a>
</div>
