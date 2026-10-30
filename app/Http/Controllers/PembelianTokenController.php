<?php

namespace App\Http\Controllers;

use App\Http\Requests\Token\StorePembelianTokenRequest;
use App\Http\Requests\Token\UpdatePembelianTokenRequest;
use App\Models\PembelianToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** PBI-05 Catat pembelian token. Catat & ubah lewat modal di halaman index (lihat KONVENSI.md). */
class PembelianTokenController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('token.index', [
            'riwayat' => $user->pembelianToken()->latest('tanggal_beli')->latest('id')->paginate(10)->withQueryString(),
            // ?ubah=ID milik user lain / tidak ada → 404
            'ubah' => $request->filled('ubah') ? $user->pembelianToken()->findOrFail($request->integer('ubah')) : null,
            // default isian nomor meter di form catat
            'nomorMeterTerakhir' => $user->pembelianToken()->whereNotNull('nomor_meter')
                ->latest('tanggal_beli')->latest('id')->value('nomor_meter'),
        ]);
    }

    public function store(StorePembelianTokenRequest $request): RedirectResponse
    {
        $request->user()->pembelianToken()->create($request->validated());

        return redirect()->route('token.index')->with('sukses', 'Pembelian token berhasil dicatat.');
    }

    public function update(UpdatePembelianTokenRequest $request, PembelianToken $pembelian): RedirectResponse
    {
        Gate::authorize('update', $pembelian);
        $pembelian->update($request->validated());

        return redirect()->route('token.index')->with('sukses', 'Pembelian token berhasil diperbarui.');
    }

    public function destroy(PembelianToken $pembelian): RedirectResponse
    {
        Gate::authorize('delete', $pembelian);
        $pembelian->delete();

        return redirect()->route('token.index')->with('sukses', 'Pembelian token dihapus.');
    }
}
