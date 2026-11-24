<x-app-layout title="Dashboard">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $btnPrimary = 'inline-flex flex-1 sm:flex-none justify-center items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2.5 rounded-xl text-sm shadow-sm transition';
        $btnOutline = 'inline-flex flex-1 sm:flex-none justify-center items-center gap-1.5 px-4 py-2.5 rounded-xl text-sm font-semibold border border-blue-500 text-blue-600 bg-white hover:bg-blue-50 transition';
        $rp = fn($n) => number_format($n, 0, ',', '.');
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            {{-- Welcome card + aksi cepat (tombol ditentukan controller sesuai role) --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-bold text-gray-800">
                                Halo, {{ auth()->user()->name ?? 'Pengguna' }} 👋
                            </h3>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700">
                                {{ auth()->user()->roleLabel() }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $sapaan }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2 w-full lg:w-auto">
                        @foreach ($aksi as $a)
                            @if (!empty($a['primary']))
                                <a href="{{ route($a['route']) }}" class="{{ $btnPrimary }}">
                                    @if ($a['route'] === 'agendas.create')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                    @endif
                                    {{ $a['label'] }}
                                </a>
                            @else
                                <a href="{{ route($a['route']) }}" class="{{ $btnOutline }}">{{ $a['label'] }}</a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Blok dashboard: daftar dan urutannya dikirim controller sesuai role --}}
            @foreach ($blok as $nama)
                @include('dashboard.partials.' . $nama)
            @endforeach

        </div>
    </div>
</x-app-layout>
