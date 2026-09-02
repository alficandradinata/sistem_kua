<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * File bawaan Laravel — DIMODIFIKASI untuk [SISTEM KUA]:
 * ditambah kolom `role` + helper peran (isWarga/isPetugas/isAdmin/hasRole/homeRoute),
 * dan relasi reservations(), appNotifications(), reports().
 * Lihat CLAUDE.md & PROGRESS.md.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_WARGA = 'warga';

    public const ROLE_PETUGAS = 'petugas';

    public const ROLE_ADMIN = 'admin';

    public const ROLES = [
        self::ROLE_WARGA => 'Warga',
        self::ROLE_PETUGAS => 'Petugas KUA',
        self::ROLE_ADMIN => 'Administrator',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
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

    // --- Role ---

    public function isWarga(): bool
    {
        return $this->role === self::ROLE_WARGA;
    }

    public function isPetugas(): bool
    {
        return $this->role === self::ROLE_PETUGAS;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Cek user punya salah satu dari peran yang diberikan.
     *
     * @param  string|array<string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Nama route tujuan setelah login, sesuai peran.
     */
    public function homeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_PETUGAS => 'petugas.dashboard',
            default => 'dashboard',
        };
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? (string) $this->role;
    }

    // --- Relationships ---

    /**
     * Reservasi antrean yang diajukan user ini.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Notifikasi kustom aplikasi (tabel notifications milik sistem KUA).
     *
     * Sengaja TIDAK dinamai notifications() supaya tidak menimpa relasi bawaan
     * trait Notifiable, yang dipakai Laravel untuk email reset password.
     */
    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Laporan yang dibuat user ini (kolom generated_by, bukan user_id).
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'generated_by');
    }

    // --- Helper methods ---

    /**
     * Jumlah notifikasi aplikasi yang belum dibaca, untuk badge di navbar.
     */
    public function unreadNotificationCount(): int
    {
        return $this->appNotifications()->unread()->count();
    }
}
