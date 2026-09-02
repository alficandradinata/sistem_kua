<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * [SISTEM KUA] Riwayat pesan WhatsApp masuk & keluar. Lihat CLAUDE.md & PROGRESS.md.
 */
class WhatsAppMessage extends Model
{
    use HasFactory;

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RECEIVED = 'received';

    // Tanpa ini Laravel menebak "whats_app_messages" dari nama kelasnya.
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'direction',
        'wa_number',
        'user_id',
        'body',
        'wamid',
        'status',
        'error',
        'is_auto_reply',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_auto_reply' => 'boolean',
            'payload' => 'array',
        ];
    }

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // --- Scopes ---

    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_IN);
    }

    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('direction', self::DIRECTION_OUT);
    }

    public function scopeForNumber(Builder $query, string $number): Builder
    {
        return $query->where('wa_number', PhoneNumber::normalize($number));
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    // --- Accessors ---

    public function getIsInboundAttribute(): bool
    {
        return $this->direction === self::DIRECTION_IN;
    }

    public function getFormattedNumberAttribute(): string
    {
        return PhoneNumber::format($this->wa_number);
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at?->locale('id')->diffForHumans() ?? '-';
    }

    // --- Helper methods ---

    /**
     * Kapan warga terakhir mengirim pesan. Dipakai untuk menghitung jendela 24 jam
     * Cloud API: di luar itu hanya template yang boleh dikirim.
     */
    public static function lastInboundAt(string $number): ?Carbon
    {
        $waktu = static::inbound()->forNumber($number)->max('created_at');

        return $waktu ? Carbon::parse($waktu) : null;
    }

    /**
     * Masih boleh mengirim teks bebas ke nomor ini?
     */
    public static function withinSessionWindow(string $number): bool
    {
        $last = static::lastInboundAt($number);

        if ($last === null) {
            return false;
        }

        return $last->diffInHours(now()) < config('whatsapp.session_window_hours', 24);
    }

    /**
     * Catat pesan tanpa mengirim apa pun (dipakai gateway & webhook).
     */
    public static function record(array $attributes): self
    {
        $attributes['wa_number'] = PhoneNumber::normalize($attributes['wa_number']);

        return static::create($attributes);
    }
}
