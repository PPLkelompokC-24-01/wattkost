<?php

namespace App\Http\Requests\PemakaianHarian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Validasi simpan jam pemakaian satu tanggal (PBI 02). Key array `jam` = alat_listrik_id. */
class StorePemakaianHarianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // kepemilikan alat dicek di after()
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'jam' => ['required', 'array'],
            'jam.*' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ];
    }

    /** Setiap key `jam` harus alat aktif milik user login; alat orang lain / sudah dihapus ditolak. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('jam')) {
                    return;
                }

                $idAlat = array_keys($this->input('jam'));

                if ($this->user()->alatListrik()->whereKey($idAlat)->count() !== count($idAlat)) {
                    $validator->errors()->add('jam', 'Ada alat yang tidak terdaftar di daftar alat Anda.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tanggal.before_or_equal' => 'Tanggal tidak boleh melewati hari ini.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'tanggal' => 'tanggal',
            'jam' => 'jam pakai',
            'jam.*' => 'jam pakai',
        ];
    }
}
