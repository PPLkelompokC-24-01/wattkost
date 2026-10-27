<?php

// SEMENTARA P3 - hapus sebelum merge (file asli milik P1, PR 3a)
// Sengaja TIDAK final supaya bisa di-mock di unit test P3. Tidak berisi rumus apa pun.

namespace App\Services;

use App\Data\EstimasiAlat;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use LogicException;

class KonsumsiService
{
    public function hitungKwh(int $dayaWatt, int $jumlah, float $jam): float
    {
        throw new LogicException('KonsumsiService belum diimplementasikan (menunggu PR 3a P1).');
    }

    /** @return Collection<int, EstimasiAlat> */
    public function estimasiBulanan(User $user, int $tahun, int $bulan): Collection
    {
        throw new LogicException('KonsumsiService belum diimplementasikan (menunggu PR 3a P1).');
    }

    /** @return Collection<int, EstimasiAlat> */
    public function estimasiHarian(User $user, CarbonInterface $tanggal): Collection
    {
        throw new LogicException('KonsumsiService belum diimplementasikan (menunggu PR 3a P1).');
    }

    public function kwhPerTanggal(User $user, CarbonInterface $awal, CarbonInterface $akhir): Collection
    {
        throw new LogicException('KonsumsiService belum diimplementasikan (menunggu PR 3a P1).');
    }
}
