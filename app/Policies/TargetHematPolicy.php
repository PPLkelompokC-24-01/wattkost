<?php

namespace App\Policies;

use App\Models\TargetHemat;
use App\Models\User;

/** Target hanya boleh diubah/dihapus pemiliknya (didaftarkan otomatis oleh Laravel lewat nama kelas). */
class TargetHematPolicy
{
    public function update(User $user, TargetHemat $target): bool
    {
        return $target->user_id === $user->id;
    }

    public function delete(User $user, TargetHemat $target): bool
    {
        return $target->user_id === $user->id;
    }
}
