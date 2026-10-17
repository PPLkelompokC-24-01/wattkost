<?php

namespace App\Http\Requests\Dev;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi form "Contoh CRUD" di /dev/komponen. Dipakai untuk tambah & ubah. */
class SimpanContohRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama_alat' => ['required', 'string', 'max:100'],
            'kategori' => ['required', Rule::in(config('wattkost.kategori_alat'))],
            'daya_watt' => ['required', 'integer', 'min:1', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'nama_alat' => 'nama alat',
            'kategori' => 'kategori',
            'daya_watt' => 'daya (W)',
        ];
    }
}
