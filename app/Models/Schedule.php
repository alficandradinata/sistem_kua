<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * [SISTEM KUA] Jam operasional KUA per hari. Lihat CLAUDE.md & PROGRESS.md.
 */
class Schedule extends Model
{
    use HasFactory;

    /**
     * Nama hari sesuai kolom day_of_week (0 = Minggu).
     */
    public const DAYS = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    protected $fillable = [
        'day_of_week',
        'open_time',
        'close_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay(Builder $query, int $dayOfWeek): Builder
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    // --- Accessors ---

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'Tidak diketahui';
    }

    /**
     * Jam operasional terbaca, mis. "08:00 - 15:00" atau "Tutup".
     */
    public function getOperationalHoursAttribute(): string
    {
        if (! $this->is_active || ! $this->open_time || ! $this->close_time) {
            return 'Tutup';
        }

        return substr($this->open_time, 0, 5).' - '.substr($this->close_time, 0, 5);
    }

    // --- Helper methods ---

    /**
     * Apakah KUA buka pada tanggal tertentu (berdasarkan hari dalam seminggu).
     */
    public static function isOpenOn(string $date): bool
    {
        return static::query()
            ->active()
            ->forDay(Carbon::parse($date)->dayOfWeek)
            ->exists();
    }

    /**
     * Ambil jadwal untuk tanggal tertentu, null kalau tidak ada.
     */
    public static function forDate(string $date): ?self
    {
        return static::query()
            ->forDay(Carbon::parse($date)->dayOfWeek)
            ->first();
    }

    /**
     * Cek apakah sebuah jam masih dalam rentang jam operasional.
     */
    public function isWithinHours(string $time): bool
    {
        if (! $this->open_time || ! $this->close_time) {
            return false;
        }

        return $time >= $this->open_time && $time <= $this->close_time;
    }
}
