<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlatListrik\StoreAlatListrikRequest;
use App\Http\Requests\AlatListrik\UpdateAlatListrikRequest;
use App\Models\AlatListrik;
use App\Models\KatalogAlat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** PBI-01 Kelola data alat listrik. Tambah & ubah lewat modal di halaman index (lihat KONVENSI.md). */
class AlatListrikController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $cari = $request->query('q');

        return view('alat.index', [
            'daftarAlat' => $user->alatListrik()
                ->when($cari, fn ($query) => $query->where('nama_alat', 'like', "%{$cari}%"))
                ->orderBy('nama_alat')->get(),
            // ?ubah=ID milik user lain / tidak ada → 404
            'ubah' => $request->filled('ubah') ? $user->alatListrik()->findOrFail($request->integer('ubah')) : null,
            'katalog' => KatalogAlat::orderBy('nama_alat')->get(['id', 'nama_alat', 'kategori', 'daya_watt']),
        ]);
    }

    public function store(StoreAlatListrikRequest $request): RedirectResponse
    {
        $request->user()->alatListrik()->create($request->validated());

        return redirect()->route('alat.index')->with('sukses', 'Alat berhasil ditambahkan.');
    }

    public function update(UpdateAlatListrikRequest $request, AlatListrik $alat): RedirectResponse
    {
        Gate::authorize('update', $alat);
        $alat->update($request->validated());

        return redirect()->route('alat.index')->with('sukses', 'Alat berhasil diperbarui.');
    }

    public function destroy(AlatListrik $alat): RedirectResponse
    {
        Gate::authorize('delete', $alat);
        $alat->delete(); // soft delete: riwayat pemakaian tetap ada

        return redirect()->route('alat.index')->with('sukses', 'Alat dihapus. Riwayat pemakaiannya tetap tersimpan.');
    }
}
