<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /** Akun yang diberi data demo oleh seeder modul. */
    public const EMAIL_DEMO = 'penghuni1@wattkost.test';

    public function run(): void
    {
        $akun = [
            ['email' => 'admin@wattkost.test', 'name' => 'Admin WattKost', 'role' => User::ROLE_ADMIN],
            ['email' => self::EMAIL_DEMO, 'name' => 'Penghuni Satu', 'role' => User::ROLE_PENGHUNI],
            ['email' => 'penghuni2@wattkost.test', 'name' => 'Penghuni Dua', 'role' => User::ROLE_PENGHUNI],
        ];

        foreach ($akun as $data) {
            // forceFill karena role sengaja tidak fillable.
            User::firstOrNew(['email' => $data['email']])->forceFill([
                'name' => $data['name'],
                'role' => $data['role'],
                'password' => 'password', // hanya untuk lokal; di-hash oleh cast
                'email_verified_at' => now(),
            ])->save();
        }
    }

    public static function userDemo(): User
    {
        return User::where('email', self::EMAIL_DEMO)->firstOrFail();
    }
}
