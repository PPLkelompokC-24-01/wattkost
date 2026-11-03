<?php

namespace App\Http\Controllers;

use App\Http\Requests\Token\RekapTokenRequest;
use App\Services\TokenService;
use Illuminate\View\View;

/** PBI-06 Rekap pengeluaran token per bulan. Semua angka dari TokenService::rekap(). */
class RekapTokenController extends Controller
{
    public function __invoke(RekapTokenRequest $request, TokenService $tokenService): View
    {
        [$awal, $akhir] = periodeBulan($request->validated('bulan'));
        $user = $request->user();

        return view('token.rekap', [
            'periode' => $awal,
            'rekap' => $tokenService->rekap($user, $awal, $akhir),
            'transaksi' => $user->pembelianToken()->whereBetween('tanggal_beli', [$awal, $akhir])
                ->latest('tanggal_beli')->latest('id')->get(),
        ]);
    }
}
