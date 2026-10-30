<?php

namespace App\Http\Requests\Token;

use Illuminate\Foundation\Http\FormRequest;

/** Validasi catat pembelian token (PBI-05). Aturan yang sama dipakai UpdatePembelianTokenRequest. */
class StorePembelianTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // kepemilikan dicek PembelianTokenPolicy
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tanggal_beli' => ['required', 'date', 'before_or_equal:today'],
            'nominal' => ['required', 'integer', 'min:1000', 'max:10000000'],
            'jumlah_kwh' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'nomor_meter' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'tanggal_beli' => 'tanggal beli',
            'nominal' => 'nominal (Rp)',
            'jumlah_kwh' => 'kWh diperoleh',
            'nomor_meter' => 'nomor meter',
            'keterangan' => 'keterangan',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tanggal_beli.before_or_equal' => 'Tanggal beli tidak boleh di masa depan.',
            'nomor_meter.regex' => 'Nomor meter hanya boleh berisi angka.',
        ];
    }
}
