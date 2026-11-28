<?php

namespace App\Http\Requests\Admin\Tantangan;

use App\Models\Tantangan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi tambah tantangan hemat (PBI 20). `dibuat_oleh` diisi controller, bukan dari form.
 * `aktif` boleh tidak dikirim (default kolom = true); form mengirim hidden "0" sebelum toggle.
 */
class StoreTantanganRequest extends FormRequest
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
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'jenis_target' => ['required', Rule::in([Tantangan::JENIS_MAKS_KWH_HARIAN, Tantangan::JENIS_PERSEN_HEMAT])],
            // persen untuk persen_hemat, kWh/hari untuk maks_kwh_harian
            'nilai_target' => ['required', 'numeric', 'gt:0', 'max:100'],
            'durasi_hari' => ['required', 'integer', 'between:1,60'],
            'poin' => ['required', 'integer', 'between:1,1000'],
            'aktif' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'judul' => 'judul',
            'deskripsi' => 'deskripsi',
            'jenis_target' => 'jenis target',
            'nilai_target' => 'nilai target',
            'durasi_hari' => 'durasi (hari)',
            'poin' => 'poin',
            'aktif' => 'status aktif',
        ];
    }
}
