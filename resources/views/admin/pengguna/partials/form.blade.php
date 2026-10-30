{{-- Isian form akun; dipakai modal tambah ($pengguna = null) & modal ubah ($pengguna = akun yang diubah). Sandi tidak pernah diisi ulang. --}}
<x-input label="Nama" name="name" :value="$pengguna?->name" placeholder="Nama lengkap" maxlength="100" />
<x-input label="Email" name="email" type="email" :value="$pengguna?->email" placeholder="nama@contoh.com" maxlength="255" />
<x-select label="Peran" name="role" stacked>
    <option value="penghuni" @selected(old('role', $pengguna?->role ?? 'penghuni') === 'penghuni')>Penghuni</option>
    <option value="admin" @selected(old('role', $pengguna?->role) === 'admin')>Admin</option>
</x-select>
<x-input label="Kata Sandi" name="password" type="password" autocomplete="new-password"
    :placeholder="$pengguna ? 'Kosongkan kalau tidak diubah' : 'Minimal 8 karakter'" />
<x-input label="Ulangi Kata Sandi" name="password_confirmation" type="password" autocomplete="new-password" />
