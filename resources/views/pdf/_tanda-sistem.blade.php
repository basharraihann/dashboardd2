{{--
    Tanda kecil "dibuat di sistem" untuk semua PDF.
    - position: fixed -> DomPDF mengulangnya di SETIAP halaman.
    - Ditaruh di margin bawah kanan (bottom negatif), jadi tidak menimpa isi dokumen.
    - @once: di pdf.merged (yang meng-include spd, rincian-biaya, pengeluaran-riil)
      tanda hanya dirender sekali, tidak bertumpuk.
    Pemakaian: @include('pdf._tanda-sistem') tepat setelah <body>.
--}}
@once
    {{-- $tanpaTanda = true: dokumen ini tidak diberi tanda (mis. berkas SPD gabungan) --}}
    @if (empty($tanpaTanda))
    <div
        style="position: fixed; right: 0; bottom: -16px; font-family: Arial, sans-serif; font-size: 6.5pt; color: #9ca3af; text-align: right; line-height: 1;">
        Dokumen ini dibuat melalui Sistem SPJ &middot; {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
    </div>
    @endif
@endonce
