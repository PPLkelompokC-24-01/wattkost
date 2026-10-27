<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilKost\UpdateProfilKostRequest;
use App\Services\TarifService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PBI-15 Kelola profil kost & tarif. Satu halaman (desain "Profil Kost"): form langsung di profil.show,
 * terisi kalau profil sudah ada, kosong kalau belum (UC-15 A1). Simpan pertama & ubah lewat updateOrCreate.
 */
class ProfilKostController extends Controller
{
    public function show(Request $request, TarifService $tarifService): View
    {
        return view('profil.show', [
            'profil' => $request->user()->profilKost, // null = belum diisi
            'daftarGolongan' => $tarifService->daftarGolongan(),
            'daftarDaya' => UpdateProfilKostRequest::DAYA_VA,
        ]);
    }

    public function update(UpdateProfilKostRequest $request): RedirectResponse
    {
        // Lewat relasi user login: user_id tidak bisa dikirim dari form, profil orang lain tidak tersentuh
        $request->user()->profilKost()->updateOrCreate([], $request->validated());

        return redirect()->route('profil.show')->with('sukses', 'Profil kost berhasil disimpan.');
    }
}
