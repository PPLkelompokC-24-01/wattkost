<?php

namespace App\Http\Controllers;

use App\Http\Requests\Token\StoreSisaKwhRequest;
use App\Http\Requests\Token\UpdateSisaKwhRequest;
use App\Models\SisaKwh;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** PBI-07 Input sisa kWh dari meteran. Setiap input = baris baru (riwayat), ubah lewat modal. */
class SisaKwhController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $riwayat = $user->sisaKwh()->latest('dicatat_pada')->latest('id')->limit(14)->get();

        return view('meteran.index', [
            'riwayat' => $riwayat,
            // TODO(PBI-07): ganti ke TokenService::sisaTerkini setelah TokenService merge.
            'terkini' => $riwayat->first(),
            // ?ubah=ID milik user lain / tidak ada → 404
            'ubah' => $request->filled('ubah') ? $user->sisaKwh()->findOrFail($request->integer('ubah')) : null,
        ]);
    }

    public function store(StoreSisaKwhRequest $request): RedirectResponse
    {
        $request->user()->sisaKwh()->create($request->dataPembacaan());
        // TODO(PBI-07): peringatan "Sisa kWh naik padahal belum ada pembelian token tercatat"
        // ditambahkan setelah TokenService merge (cek pembelian sejak pembacaan sebelumnya).

        return redirect()->route('meteran.index')->with('sukses', 'Sisa kWh berhasil dicatat.');
    }

    public function update(UpdateSisaKwhRequest $request, SisaKwh $sisaKwh): RedirectResponse
    {
        Gate::authorize('update', $sisaKwh);
        $sisaKwh->update($request->dataPembacaan());

        return redirect()->route('meteran.index')->with('sukses', 'Pembacaan meteran berhasil diperbarui.');
    }

    public function destroy(SisaKwh $sisaKwh): RedirectResponse
    {
        Gate::authorize('delete', $sisaKwh);
        $sisaKwh->delete();

        return redirect()->route('meteran.index')->with('sukses', 'Pembacaan meteran dihapus.');
    }
}
