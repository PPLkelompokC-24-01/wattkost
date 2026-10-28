<?php

namespace App\Policies;

use App\Models\PembelianToken;
use App\Models\User;

/** Pembelian token hanya boleh diubah/dihapus pemiliknya (didaftarkan otomatis oleh Laravel lewat nama kelas). */
class PembelianTokenPolicy
{
    public function update(User $user, PembelianToken $pembelian): bool
    {
        return $pembelian->user_id === $user->id;
    }

    public function delete(User $user, PembelianToken $pembelian): bool
    {
        return $pembelian->user_id === $user->id;
    }
}
