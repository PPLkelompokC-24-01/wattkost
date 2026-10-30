<?php

namespace App\Http\Requests\TargetHemat;

use App\Models\TargetHemat;

/**
 * Validasi ubah target hemat (PBI 11): aturan sama dengan tambah.
 * ASUMSI (menunggu PO): bulan boleh dipindah; cek unik mengabaikan target yang sedang diubah.
 */
class UpdateTargetHematRequest extends StoreTargetHematRequest
{
    protected function targetDiabaikan(): ?TargetHemat
    {
        return $this->route('target');
    }
}
