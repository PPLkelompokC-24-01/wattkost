<?php

namespace App\Http\Requests\Admin\TipsHemat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi ubah tips hemat (PBI 13). `dibuat_oleh` tidak ikut diubah. */
class UpdateTipsHematRequest extends FormRequest
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
