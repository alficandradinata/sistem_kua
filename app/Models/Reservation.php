<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * [SISTEM KUA] Model inti: reservasi antrean warga. Lihat CLAUDE.md & PROGRESS.md.
 */
class Reservation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    protected $fillable = [
        'user_id',
        'service_id',
        'reservation_date',
        'reservation_time',
        'status',
        'notes',
        'reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'service_id' => 'integer',
            'reservation_date' => 'date',
            'reminded_at' => 'datetime',
        ];
    }

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function queueDetail(): HasOne
    {
        return $this->hasOne(QueueDetail::class);
    }

    // --- Scopes ---

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('reservation_date', $date);
    }

    /**
     * Reservasi dalam rentang tanggal (inklusif). whereDate dipakai karena kolom
     * di-cast `date` sehingga pada SQLite tersimpan dengan jam.
     */
    public function scopeBetweenDates(Builder $query, string $start, string $end): Builder
    {
        return $query->whereDate('reservation_date', '>=', $start)
            ->whereDate('reservation_date', '<=', $end);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('reservation_date', today());
    }

    /**
     * Reservasi yang belum lewat dan belum dibatalkan.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('reservation_date', '>=', today())
            ->where('status', '!=', self::STATUS_CANCELLED);
    }

    // --- Accessors ---

    /**
     * Tanggal terbaca dalam bahasa Indonesia, mis. "12 Maret 2026".
     */
    public function getFormattedDateAttribute(): string
    {
        return Carbon::parse($this->reservation_date)
            ->locale('id')
            ->translatedFormat('j F Y');
    }

    /**
     * Tanggal lengkap dengan nama hari, mis. "Kamis, 12 Maret 2026".
     */
    public function getFullDateAttribute(): string
    {
        return Carbon::parse($this->reservation_date)
            ->locale('id')
            ->translatedFormat('l, j F Y');
    }

    public function getFormattedTimeAttribute(): string
    {
        return substr((string) $this->reservation_time, 0, 5).' WIB';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Kelas badge Tailwind sesuai status, untuk dipakai di view.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_APPROVED => 'bg-blue-100 text-blue-800',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    // --- Helper methods ---

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Reservasi hanya bisa dibatalkan kalau belum selesai dan tanggalnya belum lewat.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true)
            && Carbon::parse($this->reservation_date)->startOfDay()->gte(today());
    }

    public function approve(): bool
    {
        return $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Setujui reservasi sekaligus terbitkan nomor antrean & kirim notifikasi ke warga.
     * Dibungkus transaksi supaya status dan nomor antrean tidak pernah terpisah.
     */
    public function approveAndIssueQueue(): QueueDetail
    {
        return DB::transaction(function (): QueueDetail {
            $this->approve();

            $queue = $this->queueDetail()->firstOrCreate([], [
                'queue_number' => QueueDetail::generateNumber($this->reservation_date->toDateString()),
            ]);

            Notification::send(
                $this->user_id,
                "Reservasi {$this->service->name} pada {$this->formatted_date} disetujui. Nomor antrean Anda: {$queue->queue_number}.",
            );

            return $queue;
        });
    }

    /**
     * Tolak reservasi (oleh petugas) berikut alasannya.
     */
    public function reject(?string $reason = null): bool
    {
        return DB::transaction(function () use ($reason): bool {
            $ok = $this->update([
                'status' => self::STATUS_CANCELLED,
                'notes' => $reason ? trim($this->notes."\n[Ditolak petugas] ".$reason) : $this->notes,
            ]);

            Notification::send(
                $this->user_id,
                "Reservasi {$this->service->name} pada {$this->formatted_date} ditolak."
                    .($reason ? " Alasan: {$reason}" : ''),
            );

            return $ok;
        });
    }

    public function complete(): bool
    {
        return $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Kirim pengingat H-1 ke warga dan tandai supaya tidak terkirim dua kali.
     */
    public function sendReminder(): ?Notification
    {
        if ($this->reminded_at !== null) {
            return null;
        }

        return DB::transaction(function (): Notification {
            $pesan = "Pengingat: reservasi {$this->service->name} Anda dijadwalkan besok, "
                ."{$this->full_date} pukul {$this->formatted_time}."
                .($this->queueDetail
                    ? " Nomor antrean Anda: {$this->queueDetail->queue_number}."
                    : '');

            $notification = Notification::send($this->user_id, $pesan);

            $this->update(['reminded_at' => now()]);

            return $notification;
        });
    }

    public function cancel(): bool
    {
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }
}
