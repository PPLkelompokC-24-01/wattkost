<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KatalogAlat\StoreKatalogAlatRequest;
use App\Http\Requests\Admin\KatalogAlat\UpdateKatalogAlatRequest;
use App\Models\KatalogAlat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PBI 23 — Kelola katalog alat listrik (admin). Tambah & ubah lewat modal (pola CRUD di KONVENSI.md).
 * Akses dijaga middleware 'role:admin'; katalog tidak dimiliki per user, jadi tanpa Policy.
 */
class KatalogAlatController extends Controller
{
    public function index(Request $request): View
    {
        $daftarKatalog = KatalogAlat::query()
            ->withCount('alatListrik') // alat penghuni yang sudah dihapus (soft delete) tidak ikut dihitung
            // filter_kategori (bukan kategori) supaya error validasi field kategori tidak ikut tampil di filter
            ->when($request->filled('filter_kategori'), fn ($query) => $query->where('kategori', $request->query('filter_kategori')))
            ->when($request->filled('q'), fn ($query) => $query->where('nama_alat', 'like', '%'.$request->query('q').'%'))
            ->orderBy('nama_alat')
            ->paginate(15)->withQueryString();

        return view('admin.katalog.index', [
            'daftarKatalog' => $daftarKatalog,
            // ?ubah=ID yang tidak ada (mis. sudah dihapus admin lain) → 404
            'ubah' => $request->filled('ubah') ? KatalogAlat::findOrFail($request->integer('ubah')) : null,
        ]);
    }

    public function store(StoreKatalogAlatRequest $request): RedirectResponse
    {
        KatalogAlat::create($request->validated());

        return redirect()->route('admin.katalog.index')->with('sukses', 'Alat standar berhasil ditambahkan.');
    }

    public function update(UpdateKatalogAlatRequest $request, KatalogAlat $katalog): RedirectResponse
    {
        $katalog->update($request->validated()); // alat penghuni tidak ikut berubah: nama & daya sudah disalin

        return redirect()->route('admin.katalog.index')->with('sukses', 'Alat standar berhasil diperbarui.');
    }

    public function destroy(KatalogAlat $katalog): RedirectResponse
    {
        $katalog->delete(); // FK nullOnDelete: alat penghuni tetap ada, katalog_alat_id jadi null

        return redirect()->route('admin.katalog.index')->with('sukses', 'Alat standar berhasil dihapus.');
    }
}
