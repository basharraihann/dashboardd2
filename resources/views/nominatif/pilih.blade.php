<x-app-layout title="Pilih Jenis Nominatif">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pilih Jenis Nominatif</h2>
    </x-slot>

    <div class="bg-gradient-to-b from-gray-50/70 to-white min-h-full">
        <div class="py-6 sm:py-8 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">

            <div
                class="bg-blue-50/70 border border-blue-100 text-blue-700 text-xs sm:text-sm px-4 py-3 rounded-xl mb-6">
                Nominatif untuk memo <span class="font-mono font-semibold">{{ $memo->nomor_memo }}</span>
                {{ $memo->uraian_kegiatan ? '— ' . $memo->uraian_kegiatan : '' }}
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="{{ route('nominatif.create', ['memo' => $memo->id]) }}"
                    class="group block bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-blue-200 transition p-5 sm:p-6">
                    <h3 class="text-sm font-semibold text-gray-800 group-hover:text-blue-700">Honorarium Narasumber</h3>
                    <p class="text-xs text-gray-400 mt-1">Daftar narasumber dengan honor per OJ, pajak per orang, dan
                        netto.</p>
                </a>

                <a href="{{ route('nominatif.custom.create', ['memo' => $memo->id]) }}"
                    class="group block bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-blue-200 transition p-5 sm:p-6">
                    <h3 class="text-sm font-semibold text-gray-800 group-hover:text-blue-700">Custom</h3>
                    <p class="text-xs text-gray-400 mt-1">Item bebas (uraian, harga, jumlah) dengan PPN, PPh 22, dan PPh
                        23.</p>
                </a>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('memo.index') }}" class="text-sm text-gray-400 hover:text-gray-600 transition">
                    &larr; Nanti saja, kembali ke daftar memo
                </a>
            </div>
        </div>
    </div>
</x-app-layout>