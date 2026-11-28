<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tantangan\StoreTantanganRequest;
use App\Http\Requests\Admin\Tantangan\UpdateTantanganRequest;
use App\Models\Tantangan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PBI 20 — Kelola Tantangan Hemat (admin). Tambah & ubah lewat modal (pola CRUD di KONVENSI.md).
 * Akses dijaga middleware 'role:admin'; tantangan tidak dimiliki per user, jadi tanpa Policy.
 */
class TantanganController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.tantangan.index', [
            'daftarTantangan' => Tantangan::withCount('pesertaTantangan')->orderBy('id')->paginate(15)->withQueryString(),
            // ?ubah=ID yang tidak ada (mis. sudah dihapus admin lain) → 404
            'ubah' => $request->filled('ubah') ? Tantangan::findOrFail($request->integer('ubah')) : null,
        ]);
    }

    public function store(StoreTantanganRequest $request): RedirectResponse
    {
        Tantangan::create([...$request->validated(), 'dibuat_oleh' => $request->user()->id]);

        return redirect()->route('admin.tantangan.index')->with('sukses', 'Tantangan berhasil ditambahkan.');
    }

    public function update(UpdateTantanganRequest $request, Tantangan $tantangan): RedirectResponse
    {
        $tantangan->update($request->validated());

        return redirect()->route('admin.tantangan.index')->with('sukses', 'Tantangan berhasil diperbarui.');
    }

    public function destroy(Tantangan $tantangan): RedirectResponse
    {
        // A3: riwayat & poin peserta ikut terhapus (cascade) kalau dihapus, jadi ditolak → nonaktifkan saja.
        if ($tantangan->pesertaTantangan()->exists()) {
            return redirect()->route('admin.tantangan.index')
                ->with('gagal', "Tantangan {$tantangan->judul} sudah punya peserta, jadi tidak bisa dihapus. Nonaktifkan saja lewat tombol Edit.");
        }

        $tantangan->delete();

        return redirect()->route('admin.tantangan.index')->with('sukses', 'Tantangan berhasil dihapus.');
    }
}
