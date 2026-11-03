<?php

namespace App\Http\Requests\Token;

use Illuminate\Foundation\Http\FormRequest;

/** Validasi filter rekap token (PBI-06 A3). ?bulan=Y-m, kosong → bulan berjalan. */
class RekapTokenRequest extends FormRequest
{
    /** Gagal validasi → kembali ke rekap bulan berjalan (tanpa ?bulan), error tampil di <x-pilih-bulan>. */
    protected $redirectRoute = 'token.rekap';

    public function authorize(): bool
    {
        return true; // data dibatasi lewat relasi user login
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bulan' => ['bail', 'nullable', 'date_format:Y-m', 'after_or_equal:2020-01', 'before_or_equal:'.now()->format('Y-m')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'bulan.date_format' => 'Periode tidak valid, pilih bulan dari daftar.',
            'bulan.after_or_equal' => 'Rekap paling awal Januari 2020.',
            'bulan.before_or_equal' => 'Periode tidak boleh melewati bulan ini.',
        ];
    }
}
