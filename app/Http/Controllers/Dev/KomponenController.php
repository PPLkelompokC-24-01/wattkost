<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dev\SimpanContohRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Halaman katalog komponen + "Contoh CRUD" (hanya local/testing).
 * Data contoh tidak disimpan ke tabel; store/update/destroy hanya memvalidasi lalu redirect,
 * supaya alur modal tambah/ubah/error bisa dicoba dan ditiru pasangan lain.
 */
class KomponenController extends Controller
{
    private const CONTOH_ALAT = [
        1 => ['nama_alat' => 'Kipas angin', 'kategori' => 'Pendingin', 'daya_watt' => 45],
        2 => ['nama_alat' => 'Rice cooker', 'kategori' => 'Dapur', 'daya_watt' => 350],
        3 => ['nama_alat' => 'Laptop', 'kategori' => 'Elektronik', 'daya_watt' => 65],
    ];

    public function index(Request $request): View
    {
        $daftar = $this->daftarContoh();
        // Di modul asli: $request->user()->alatListrik()->findOrFail(...)
        $ubah = $request->filled('ubah') ? ($daftar->get($request->integer('ubah')) ?? abort(404)) : null;

        return view('dev.komponen', ['contohAlat' => $daftar, 'ubah' => $ubah, 'contohGrafik' => $this->contohGrafik()]);
    }

    public function store(SimpanContohRequest $request): RedirectResponse
    {
        return $this->kembali('Contoh: alat berhasil ditambahkan (data tidak benar-benar disimpan).');
    }

    public function update(SimpanContohRequest $request, int $id): RedirectResponse
    {
        abort_unless($this->daftarContoh()->has($id), 404);

        return $this->kembali('Contoh: alat berhasil diperbarui (data tidak benar-benar disimpan).');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->daftarContoh()->has($id), 404);

        return $this->kembali('Contoh: alat berhasil dihapus (data tidak benar-benar dihapus).');
    }

    /** @return Collection<int, object> */
    private function daftarContoh(): Collection
    {
        return collect(self::CONTOH_ALAT)->map(fn (array $alat, int $id) => (object) ['id' => $id, ...$alat]);
    }

    /** Data grafik 30 hari (Oktober 2026) yang tetap, supaya tampilan bisa dibandingkan dengan desain. */
    private function contohGrafik(): array
    {
        $kwhHarian = [2.8, 3.1, 2.9, 3.6, 3.3, 4.2, 3.8, 3.5, 4.0, 3.2, 2.9, 3.4, 3.9, 4.4, 3.7,
            3.1, 3.0, 3.6, 4.1, 3.8, 3.3, 3.5, 4.6, 4.0, 3.4, 3.2, 3.9, 4.3, 3.6, 4.1];

        return [
            'hari' => array_map(fn (int $h) => sprintf('%02d Okt', $h), range(1, 30)),
            'kwhHarian' => $kwhHarian,
        ];
    }

    private function kembali(string $pesan): RedirectResponse
    {
        return redirect()->to(route('dev.komponen').'#contoh-crud')->with('sukses', $pesan);
    }
}
