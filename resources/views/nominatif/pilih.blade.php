@php
    $tgl = now()->locale('id')->translatedFormat('j F Y');
    $tglKapital = mb_strtoupper($tgl);
    // class bantu supaya tabel preview tidak berulang-ulang
    $c = 'border border-gray-800 px-[0.4em] py-[0.25em]';
    $th = $c . ' font-bold text-center';
@endphp

<x-app-layout title="Pilih Jenis Nominatif">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pilih Jenis Nominatif</h2>
    </x-slot>

    <div class="bg-gradient-to-b from-gray-50/70 to-white min-h-full">
        <div class="py-6 sm:py-8 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">

            {{-- Info memo --}}
            <div
                class="bg-blue-50/70 border border-blue-100 text-blue-700 text-xs sm:text-sm px-4 py-3 rounded-xl mb-6">
                Nominatif untuk memo <span class="font-mono font-semibold">{{ $memo->nomor_memo }}</span>
                {{ $memo->uraian_kegiatan ? '— ' . $memo->uraian_kegiatan : '' }}
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- ===== Honorarium Narasumber ===== --}}
                <a href="{{ route('nominatif.create', ['memo' => $memo->id]) }}"
                    class="group flex flex-col bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md hover:border-blue-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition overflow-hidden">

                    <div class="bg-gray-100 border-b border-gray-200 p-4" aria-hidden="true">
                        {{-- Kertas: ukuran font mengikuti lebar kartu (cqw), jadi preview selalu proporsional --}}
                        <div class="relative bg-white ring-1 ring-gray-200 shadow-sm overflow-hidden aspect-[4/3]"
                            style="container-type:inline-size">
                            <div class="text-gray-900 select-none" style="font-size:1.3cqw; padding:3.5em 3.5em 0;">

                                <div class="text-center font-bold">DAFTAR HONORARIUM NARASUMBER</div>
                                <div class="mx-auto mt-[0.5em] w-[9em] border-b border-dotted border-gray-500"></div>
                                <div class="text-center font-bold mt-[1em] mb-[0.8em]">JAKARTA, {{ $tglKapital }}</div>

                                <table class="w-full border-collapse table-fixed">
                                    <colgroup>
                                        <col style="width:4%">
                                        <col style="width:26%">
                                        <col style="width:11%">
                                        <col style="width:4%">
                                        <col style="width:12%">
                                        <col style="width:12%">
                                        <col style="width:11%">
                                        <col style="width:11%">
                                        <col style="width:13%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th class="{{ $th }}">No</th>
                                            <th class="{{ $th }}">Nama</th>
                                            <th class="{{ $th }}">Sebagai</th>
                                            <th class="{{ $th }}">OJ</th>
                                            <th class="{{ $th }}">Honor</th>
                                            <th class="{{ $th }}">Bruto</th>
                                            <th class="{{ $th }}">Pajak</th>
                                            <th class="{{ $th }}">Netto</th>
                                            <th class="{{ $th }}">Tanda Tangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="{{ $c }} text-center py-[0.6em]">1</td>
                                            <td class="{{ $c }} text-gray-400">Nama narasumber...</td>
                                            <td class="{{ $c }} text-center">Narasumber</td>
                                            <td class="{{ $c }} text-center">1</td>
                                            @foreach (range(1, 4) as $i)
                                                <td class="{{ $c }}">
                                                    <div class="flex justify-between"><span>Rp</span><span>0</span></div>
                                                </td>
                                            @endforeach
                                            <td class="{{ $c }}"></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="flex justify-between mt-[0.8em]">
                                    <div class="pt-[1.3em]">Bendahara Pengeluaran
                                        <div class="mt-[5em] w-[9em] border-b border-dotted border-gray-500"></div>
                                        <div class="mt-[0.4em]">NIP. -</div>
                                    </div>
                                    <div>
                                        <div>Jakarta, {{ $tgl }}</div>
                                        <div>Pejabat Pembuat Komitmen</div>
                                        <div class="mt-[3.2em] w-[9em] border-b border-dotted border-gray-500"></div>
                                        <div class="mt-[0.4em]">NIP. -</div>
                                    </div>
                                </div>

                                <div class="text-center font-bold mt-[2em] mb-[0.8em]">DAFTAR HADIR NARASUMBER</div>
                                <table class="w-full border-collapse table-fixed">
                                    <colgroup>
                                        <col style="width:4%">
                                        <col style="width:27%">
                                        <col style="width:22%">
                                        <col style="width:5%">
                                        <col style="width:20%">
                                        <col style="width:22%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th class="{{ $th }}">No</th>
                                            <th class="{{ $th }}">Nama</th>
                                            <th class="{{ $th }}">Instansi</th>
                                            <th class="{{ $th }}">Gol</th>
                                            <th class="{{ $th }}">Jabatan</th>
                                            <th class="{{ $th }}">Tanda Tangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="{{ $c }} text-center py-[0.6em]">1</td>
                                            <td class="{{ $c }}"></td>
                                            <td class="{{ $c }} text-center">-</td>
                                            <td class="{{ $c }} text-center">-</td>
                                            <td class="{{ $c }} text-center">-</td>
                                            <td class="{{ $c }}"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-white to-transparent">
                            </div>
                        </div>
                    </div>

                    <div class="p-5">
                        <h3 class="text-sm font-semibold text-gray-800 group-hover:text-blue-700">Honorarium Narasumber
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Daftar narasumber dengan honor per OJ, pajak per orang,
                            dan netto.</p>
                    </div>
                </a>

                {{-- ===== Custom ===== --}}
                <a href="{{ route('nominatif.custom.create', ['memo' => $memo->id]) }}"
                    class="group flex flex-col bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md hover:border-blue-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 transition overflow-hidden">

                    <div class="bg-gray-100 border-b border-gray-200 p-4" aria-hidden="true">
                        <div class="relative bg-white ring-1 ring-gray-200 shadow-sm overflow-hidden aspect-[4/3]"
                            style="container-type:inline-size">
                            <div class="text-gray-900 select-none" style="font-size:1.3cqw; padding:3.5em 3.5em 0;">

                                <div class="text-center font-bold">DAFTAR NOMINATIF</div>
                                <div
                                    class="mx-auto mt-[0.5em] mb-[1.6em] w-[9em] border-b border-dotted border-gray-500">
                                </div>

                                {{-- 9 kolom, sama dengan PDF: No | Uraian | Harga | Jumlah Satuan | Sub Total | PPh 22
                                | PPh 23 | PPN | Total --}}
                                <table class="w-full border-collapse table-fixed">
                                    <colgroup>
                                        <col style="width:4%">
                                        <col style="width:31%">
                                        <col style="width:11%">
                                        <col style="width:9%">
                                        <col style="width:10%">
                                        <col style="width:8%">
                                        <col style="width:8%">
                                        <col style="width:9%">
                                        <col style="width:10%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th class="{{ $th }} py-[0.9em]">No</th>
                                            <th class="{{ $th }}">URAIAN</th>
                                            <th class="{{ $th }}">HARGA</th>
                                            <th class="{{ $th }}">JUMLAH SATUAN</th>
                                            <th class="{{ $th }}">SUB TOTAL</th>
                                            <th class="{{ $th }}">PPH 22</th>
                                            <th class="{{ $th }}">PPH 23</th>
                                            <th class="{{ $th }}">PPN</th>
                                            <th class="{{ $th }}">TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="{{ $c }}">1</td>
                                            <td class="{{ $c }} text-gray-400">Uraian item...</td>
                                            <td class="{{ $c }} text-right">-</td>
                                            <td class="{{ $c }} text-center">1 PKT</td>
                                            <td class="{{ $c }} text-right">-</td>
                                            <td class="{{ $c }} text-right">-</td>
                                            <td class="{{ $c }} text-right">-</td>
                                            <td class="{{ $c }} text-right">-</td>
                                            <td class="{{ $c }} text-right">-</td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="{{ $c }} bg-gray-300">PPH 22 &amp; PPH 23</td>
                                            <td class="{{ $c }} text-right">-</td>
                                            <td class="{{ $c }} text-right">-</td>
                                            <td class="{{ $c }}"></td>
                                            <td class="{{ $c }}"></td>
                                        </tr>
                                        <tr>
                                            <td colspan="8" class="{{ $c }} bg-gray-300">TOTAL</td>
                                            <td class="{{ $c }} text-right">-</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="grid grid-cols-3 gap-[2em] mt-[2em]">
                                    <div>
                                        <div>Setuju dibayar,</div>
                                        <div>Pejabat Pembuat Komitmen</div>
                                        <div class="mt-[5em] w-[9em] border-b border-dotted border-gray-500"></div>
                                        <div class="mt-[0.4em]">NIP.</div>
                                    </div>
                                    <div>
                                        <div>Lunas dibayar,</div>
                                        <div>Bendahara Pengeluaran</div>
                                        <div class="mt-[5em] w-[9em] border-b border-dotted border-gray-500"></div>
                                        <div class="mt-[0.4em]">NIP.</div>
                                    </div>
                                    <div>
                                        <div>Jakarta, {{ $tgl }}</div>
                                        <div>Penanggungjawab Kegiatan</div>
                                        <div class="mt-[5em] w-[9em] border-b border-dotted border-gray-500"></div>
                                        <div class="mt-[0.4em]">NIP.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-white to-transparent">
                            </div>
                        </div>
                    </div>

                    <div class="p-5">
                        <h3 class="text-sm font-semibold text-gray-800 group-hover:text-blue-700">Custom</h3>
                        <p class="text-xs text-gray-500 mt-1">Item bebas (uraian, harga, jumlah) dengan PPN, PPh 22, dan
                            PPh 23.</p>
                    </div>
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