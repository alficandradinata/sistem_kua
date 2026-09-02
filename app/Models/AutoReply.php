<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * [SISTEM KUA] Kata kunci → balasan otomatis WhatsApp, dikelola admin.
 * Lihat CLAUDE.md & PROGRESS.md.
 */
class AutoReply extends Model
{
    use HasFactory;

    public const MATCH_EXACT = 'exact';

    public const MATCH_CONTAINS = 'contains';

    public const MATCH_TYPES = [
        self::MATCH_EXACT => 'Sama persis',
        self::MATCH_CONTAINS => 'Mengandung kata',
    ];

    protected $fillable = [
        'keyword',
        'match_type',
        'reply_body',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // --- Accessors ---

    public function getMatchLabelAttribute(): string
    {
        return self::MATCH_TYPES[$this->match_type] ?? $this->match_type;
    }

    // --- Helper methods ---

    /**
     * Cari balasan yang cocok untuk sebuah pesan warga.
     * Yang diperiksa lebih dulu adalah sort_order terkecil.
     */
    public static function match(string $message): ?self
    {
        $bersih = mb_strtolower(trim($message));

        if ($bersih === '') {
            return null;
        }

        foreach (static::active()->ordered()->get() as $reply) {
            $kunci = mb_strtolower(trim($reply->keyword));

            $cocok = $reply->match_type === self::MATCH_EXACT
                ? $bersih === $kunci
                : str_contains($bersih, $kunci);

            if ($cocok) {
                return $reply;
            }
        }

        return null;
    }
}
