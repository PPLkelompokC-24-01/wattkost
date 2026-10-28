<?php

namespace App\Http\Requests\Token;

use Illuminate\Foundation\Http\FormRequest;

/** Validasi input sisa kWh dari meteran (PBI-07). Aturan yang sama dipakai UpdateSisaKwhRequest. */
class StoreSisaKwhRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // kepemilikan dicek SisaKwhPolicy
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sisa_kwh' => ['required', 'numeric', 'min:0', 'max:10000'],
            'dicatat_pada' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'sisa_kwh' => 'sisa kWh',
            'dicatat_pada' => 'waktu pembacaan',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'dicatat_pada.before_or_equal' => 'Waktu pembacaan tidak boleh di masa depan.',
        ];
    }

    /**
     * Data siap simpan. Waktu kosong → sekarang (catat baru) atau tetap nilai lama (ubah).
     *
     * @return array<string, mixed>
     */
    public function dataPembacaan(): array
    {
        return array_filter($this->validated(), fn ($nilai) => $nilai !== null) + $this->waktuDefault();
    }

    /** @return array<string, mixed> */
    protected function waktuDefault(): array
    {
        return ['dicatat_pada' => now()];
    }
}
