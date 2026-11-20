@props(['url', 'memo', 'jenis' => 'SPP', 'nomor' => '', 'label' => 'Input SAKTI', 'ubah' => false])
@php $dlg = 'sn-' . md5($url); @endphp

<button type="button" onclick="document.getElementById('{{ $dlg }}').showModal()"
    class="inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 cursor-pointer
    {{ $ubah ? 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' : 'border border-blue-800 bg-blue-700 text-white hover:bg-blue-800' }}">
    {{ $label }}
</button>

<dialog id="{{ $dlg }}" class="w-full max-w-md rounded-2xl p-0 shadow-xl backdrop:bg-black/40">
    <form method="POST" action="{{ $url }}" class="space-y-4 p-6 text-left">
        @csrf
        <h3 class="text-base font-bold text-gray-900">{{ $ubah ? 'Ubah nomor SAKTI' : 'Input SAKTI' }}</h3>
        <p class="text-sm text-gray-600">
            {{ $ubah ? 'Koreksi dokumen untuk memo' : 'Catat memo' }}
            <b class="tabular-nums">{{ $memo }}</b>
            {{ $ubah ? '.' : 'sudah diinput di SAKTI.' }}
        </p>

        <div class="grid grid-cols-3 gap-3">
            <label class="col-span-1 text-xs font-semibold text-gray-600">
                Jenis
                <select name="jenis_dokumen" required
                    class="mt-1 w-full rounded-md border border-gray-300 px-2 py-2 text-sm">
                    @foreach (['SPP', 'SPBy'] as $opsi)
                        <option value="{{ $opsi }}" @selected($opsi === $jenis)>{{ $opsi }}</option>
                    @endforeach
                </select>
            </label>
            <label class="col-span-2 text-xs font-semibold text-gray-600">
                Nomor
                <input type="text" name="nomor_spp_spby" required maxlength="100" value="{{ $nomor }}"
                    placeholder="Nomor SPP / SPBy" autocomplete="off"
                    class="mt-1 w-full rounded-md border border-gray-300 px-2 py-2 text-sm">
            </label>
        </div>

        <div class="flex justify-end gap-2 pt-1">
            <button type="button" onclick="this.closest('dialog').close()"
                class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
            <button type="submit"
                class="rounded-md border border-blue-800 bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-800">Simpan</button>
        </div>
    </form>
</dialog>