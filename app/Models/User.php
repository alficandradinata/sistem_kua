<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\PhoneNumber;
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

    // --- Nomor HP / WhatsApp ---

    /**
     * Nomor disimpan selalu dalam format 62… supaya cocok dengan nomor pengirim
     * dari WhatsApp Cloud API. Nomor yang jelas tidak valid disimpan null.
     */
    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = PhoneNumber::normalize($value);
    }

    public function scopeByPhone(Builder $query, ?string $number): Builder
    {
        return $query->where('phone', PhoneNumber::normalize($number));
    }

    /**
     * Cari pemilik sebuah nomor WhatsApp. Null bila nomornya belum terdaftar.
     */
    public static function findByPhone(?string $number): ?self
    {
        $normal = PhoneNumber::normalize($number);

        return $normal ? static::where('phone', $normal)->first() : null;
    }

    public function getFormattedPhoneAttribute(): string
    {
        return PhoneNumber::format($this->phone);
    }

    public function hasWhatsApp(): bool
    {
        return $this->phone !== null;
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
     * Akun ini pernah memutuskan sesuatu sebagai petugas — menyetujui/menolak
     * reservasi, atau memanggil/melayani antrean.
     *
     * Dipakai untuk menahan penghapusan akun: jejak audit harus tetap bisa
     * menunjuk nama penanggung jawabnya.
     */
    public function hasVerificationHistory(): bool
    {
        return Reservation::where('approved_by', $this->id)
            ->orWhere('rejected_by', $this->id)
            ->exists()
            || QueueDetail::where('called_by', $this->id)
                ->orWhere('attended_by', $this->id)
                ->exists();
    }

    /**
     * Jumlah notifikasi aplikasi yang belum dibaca, untuk badge di navbar.
     */
    public function unreadNotificationCount(): int
    {
        return $this->appNotifications()->unread()->count();
    }
}
