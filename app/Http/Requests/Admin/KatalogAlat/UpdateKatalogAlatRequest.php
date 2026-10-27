<?php

namespace App\Http\Requests\Admin\KatalogAlat;

use Illuminate\Validation\Rule;

/** Validasi ubah alat standar: sama dengan tambah, tetapi nama milik data itu sendiri tidak dianggap duplikat. */
class UpdateKatalogAlatRequest extends StoreKatalogAlatRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'nama_alat' => ['required', 'string', 'max:100', Rule::unique('katalog_alat', 'nama_alat')->ignore($this->route('katalog'))],
        ];
    }
}
