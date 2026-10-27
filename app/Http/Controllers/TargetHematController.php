<?php

namespace App\Http\Controllers;

use App\Http\Requests\TargetHemat\StoreTargetHematRequest;
use App\Http\Requests\TargetHemat\UpdateTargetHematRequest;
use App\Models\TargetHemat;
use App\Services\TargetHematService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** PBI 11 — Kelola Target Hemat Bulanan. Tambah & ubah lewat modal (pola CRUD di KONVENSI.md). */
class TargetHematController extends Controller
{
    public function __construct(private TargetHematService $service) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $riwayat = $user->targetHemat()->orderByDesc('tahun')->orderByDesc('bulan')->paginate(12);
        $bulanIni = $user->targetHemat()->where('tahun', now()->year)->where('bulan', now()->month)->first();

        return view('target.index', [
            'bulanIni' => $bulanIni,
            'progresBulanIni' => $bulanIni ? $this->service->progres($user, $bulanIni) : null,
            'riwayat' => $riwayat,
            'progres' => $riwayat->getCollection()->mapWithKeys(fn (TargetHemat $t) => [$t->id => $this->service->progres($user, $t)]),
            // ?ubah=ID milik user lain / tidak ada → 404
            'ubah' => $request->filled('ubah') ? $user->targetHemat()->findOrFail($request->integer('ubah')) : null,
        ]);
    }

    public function store(StoreTargetHematRequest $request): RedirectResponse
    {
        $request->user()->targetHemat()->create($request->dataTarget());

        return redirect()->route('target.index')->with('sukses', 'Target berhasil disimpan.');
    }

    public function update(UpdateTargetHematRequest $request, TargetHemat $target): RedirectResponse
    {
        Gate::authorize('update', $target);
        $target->update($request->dataTarget());

        return redirect()->route('target.index')->with('sukses', 'Target berhasil diperbarui.');
    }

    public function destroy(TargetHemat $target): RedirectResponse
    {
        Gate::authorize('delete', $target);
        $target->delete();

        return redirect()->route('target.index')->with('sukses', 'Target dihapus.');
    }
}
