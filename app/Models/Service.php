<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * [SISTEM KUA] Master data layanan KUA. Lihat CLAUDE.md & PROGRESS.md.
 */
class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'duration',
        'fee',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // --- Relationships ---

    public function slots(): HasMany
    {
        return $this->hasMany(ServiceSlot::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // --- Accessors ---

    /**
     * Biaya dalam format rupiah, mis. "Rp 600.000" atau "Gratis".
     */
    public function getFormattedFeeAttribute(): string
    {
        if ((float) $this->fee <= 0) {
            return 'Gratis';
        }

        return 'Rp '.number_format((float) $this->fee, 0, ',', '.');
    }

    /**
     * Durasi layanan dalam bentuk terbaca, mis. "1 jam 30 menit".
     */
    public function getDurationLabelAttribute(): string
    {
        $minutes = (int) $this->duration;

        if ($minutes < 60) {
            return $minutes.' menit';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest === 0 ? $hours.' jam' : $hours.' jam '.$rest.' menit';
    }
}
