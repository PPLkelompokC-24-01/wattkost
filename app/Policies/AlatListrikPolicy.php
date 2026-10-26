<?php

namespace App\Policies;

use App\Models\AlatListrik;
use App\Models\User;

/** Alat hanya boleh diubah/dihapus pemiliknya (didaftarkan otomatis oleh Laravel lewat nama kelas). */
class AlatListrikPolicy
{
    public function update(User $user, AlatListrik $alat): bool
    {
        return $alat->user_id === $user->id;
    }

    public function delete(User $user, AlatListrik $alat): bool
    {
        return $alat->user_id === $user->id;
    }
}
