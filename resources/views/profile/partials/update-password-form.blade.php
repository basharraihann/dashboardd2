<header>
    <h3 class="pf-title">Ubah Password</h3>
    <p class="pf-desc">Gunakan password yang panjang dan sulit ditebak agar akun tetap aman.</p>
</header>

<form method="post" action="{{ route('password.update') }}" class="pf-body pf-form">
    @csrf
    @method('put')

    <div class="pf-fields">
        <div class="pf-span pf-half">
            <label for="update_password_current_password" class="pf-label">Password Saat Ini</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password"
                class="pf-input @if ($errors->updatePassword->has('current_password')) is-error @endif">
            @if ($errors->updatePassword->has('current_password'))
                <p class="pf-error">{{ $errors->updatePassword->first('current_password') }}</p>
            @endif
        </div>

        <div>
            <label for="update_password_password" class="pf-label">Password Baru</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password"
                class="pf-input @if ($errors->updatePassword->has('password')) is-error @endif">
            @if ($errors->updatePassword->has('password'))
                <p class="pf-error">{{ $errors->updatePassword->first('password') }}</p>
            @endif
        </div>

        <div>
            <label for="update_password_password_confirmation" class="pf-label">Konfirmasi Password Baru</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                class="pf-input @if ($errors->updatePassword->has('password_confirmation')) is-error @endif">
            @if ($errors->updatePassword->has('password_confirmation'))
                <p class="pf-error">{{ $errors->updatePassword->first('password_confirmation') }}</p>
            @endif
        </div>
    </div>

    <div class="pf-actions">
        <button type="submit" class="pf-btn pf-btn--primary">Simpan Password</button>

        @if (session('status') === 'password-updated')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                class="pf-saved">Password diperbarui.</p>
        @endif
    </div>
</form>
