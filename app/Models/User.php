<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // ===== Role =====
    public const ROLE_USER = 'user';
    public const ROLE_TU_UNIT = 'tu_unit';
    public const ROLE_TU_SEKRETARIAT = 'tu_sekretariat';
    public const ROLE_ESELON2 = 'eselon2';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /**
     * Role lama sebelum TU dipecah jadi TU Unit dan TU Sekretariat.
     * Tidak bisa dipilih lagi di form. Hapus konstanta ini kalau sudah tidak dipakai di mana pun.
     */
    public const ROLE_TU = 'tu';

    /**
     * Label tampilan tiap role.
     */
    public const ROLE_LABELS = [
        self::ROLE_USER => 'Staf / User',
        self::ROLE_TU_UNIT => 'TU Unit',
        self::ROLE_TU_SEKRETARIAT => 'TU Sekretariat',
        self::ROLE_ESELON2 => 'Eselon II',
        self::ROLE_ADMIN => 'Administrator',
        self::ROLE_SUPER_ADMIN => 'Super Admin',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'unit_id',
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

    // ===== Relasi =====

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    // ===== Helper role =====

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Admin dan Super Admin diperlakukan setara.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }

    /**
     * TU Unit atau TU Sekretariat.
     */
    public function isTu(): bool
    {
        return in_array($this->role, [self::ROLE_TU_UNIT, self::ROLE_TU_SEKRETARIAT], true);
    }

    public function isTuSekretariat(): bool
    {
        return $this->role === self::ROLE_TU_SEKRETARIAT;
    }

    public function isEselon2(): bool
    {
        return $this->role === self::ROLE_ESELON2;
    }

    /**
     * Boleh approval di Monitoring Memo.
     */
    public function canApproveMemo(): bool
    {
        return in_array($this->role, [
            self::ROLE_TU_SEKRETARIAT,
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
        ], true);
    }

    /**
     * Nama role untuk ditampilkan, contoh: "TU Sekretariat".
     */
    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? ucfirst((string) $this->role);
    }
}