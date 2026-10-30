<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pengguna\StorePenggunaRequest;
use App\Http\Requests\Admin\Pengguna\UpdatePenggunaRequest;
use App\Models\User;
use App\Services\PenggunaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PBI 25 — Kelola pengguna (admin). Tambah & ubah lewat modal (pola CRUD di KONVENSI.md).
 * Akses dijaga middleware 'role:admin'. `role` tidak fillable, jadi selalu di-set eksplisit di sini.
 */
class PenggunaController extends Controller
{
    public function __construct(private PenggunaService $service) {}

    public function index(Request $request): View
    {
        $cari = $request->string('q')->trim()->toString();

        $daftarPengguna = User::query()
            ->withCount('alatListrik') // alat yang sudah dihapus (soft delete) tidak ikut dihitung
            // filter_role (bukan role) supaya error validasi field role tidak ikut tampil di filter
            ->when($request->filled('filter_role'), fn (Builder $query) => $query->where('role', $request->string('filter_role')->toString()))
            ->when($cari !== '', fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$cari}%")->orWhere('email', 'like', "%{$cari}%")))
            ->latest()->orderByDesc('id')
            ->paginate(10)->withQueryString();

        return view('admin.pengguna.index', [
            'daftarPengguna' => $daftarPengguna,
            'ubah' => $request->filled('ubah') ? User::findOrFail($request->integer('ubah')) : null, // id tidak ada → 404
        ]);
    }

    public function store(StorePenggunaRequest $request): RedirectResponse
    {
        $pengguna = new User($request->safe()->only(['name', 'email', 'password'])); // password di-hash oleh cast 'hashed'
        $pengguna->role = $request->validated('role');
        $pengguna->save();

        return redirect()->route('admin.pengguna.index')->with('sukses', 'Akun berhasil ditambahkan.');
    }

    public function update(UpdatePenggunaRequest $request, User $pengguna): RedirectResponse
    {
        if ($alasan = $this->service->alasanTolakUbahPeran($request->user(), $pengguna, $request->validated('role'))) {
            return redirect()->route('admin.pengguna.index')->with('gagal', $alasan);
        }

        $pengguna->fill($request->safe()->only(['name', 'email']));
        $pengguna->role = $request->validated('role');
        if ($request->filled('password')) {
            $pengguna->password = $request->validated('password'); // kosong = sandi lama tetap
        }
        $pengguna->save();

        return redirect()->route('admin.pengguna.index')->with('sukses', 'Akun berhasil diperbarui.');
    }

    public function destroy(Request $request, User $pengguna): RedirectResponse
    {
        if ($alasan = $this->service->alasanTolakHapus($request->user(), $pengguna)) {
            return redirect()->route('admin.pengguna.index')->with('gagal', $alasan);
        }

        $pengguna->delete(); // FK cascadeOnDelete: alat, pemakaian, token, sisa kWh, profil, target ikut terhapus

        return redirect()->route('admin.pengguna.index')->with('sukses', 'Akun berhasil dihapus.');
    }
}
