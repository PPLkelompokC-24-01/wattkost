<?php

namespace App\Services;

use App\Models\User;

/**
 * Aturan pengaman Kelola Pengguna (PBI 25, UC-25 A2). Mengembalikan pesan penolakan, atau null kalau boleh.
 * Pelaku selalu admin, jadi saat hanya ada satu admin, aturan "diri sendiri" yang lebih dulu kena.
 */
final class PenggunaService
{
    public function alasanTolakHapus(User $pelaku, User $target): ?string
    {
        if ($pelaku->is($target)) {
            return 'Kamu tidak bisa menghapus akunmu sendiri.';
        }

        if ($target->isAdmin() && $this->jumlahAdmin() <= 1) {
            return 'Admin terakhir tidak bisa dihapus.';
        }

        return null;
    }

    public function alasanTolakUbahPeran(User $pelaku, User $target, string $peranBaru): ?string
    {
        if (! $target->isAdmin() || $peranBaru === User::ROLE_ADMIN) {
            return null; // bukan penurunan admin → penghuni
        }

        if ($pelaku->is($target)) {
            return 'Kamu tidak bisa menurunkan peran akunmu sendiri.';
        }

        if ($this->jumlahAdmin() <= 1) {
            return 'Admin terakhir tidak bisa diturunkan menjadi penghuni.';
        }

        return null;
    }

    private function jumlahAdmin(): int
    {
        return User::query()->where('role', User::ROLE_ADMIN)->count();
    }
}
