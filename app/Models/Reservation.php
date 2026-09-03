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

    /** Dibatalkan sendiri oleh warga. */
    public const STATUS_CANCELLED = 'cancelled';

    /** Ditolak petugas KUA — beda urusan administratif dengan STATUS_CANCELLED. */
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
        self::STATUS_REJECTED => 'Ditolak',
    ];

    /**
     * Status yang membuat reservasi berhenti memakai kuota slot. Dipakai lewat
     * scope `active()` — jangan bandingkan langsung ke STATUS_CANCELLED saja,
     * karena reservasi yang ditolak juga harus mengembalikan kuotanya.
     */
    public const STATUSES_INACTIVE = [
        self::STATUS_CANCELLED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'user_id',
        'service_id',
        'reservation_date',
        'reservation_time',
        'status',
        'notes',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'service_id' => 'integer',
            'reservation_date' => 'date',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'rejected_by' => 'integer',
            'rejected_at' => 'datetime',
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

    /**
     * Petugas yang menyetujui. FK eksplisit karena kolomnya bukan user_id —
     * user_id itu milik warga pemohon.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
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

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Reservasi yang masih memesan tempat — belum dibatalkan warga maupun
     * ditolak petugas. Inilah yang menghabiskan kuota slot.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::STATUSES_INACTIVE);
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
     * Reservasi yang belum lewat dan masih aktif (bukan dibatalkan / ditolak).
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('reservation_date', '>=', today())->active();
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
     * Jejak audit terbaca: siapa memutuskan dan kapan. Null kalau reservasi
     * belum pernah diverifikasi petugas.
     *
     * Nama bisa kosong kalau akun petugasnya sudah dihapus (FK nullOnDelete),
     * jadi tanggalnya tetap ditampilkan sebagai bukti keputusan itu ada.
     */
    public function getVerificationLogAttribute(): ?string
    {
        [$aksi, $petugas, $waktu] = match (true) {
            $this->isRejected() && $this->rejected_at !== null => ['Ditolak', $this->rejectedBy?->name, $this->rejected_at],
            $this->approved_at !== null => ['Disetujui', $this->approvedBy?->name, $this->approved_at],
            default => [null, null, null],
        };

        if ($waktu === null) {
            return null;
        }

        return $aksi.' oleh '.($petugas ?? 'petugas yang akunnya sudah dihapus')
            .' · '.$waktu->locale('id')->translatedFormat('j M Y, H:i').' WIB';
    }

    /**
     * Kelas badge Tailwind sesuai status, untuk dipakai di view.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            self::STATUS_APPROVED => 'bg-sky-50 text-sky-800 ring-sky-600/20',
            self::STATUS_COMPLETED => 'bg-kua-50 text-kua-800 ring-kua-600/20',
            // Dibatalkan warga = netral; ditolak petugas = merah, karena
            // yang kedua butuh tindak lanjut warga (perbaiki berkas, ajukan ulang).
            self::STATUS_CANCELLED => 'bg-stone-100 text-stone-600 ring-stone-500/20',
            self::STATUS_REJECTED => 'bg-rose-50 text-rose-800 ring-rose-600/20',
            default => 'bg-stone-100 text-stone-700 ring-stone-500/20',
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

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Reservasi hanya bisa dibatalkan kalau belum selesai dan tanggalnya belum lewat.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true)
            && Carbon::parse($this->reservation_date)->startOfDay()->gte(today());
    }

    /**
     * @param  int|null  $petugasId  penanggung jawab keputusan; default petugas yang login.
     */
    public function approve(?int $petugasId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $petugasId ?? auth()->id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Setujui reservasi sekaligus terbitkan nomor antrean & kirim notifikasi ke warga.
     * Dibungkus transaksi supaya status dan nomor antrean tidak pernah terpisah.
     */
    public function approveAndIssueQueue(?int $petugasId = null): QueueDetail
    {
        return DB::transaction(function () use ($petugasId): QueueDetail {
            $this->approve($petugasId);

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
     *
     * Statusnya REJECTED, bukan CANCELLED: laporan KUA harus bisa memisahkan
     * berkas yang ditolak dari reservasi yang dibatalkan warga sendiri.
     * Alasannya masuk kolom sendiri, bukan menumpang `notes` milik warga.
     */
    public function reject(?string $reason = null, ?int $petugasId = null): bool
    {
        return DB::transaction(function () use ($reason, $petugasId): bool {
            $ok = $this->update([
                'status' => self::STATUS_REJECTED,
                'rejection_reason' => $reason ? trim($reason) : null,
                'rejected_by' => $petugasId ?? auth()->id(),
                'rejected_at' => now(),
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
