<?php

namespace App\Policies;

use App\Models\SisaKwh;
use App\Models\User;

/** Pembacaan meteran hanya boleh diubah/dihapus pemiliknya (didaftarkan otomatis oleh Laravel lewat nama kelas). */
class SisaKwhPolicy
{
    public function update(User $user, SisaKwh $sisaKwh): bool
    {
        return $sisaKwh->user_id === $user->id;
    }

    public function delete(User $user, SisaKwh $sisaKwh): bool
    {
        return $sisaKwh->user_id === $user->id;
    }
}
