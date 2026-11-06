<?php

namespace App\Http\Requests\JejakKarbon;

use Illuminate\Foundation\Http\FormRequest;

/** Validasi ubah faktor emisi milik penghuni (PBI 24, UC-24 A2/A3). Satuan kg CO2/kWh. */
class UpdateFaktorEmisiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // decimal:0,4 mengikuti kolom decimal(6,4): 0.00001 lolos gt:0 tapi tersimpan 0.0000 di MySQL
            'faktor_emisi' => ['required', 'numeric', 'decimal:0,4', 'gt:0', 'max:5'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'faktor_emisi' => 'faktor emisi',
        ];
    }
}
