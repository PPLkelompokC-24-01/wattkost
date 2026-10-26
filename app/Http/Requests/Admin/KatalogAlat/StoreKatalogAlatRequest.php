<?php

namespace App\Http\Requests\Admin\KatalogAlat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi tambah alat standar di katalog (PBI 23). Aturan yang sama dipakai UpdateKatalogAlatRequest. */
class StoreKatalogAlatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama_alat' => ['required', 'string', 'max:100', Rule::unique('katalog_alat', 'nama_alat')],
            'kategori' => ['required', Rule::in(config('wattkost.kategori_alat'))],
            'daya_watt' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'nama_alat' => 'nama alat',
            'kategori' => 'kategori',
            'daya_watt' => 'daya default (W)',
        ];
    }
}
