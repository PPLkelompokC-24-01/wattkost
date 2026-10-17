<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Beranda: ringkasan dari modul lain. Setiap kartu punya satu method privat yang
 * sekarang mengembalikan null (tampil "—") dan diisi P4 setelah service-nya merge.
 * Jangan menulis rumus di sini — panggil service.
 *
 * Bentuk data tiap kartu: ['nilai' => '42,5 kWh', 'note' => 'Diperbarui 08 Okt, 07.30'] (sudah diformat).
 */
class BerandaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('beranda', [
            'profil' => $user->profilKost,
            'sisaKwh' => $this->sisaKwh($user),
            'prediksiHabis' => $this->prediksiHabis($user),
            'pemakaianBulanIni' => $this->pemakaianBulanIni($user),
            'alatTerboros' => $this->alatTerboros($user),
        ]);
    }

    /** Sisa kWh terkini — nanti dari TokenService (PBI 7, Pasangan 2). */
    private function sisaKwh(User $user): ?array
    {
        return null;
    }

    /** Perkiraan token habis — nanti dari PrediksiTokenService (PBI 8). */
    private function prediksiHabis(User $user): ?array
    {
        return null;
    }

    /** kWh & estimasi biaya bulan ini — nanti dari KonsumsiService (PBI 3) + estimasi biaya (PBI 18). */
    private function pemakaianBulanIni(User $user): ?array
    {
        return null;
    }

    /** Alat paling boros bulan ini — nanti dari PeringkatAlatService (PBI 4). */
    private function alatTerboros(User $user): ?array
    {
        return null;
    }
}
