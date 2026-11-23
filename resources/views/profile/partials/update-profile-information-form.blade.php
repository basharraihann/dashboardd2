<header>
    <h3 class="pf-title">Informasi Profil</h3>
    <p class="pf-desc">Perbarui nama dan alamat email akun Anda.</p>
</header>

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="pf-body pf-form">
    @csrf
    @method('patch')

    <div class="pf-fields">
        <div>
            <label for="name" class="pf-label">Nama Lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name"
                class="pf-input @error('name') is-error @enderror">
            @error('name')
                <p class="pf-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="pf-label">Alamat Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                class="pf-input @error('email') is-error @enderror">
            @error('email')
                <p class="pf-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <div class="pf-warn">
            Alamat email Anda belum diverifikasi.
            <button form="send-verification" class="pf-link">Kirim ulang email verifikasi</button>

            @if (session('status') === 'verification-link-sent')
                <p class="pf-ok">Link verifikasi baru sudah dikirim ke email Anda.</p>
            @endif
        </div>
    @endif

    <div class="pf-actions">
        <button type="submit" class="pf-btn pf-btn--primary">Simpan Perubahan</button>

        @if (session('status') === 'profile-updated')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                class="pf-saved">Tersimpan.</p>
        @endif
    </div>
</form>
