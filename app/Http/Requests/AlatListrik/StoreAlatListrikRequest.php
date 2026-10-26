<?php

namespace App\Http\Requests\AlatListrik;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi tambah alat listrik (PBI-01). Aturan yang sama dipakai UpdateAlatListrikRequest. */
class StoreAlatListrikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // kepemilikan dicek AlatListrikPolicy
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'katalog_alat_id' => ['nullable', 'integer', 'exists:katalog_alat,id'],
            'nama_alat' => ['required', 'string', 'max:100'],
            'kategori' => ['required', Rule::in(config('wattkost.kategori_alat'))],
            'daya_watt' => ['required', 'integer', 'min:1', 'max:10000'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'katalog_alat_id' => 'alat dari katalog',
            'nama_alat' => 'nama alat',
            'kategori' => 'kategori',
            'daya_watt' => 'daya (W)',
            'jumlah' => 'jumlah',
        ];
    }
}
