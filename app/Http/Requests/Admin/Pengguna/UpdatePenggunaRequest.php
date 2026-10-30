<?php

namespace App\Http\Requests\Admin\Pengguna;

use Illuminate\Validation\Rule;

/** Validasi ubah akun: email milik akun itu sendiri tidak dianggap dobel, sandi kosong = tidak diubah. */
class UpdatePenggunaRequest extends StorePenggunaRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('pengguna'))],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
