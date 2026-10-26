<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TipsHemat\StoreTipsHematRequest;
use App\Http\Requests\Admin\TipsHemat\UpdateTipsHematRequest;
use App\Models\TipsHemat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PBI 13 — Kelola Tips Hemat (admin). Tambah & ubah lewat modal (pola CRUD di KONVENSI.md).
 * Akses dijaga middleware 'role:admin'; tips tidak dimiliki per user, jadi tanpa Policy.
 */
class TipsHematController extends Controller
{
    public function index(Request $request): View
    {
        $daftarTips = TipsHemat::query()
            // filter_kategori (bukan kategori) supaya error validasi field kategori tidak ikut tampil di filter
            ->when($request->filled('filter_kategori'), fn ($query) => $query->where('kategori', $request->query('filter_kategori')))
            ->when($request->filled('q'), fn ($query) => $query->where('judul', 'like', '%'.$request->query('q').'%'))
            ->latest('updated_at')->latest('id')
            ->paginate(10)->withQueryString();

        return view('admin.tips.index', [
            'daftarTips' => $daftarTips,
            // ?ubah=ID yang tidak ada (mis. sudah dihapus admin lain) → 404
            'ubah' => $request->filled('ubah') ? TipsHemat::findOrFail($request->integer('ubah')) : null,
        ]);
    }

    public function store(StoreTipsHematRequest $request): RedirectResponse
    {
        TipsHemat::create([...$request->validated(), 'dibuat_oleh' => $request->user()->id]);

        return redirect()->route('admin.tips.index')->with('sukses', 'Tips berhasil ditambahkan.');
    }

    public function update(UpdateTipsHematRequest $request, TipsHemat $tip): RedirectResponse
    {
        $tip->update($request->validated());

        return redirect()->route('admin.tips.index')->with('sukses', 'Tips berhasil diperbarui.');
    }

    public function destroy(TipsHemat $tip): RedirectResponse
    {
        $tip->delete();

        return redirect()->route('admin.tips.index')->with('sukses', 'Tips berhasil dihapus.');
    }
}
