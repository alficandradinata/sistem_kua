<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * [SISTEM KUA] Daftar hari libur (blokir tanggal reservasi). Lihat CLAUDE.md & PROGRESS.md.
 */
class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'holiday_date',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('holiday_date', '>=', today())
            ->orderBy('holiday_date');
    }

    public function scopeInYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('holiday_date', $year);
    }

    // --- Accessors ---

    /**
     * Tanggal terbaca, mis. "Kamis, 25 Desember 2026".
     */
    public function getFormattedDateAttribute(): string
    {
        return Carbon::parse($this->holiday_date)
            ->locale('id')
            ->translatedFormat('l, j F Y');
    }

    // --- Helper methods ---

    /**
     * Apakah tanggal tersebut hari libur? Dipakai untuk memblokir pilihan tanggal reservasi.
     */
    public static function isHoliday(string $date): bool
    {
        return static::query()
            ->active()
            ->whereDate('holiday_date', $date)
            ->exists();
    }

    /**
     * Daftar tanggal libur (format Y-m-d) untuk dikirim ke datepicker di frontend.
     */
    public static function activeDates(): array
    {
        return static::query()
            ->active()
            ->pluck('holiday_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();
    }
}
