<?php

namespace App\Http\Controllers;

use App\Models\PesertaTantangan;
use App\Models\Tantangan;
use App\Services\TantanganService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PBI 20 — Tantangan Hemat (penghuni): tab Tersedia / Diikuti / Selesai + ikut tantangan.
 * Tanpa cron: peserta yang durasinya sudah lewat dinilai setiap halaman ini dibuka.
 * Data peserta selalu diambil lewat relasi user yang login.
 */
class TantanganController extends Controller
{
    public const TAB = ['tersedia', 'diikuti', 'selesai'];

    public function __construct(private TantanganService $tantangan) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $this->tantangan->nilaiYangSelesai($user);
        $diikuti = $user->pesertaTantangan()->where('status', PesertaTantangan::STATUS_BERJALAN)
            ->with('tantangan')->orderBy('selesai')->get();

        return view('tantangan.index', [
            'tab' => in_array($request->query('tab'), self::TAB, true) ? $request->query('tab') : 'tersedia',
            'tersedia' => Tantangan::where('aktif', true)->orderBy('id')->get(),
            'diikuti' => $diikuti,
            'progres' => $diikuti->mapWithKeys(fn (PesertaTantangan $p) => [$p->id => $this->tantangan->progres($p)]),
            'idBerjalan' => $diikuti->pluck('tantangan_id')->all(), // A1: tombol Ikuti nonaktif
            'selesai' => $user->pesertaTantangan()->whereIn('status', [PesertaTantangan::STATUS_BERHASIL, PesertaTantangan::STATUS_GAGAL])
                ->with('tantangan')->latest('dinilai_pada')->latest('id')->get(),
            'totalPoin' => $this->tantangan->totalPoin($user),
        ]);
    }

    public function ikuti(Request $request, Tantangan $tantangan): RedirectResponse
    {
        abort_unless($tantangan->aktif, 404);
        $user = $request->user();
        $this->tantangan->nilaiYangSelesai($user); // yang sudah lewat selesai dinilai dulu, supaya boleh ikut lagi

        if ($this->tantangan->sedangBerjalan($user, $tantangan)) {
            return redirect()->route('tantangan.index')->with('gagal', 'Kamu sudah mengikuti tantangan ini dan masih berjalan.');
        }

        $this->tantangan->ikuti($user, $tantangan);

        return redirect()->route('tantangan.index', ['tab' => 'diikuti'])
            ->with('sukses', "Kamu mulai mengikuti tantangan {$tantangan->judul}.");
    }
}
