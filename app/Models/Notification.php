<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [SISTEM KUA] Notifikasi aplikasi (email/sms/in-app). Lihat CLAUDE.md & PROGRESS.md.
 */
class Notification extends Model
{
    use HasFactory;

    public const TYPE_EMAIL = 'email';

    public const TYPE_SMS = 'sms';

    public const TYPE_IN_APP = 'in-app';

    public const TYPES = [
        self::TYPE_EMAIL => 'Email',
        self::TYPE_SMS => 'SMS',
        self::TYPE_IN_APP => 'Aplikasi',
    ];

    protected $fillable = [
        'user_id',
        'message',
        'type',
        'is_read',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_read' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // --- Scopes ---

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    // --- Accessors ---

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Waktu relatif, mis. "2 jam yang lalu".
     */
    public function getTimeAgoAttribute(): string
    {
        return ($this->sent_at ?? $this->created_at)?->locale('id')->diffForHumans() ?? '-';
    }

    // --- Helper methods ---

    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    /**
     * Buat sekaligus kirim notifikasi ke seorang user.
     */
    public static function send(int $userId, string $message, string $type = self::TYPE_IN_APP): self
    {
        return static::create([
            'user_id' => $userId,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
            'sent_at' => now(),
        ]);
    }
}
