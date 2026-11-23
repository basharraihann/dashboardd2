<header>
    <h3 class="pf-title">Hapus Akun</h3>
    <p class="pf-desc">Tindakan ini permanen dan tidak bisa dibatalkan.</p>
</header>

<div class="pf-body pf-danger-row">
    <p>Setelah akun dihapus, seluruh data akun ini ikut terhapus. Unduh dulu data yang masih Anda perlukan.</p>

    <button type="button" class="pf-btn pf-btn--danger-outline" x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">Hapus Akun</button>
</div>

<x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
    <form method="post" action="{{ route('profile.destroy') }}" class="pf-modal">
        @csrf
        @method('delete')

        <h3 class="pf-title" style="color:#111827">Hapus akun Anda?</h3>
        <p class="pf-desc" style="margin-top:.5rem">
            Seluruh data akun akan dihapus permanen. Masukkan password untuk mengonfirmasi.
        </p>

        <div style="margin-top:1.25rem">
            <label for="password" class="pf-label" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">Password</label>
            <input id="password" name="password" type="password" placeholder="Password"
                class="pf-input @if ($errors->userDeletion->has('password')) is-error @endif">
            @if ($errors->userDeletion->has('password'))
                <p class="pf-error">{{ $errors->userDeletion->first('password') }}</p>
            @endif
        </div>

        <div class="pf-modal-actions">
            <button type="button" class="pf-btn pf-btn--ghost" x-on:click="$dispatch('close')">Batal</button>
            <button type="submit" class="pf-btn pf-btn--danger">Hapus Akun</button>
        </div>
    </form>
</x-modal>
