<?php

// SEMENTARA P3 - hapus sebelum merge (file asli milik P2, PR 7a)
// Sengaja TIDAK final supaya bisa di-mock di unit test P3. Tidak berisi rumus apa pun.
// App\Data\RekapToken belum ada; aman karena rekap() tidak pernah dipanggil sampai selesai.

namespace App\Services;

use App\Data\RekapToken;
use App\Models\SisaKwh;
use App\Models\User;
use Carbon\CarbonInterface;
use LogicException;

class TokenService
{
    public function rekap(User $user, CarbonInterface $awal, CarbonInterface $akhir): RekapToken
    {
        throw new LogicException('TokenService belum diimplementasikan (menunggu PR 7a P2).');
    }

    public function sisaTerkini(User $user): ?SisaKwh
    {
        throw new LogicException('TokenService belum diimplementasikan (menunggu PR 7a P2).');
    }

    public function kwhDibeliAntara(User $user, CarbonInterface $dari, CarbonInterface $sampai): float
    {
        throw new LogicException('TokenService belum diimplementasikan (menunggu PR 7a P2).');
    }
}
