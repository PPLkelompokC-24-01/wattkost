<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_PENGHUNI = 'penghuni';

    /**
     * Kolom `role` sengaja TIDAK fillable supaya tidak bisa diisi dari form
     * (register selalu penghuni). Set role lewat forceFill() / factory state.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** @return HasOne<ProfilKost, $this> */
    public function profilKost(): HasOne
    {
        return $this->hasOne(ProfilKost::class);
    }

    /** @return HasMany<AlatListrik, $this> */
    public function alatListrik(): HasMany
    {
        return $this->hasMany(AlatListrik::class);
    }

    /** @return HasMany<PemakaianHarian, $this> */
    public function pemakaianHarian(): HasMany
    {
        return $this->hasMany(PemakaianHarian::class);
    }

    /** @return HasMany<PembelianToken, $this> */
    public function pembelianToken(): HasMany
    {
        return $this->hasMany(PembelianToken::class);
    }

    /** @return HasMany<SisaKwh, $this> */
    public function sisaKwh(): HasMany
    {
        return $this->hasMany(SisaKwh::class);
    }

    /** @return HasMany<TargetHemat, $this> */
    public function targetHemat(): HasMany
    {
        return $this->hasMany(TargetHemat::class);
    }

    /** @return HasOne<PengaturanPengguna, $this> */
    public function pengaturanPengguna(): HasOne
    {
        return $this->hasOne(PengaturanPengguna::class);
    }
}
