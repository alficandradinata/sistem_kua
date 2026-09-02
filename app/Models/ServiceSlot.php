<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * [SISTEM KUA] Slot & kuota antrean per layanan. Lihat CLAUDE.md & PROGRESS.md.
 */
class ServiceSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'quota_per_day',
        'slot_start_time',
        'slot_duration',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'quota_per_day' => 'integer',
            'slot_duration' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // --- Relationships ---

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForService(Builder $query, int $serviceId): Builder
    {
        return $query->where('service_id', $serviceId);
    }

    // --- Accessors ---

    /**
     * Jam selesai slot = jam mulai + durasi slot.
     */
    public function getSlotEndTimeAttribute(): string
    {
        return Carbon::parse($this->slot_start_time)
            ->addMinutes($this->slot_duration)
            ->format('H:i:s');
    }

    /**
     * Rentang jam terbaca, mis. "08:30 - 09:00".
     */
    public function getTimeRangeAttribute(): string
    {
        return substr($this->slot_start_time, 0, 5).' - '.substr($this->slot_end_time, 0, 5);
    }

    // --- Helper methods ---

    /**
     * Jumlah reservasi yang sudah memakai slot ini pada tanggal tertentu.
     * Reservasi yang dibatalkan warga maupun ditolak petugas tidak dihitung,
     * agar kuotanya kembali tersedia.
     */
    public function bookedCount(string $date): int
    {
        return Reservation::query()
            ->where('service_id', $this->service_id)
            ->whereDate('reservation_date', $date)
            ->where('reservation_time', $this->slot_start_time)
            ->active()
            ->count();
    }

    /**
     * Sisa kuota slot ini pada tanggal tertentu.
     */
    public function remainingQuota(string $date): int
    {
        return max(0, $this->quota_per_day - $this->bookedCount($date));
    }

    /**
     * Slot masih bisa dipesan pada tanggal tertentu?
     */
    public function isAvailable(string $date): bool
    {
        return $this->is_active && $this->remainingQuota($date) > 0;
    }
}
