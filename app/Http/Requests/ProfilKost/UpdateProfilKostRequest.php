<?php

namespace App\Http\Requests\ProfilKost;

use App\Services\TarifService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validasi simpan profil kost & tarif (PBI 15). Dipakai untuk isi pertama maupun ubah (updateOrCreate). */
class UpdateProfilKostRequest extends FormRequest
{
    /** Daya listrik rumah tangga PLN yang diterima (VA). */
    public const DAYA_VA = [450, 900, 1300, 2200, 3500, 4400, 5500, 6600];

    public function authorize(): bool
    {
        return true; // profil selalu diambil lewat $request->user()->profilKost(), tanpa id di route
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama_kost' => ['required', 'string', 'max:100'],
            'golongan_tarif' => ['nullable', Rule::in(array_keys(app(TarifService::class)->daftarGolongan()))],
            'daya_va' => ['required', 'integer', Rule::in(self::DAYA_VA)],
            'tarif_per_kwh' => ['required', 'numeric', 'min:100', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $daftar = collect(self::DAYA_VA)->map(fn (int $daya) => angka($daya, 0))->join(', ', ', atau ');

        return [
            'daya_va.in' => "Daya listrik harus salah satu dari {$daftar} VA.",
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'nama_kost' => 'nama kost',
            'golongan_tarif' => 'golongan tarif',
            'daya_va' => 'daya listrik (VA)',
            'tarif_per_kwh' => 'tarif per kWh',
        ];
    }
}
