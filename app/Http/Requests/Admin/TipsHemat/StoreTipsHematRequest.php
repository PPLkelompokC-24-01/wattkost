<?php

namespace App\Http\Requests\Admin\TipsHemat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi tambah tips hemat (PBI 13). `dibuat_oleh` diisi controller, bukan dari form. */
class StoreTipsHematRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:150'],
            'isi' => ['required', 'string', 'max:2000'],
            'kategori' => ['required', Rule::in([...config('wattkost.kategori_alat'), 'Umum'])],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'judul' => 'judul',
            'isi' => 'isi tips',
            'kategori' => 'kategori',
        ];
    }
}
