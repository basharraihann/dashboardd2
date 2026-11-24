<x-app-layout title="Pengaturan Nomor Memo">
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6">

        {{-- Judul halaman (sama pola dengan halaman Memorandum) --}}
        <a href="{{ route('memo.index') }}"
            class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-blue-600">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Memorandum
        </a>
        <h1 class="mt-3 text-2xl font-semibold text-slate-900">Pengaturan nomor</h1>
        <p class="mt-1 text-sm text-slate-500">Atur format dan nomor awal untuk memo berikutnya.</p>

        {{-- Preview: gaya sama dengan panel "Nomor memo berikutnya" --}}
        <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50/70 px-6 py-5">
            <p class="text-sm font-medium text-blue-700">Nomor memo berikutnya</p>
            <p id="prev" class="mt-1 break-all text-3xl font-semibold tabular-nums text-slate-900"></p>
            <p class="mt-1 text-sm text-slate-500">Pratinjau langsung dari format dan nomor awal di bawah.</p>
        </div>

        <form method="POST" action="{{ route('memo.nomor-setting.update') }}"
            class="mt-5 space-y-6 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
            @csrf @method('PUT')

            {{-- Format nomor --}}
            <div>
                <label for="tpl" class="text-sm font-medium text-slate-700">Format nomor</label>
                <input id="tpl" name="template" value="{{ old('template', $s->template) }}" autocomplete="off"
                    class="mt-1.5 h-11 w-full rounded-xl border border-slate-200 px-3.5 font-mono text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('template') border-red-300 @enderror">
                @error('template')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-sm text-slate-500">Wajib diawali <code
                        class="rounded bg-slate-100 px-1.5 py-0.5 text-slate-700">{no}</code>.
                    Klik token untuk menambahkannya ke format.</p>

                {{-- Token (klik untuk menyisipkan) --}}
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ([['{tahun}', '2026'], ['{tahun2}', '26'], ['{bulan}', '07'], ['{romawi}', 'VII']] as [$tok, $ex])
                        <button type="button" data-token="{{ $tok }}"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                            <span class="font-mono">{{ $tok }}</span>
                            <span class="text-slate-400">{{ $ex }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- Contoh format (klik untuk memakai) --}}
                <div class="mt-4">
                    <p class="text-sm text-slate-500">Contoh format</p>
                    <div class="mt-2 flex flex-col gap-2">
                        @foreach (['{no}.KU.00.00/{tahun}', '{no}/LS.D1.PPK/KU.00/{bulan}/{tahun}', '{no}/KU/{romawi}/{tahun}'] as $ct)
                            <button type="button" data-template="{{ $ct }}"
                                class="w-full rounded-lg bg-slate-50 px-3 py-2 text-left font-mono text-sm text-slate-600 hover:bg-blue-50 hover:text-blue-700">
                                {{ $ct }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <hr class="border-slate-100">

            {{-- Nomor awal --}}
            <div>
                <label for="awal" class="text-sm font-medium text-slate-700">Nomor awal</label>
                <input id="awal" type="number" min="1" name="nomor_awal" value="{{ old('nomor_awal', $s->nomor_awal) }}"
                    class="mt-1.5 h-11 w-40 rounded-xl border border-slate-200 px-3.5 text-sm text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 @error('nomor_awal') border-red-300 @enderror">
                @error('nomor_awal')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-sm text-slate-500">
                    Nomor berikutnya tidak akan lebih kecil dari ini. Nomor terbesar yang sudah terpakai:
                    <span class="font-semibold text-slate-700">{{ $terbesar }}</span>.
                    Jika diisi lebih kecil dari itu, tidak berpengaruh.
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('memo.index') }}"
                    class="inline-flex h-11 items-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit"
                    class="inline-flex h-11 items-center rounded-xl bg-blue-600 px-5 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    Simpan pengaturan
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const ROM = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
            const terbesar = @json((int) $terbesar);
            const $ = (id) => document.getElementById(id);

            function prev() {
                const d = new Date();
                const urut = Math.max(terbesar + 1, parseInt($('awal').value, 10) || 1);
                $('prev').textContent = $('tpl').value
                    .split('{no}').join(urut)
                    .split('{tahun2}').join(String(d.getFullYear()).slice(-2))
                    .split('{tahun}').join(d.getFullYear())
                    .split('{bulan}').join(String(d.getMonth() + 1).padStart(2, '0'))
                    .split('{romawi}').join(ROM[d.getMonth() + 1]);
            }

            // Sisipkan token di posisi kursor
            document.querySelectorAll('[data-token]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const el = $('tpl');
                    const s = el.selectionStart ?? el.value.length;
                    const e = el.selectionEnd ?? el.value.length;
                    el.value = el.value.slice(0, s) + btn.dataset.token + el.value.slice(e);
                    el.focus();
                    el.setSelectionRange(s + btn.dataset.token.length, s + btn.dataset.token.length);
                    prev();
                });
            });

            // Pakai contoh format
            document.querySelectorAll('[data-template]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    $('tpl').value = btn.dataset.template;
                    $('tpl').focus();
                    prev();
                });
            });

            ['tpl', 'awal'].forEach((id) => $(id).addEventListener('input', prev));
            prev();
        })();
    </script>
</x-app-layout>