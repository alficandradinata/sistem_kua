<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [SISTEM KUA] Detail nomor antrean per reservasi. Lihat CLAUDE.md & PROGRESS.md.
 */
class QueueDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'queue_number',
        'is_called',
        'called_at',
        'called_by',
        'attended_at',
        'attended_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reservation_id' => 'integer',
            'is_called' => 'boolean',
            'called_at' => 'datetime',
            'called_by' => 'integer',
            'attended_at' => 'datetime',
            'attended_by' => 'integer',
        ];
    }

    // --- Relationships ---

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Petugas loket yang memanggil nomor ini. FK eksplisit — tabel ini tidak
     * punya user_id sendiri, warganya lewat relasi reservation.
     */
    public function calledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    public function attendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attended_by');
    }

    // --- Scopes ---

    /**
     * Antrean yang belum dipanggil ke loket.
     */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('is_called', false);
    }

    public function scopeCalled(Builder $query): Builder
    {
        return $query->where('is_called', true);
    }

    /**
     * Antrean yang sudah benar-benar dilayani.
     */
    public function scopeAttended(Builder $query): Builder
    {
        return $query->whereNotNull('attended_at');
    }

    // --- Accessors ---

    /**
     * Lama menunggu dari dipanggil sampai dilayani, dalam menit.
     */
    public function getWaitingDurationAttribute(): ?int
    {
        if (! $this->called_at || ! $this->attended_at) {
            return null;
        }

        return (int) $this->called_at->diffInMinutes($this->attended_at);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->attended_at) {
            return 'Sudah dilayani';
        }

        return $this->is_called ? 'Sedang dipanggil' : 'Menunggu';
    }

    /**
     * Petugas loket penanggung jawab, untuk jejak audit. Hanya namanya — jamnya
     * sudah tampil terpisah di papan antrean, jangan diulang.
     *
     * Nama bisa hilang kalau akun petugasnya terhapus (FK nullOnDelete),
     * jadi barisnya tetap muncul supaya jelas keputusan itu ada pelakunya.
     */
    public function getHandledByLabelAttribute(): ?string
    {
        if ($this->attended_at) {
            return 'Dilayani '.($this->attendedBy?->name ?? 'petugas yang akunnya sudah dihapus');
        }

        if ($this->called_at) {
            return 'Dipanggil '.($this->calledBy?->name ?? 'petugas yang akunnya sudah dihapus');
        }

        return null;
    }

    // --- Helper methods ---

    /**
     * @param  int|null  $petugasId  petugas loket yang memanggil; default yang login.
     */
    public function markAsCalled(?int $petugasId = null): bool
    {
        return $this->update([
            'is_called' => true,
            'called_at' => now(),
            'called_by' => $petugasId ?? auth()->id(),
        ]);
    }

    /**
     * Antrean yang langsung diselesaikan tanpa dipanggil lebih dulu ikut
     * dicatat pemanggilnya, supaya tidak ada baris tanpa penanggung jawab.
     */
    public function markAsAttended(?int $petugasId = null): bool
    {
        $petugasId ??= auth()->id();

        return $this->update([
            'is_called' => true,
            'called_at' => $this->called_at ?? now(),
            'called_by' => $this->called_by ?? $petugasId,
            'attended_at' => now(),
            'attended_by' => $petugasId,
        ]);
    }

    public function isAttended(): bool
    {
        return $this->attended_at !== null;
    }

    /**
     * Nomor antrean berikutnya untuk sebuah tanggal, mis. "A-001".
     * Memakai urutan tertinggi yang sudah terbit (bukan jumlah baris) supaya
     * nomor tidak terpakai ulang bila ada antrean yang dihapus.
     */
    public static function generateNumber(string $date, string $prefix = 'A'): string
    {
        $last = static::query()
            ->whereHas('reservation', fn (Builder $q) => $q->whereDate('reservation_date', $date))
            ->where('queue_number', 'like', $prefix.'-%')
            ->orderByDesc('queue_number')
            ->value('queue_number');

        $next = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Antrean pada tanggal tertentu (lewat relasi reservasi).
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereHas('reservation', fn (Builder $q) => $q->whereDate('reservation_date', $date));
    }
}
