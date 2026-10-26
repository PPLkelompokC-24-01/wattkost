<?php

namespace App\Http\Controllers;

use App\Http\Requests\PemakaianHarian\StorePemakaianHarianRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** PBI-02 Catat durasi pemakaian harian. Satu halaman: pilih tanggal, isi jam pakai setiap alat. */
class PemakaianHarianController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $dipilih = $this->bacaTanggal($request->query('tanggal'));
        // ?tanggal= salah format / tidak ada / di masa depan → hari ini
        $tanggal = $dipilih?->lte(today()) ? $dipilih : today();

        return view('pemakaian.index', [
            'tanggal' => $tanggal,
            'daftarAlat' => $user->alatListrik()->orderBy('nama_alat')->get(),
            // [alat_listrik_id => jam_pakai] untuk mengisi nilai lama (UC-02 A1)
            'jamTercatat' => $user->pemakaianHarian()->whereDate('tanggal', $tanggal)->pluck('jam_pakai', 'alat_listrik_id'),
        ]);
    }

    /** Jam > 0 → buat/ubah catatan (unique alat + tanggal, jadi tidak dobel). Jam kosong/0 → hapus catatan lama. */
    public function store(StorePemakaianHarianRequest $request): RedirectResponse
    {
        $user = $request->user();
        $tanggal = Carbon::parse($request->validated('tanggal'))->startOfDay();

        DB::transaction(function () use ($request, $user, $tanggal) {
            foreach ($request->validated('jam') as $alatId => $jam) {
                if ((float) $jam > 0) {
                    $user->pemakaianHarian()->updateOrCreate(['alat_listrik_id' => $alatId, 'tanggal' => $tanggal], ['jam_pakai' => $jam]);
                } else {
                    $user->pemakaianHarian()->where('alat_listrik_id', $alatId)->whereDate('tanggal', $tanggal)->delete();
                }
            }
        });

        return redirect()->route('pemakaian.index', ['tanggal' => $tanggal->toDateString()])
            ->with('sukses', 'Pemakaian tanggal '.$tanggal->translatedFormat('d M Y').' berhasil disimpan.');
    }

    /** "2026-10-14" → Carbon 00:00; null kalau bukan tanggal Y-m-d yang benar (mis. 2026-02-31). */
    private function bacaTanggal(mixed $nilai): ?Carbon
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }

        $tanggal = Carbon::createFromFormat('!Y-m-d', $nilai);

        return $tanggal->format('Y-m-d') === $nilai ? $tanggal : null;
    }
}
