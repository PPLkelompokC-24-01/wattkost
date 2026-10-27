<?php

namespace App\Services;

use App\Models\User;

/** Tarif listrik penghuni (PBI 15). Kontrak dikunci di docs/ARSITEKTUR.md. */
final class TarifService
{
    /** Tarif dari profil kost user; null kalau profil belum diisi (dipakai PBI 18 A2). */
    public function tarifPerKwh(User $user): ?float
    {
        $tarif = $user->profilKost?->tarif_per_kwh;

        return $tarif === null ? null : (float) $tarif;
    }

    /**
     * Daftar golongan dari config('wattkost.golongan_tarif') untuk dropdown.
     * Kunci golongan mengandung titik ("R-1/TR 1.300 VA"), jadi ambil array utuh, jangan dot notation.
     *
     * @return array<string, array{daya_va: int, tarif: float}>
     */
    public function daftarGolongan(): array
    {
        return config('wattkost.golongan_tarif', []);
    }
}
