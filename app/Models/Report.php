<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * [SISTEM KUA] Rekap agregat reservasi (harian/mingguan/bulanan). Lihat CLAUDE.md & PROGRESS.md.
 */
class Report extends Model
{
    use HasFactory;

    public const TYPE_DAILY = 'daily';

    public const TYPE_WEEKLY = 'weekly';

    public const TYPE_MONTHLY = 'monthly';

    public const TYPES = [
        self::TYPE_DAILY => 'Harian',
        self::TYPE_WEEKLY => 'Mingguan',
        self::TYPE_MONTHLY => 'Bulanan',
    ];

    protected $fillable = [
        'report_date',
        'report_type',
        'total_reservations',
        'total_completed',
        'total_cancelled',
        'total_rejected',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'total_reservations' => 'integer',
            'total_completed' => 'integer',
            'total_cancelled' => 'integer',
            'total_rejected' => 'integer',
            'generated_by' => 'integer',
        ];
    }

    // --- Relationships ---

    /**
     * Petugas yang membuat laporan. Foreign key eksplisit karena kolomnya
     * bernama generated_by, bukan user_id.
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // --- Scopes ---

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('report_type', $type);
    }

    public function scopeBetween(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('report_date', [$start, $end]);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('report_date');
    }

    // --- Pembuatan laporan ---

    /**
     * Rentang tanggal (awal, akhir) sebuah periode laporan untuk tanggal acuan.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function periodRange(string $type, string $date): array
    {
        $ref = Carbon::parse($date)->startOfDay();

        return match ($type) {
            self::TYPE_WEEKLY => [$ref->copy()->startOfWeek(), $ref->copy()->endOfWeek()],
            self::TYPE_MONTHLY => [$ref->copy()->startOfMonth(), $ref->copy()->endOfMonth()],
            default => [$ref->copy(), $ref->copy()],
        };
    }

    /**
     * Hitung agregat reservasi pada satu rentang tanggal.
     *
     * @return array<string, int> total + jumlah per status
     */
    public static function statsBetween(string $start, string $end): array
    {
        $perStatus = Reservation::betweenDates($start, $end)
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $stats = ['total' => (int) $perStatus->sum()];

        foreach (array_keys(Reservation::STATUSES) as $status) {
            $stats[$status] = (int) $perStatus->get($status, 0);
        }

        return $stats;
    }

    /**
     * Buat (atau perbarui) laporan sebuah periode. Tanggal acuan dinormalkan ke
     * awal periode supaya satu periode hanya punya satu baris laporan.
     */
    public static function generateFor(string $type, string $date, int $userId): self
    {
        [$start, $end] = self::periodRange($type, $date);

        $stats = self::statsBetween($start->toDateString(), $end->toDateString());

        $values = [
            'total_reservations' => $stats['total'],
            'total_completed' => $stats[Reservation::STATUS_COMPLETED],
            'total_cancelled' => $stats[Reservation::STATUS_CANCELLED],
            'total_rejected' => $stats[Reservation::STATUS_REJECTED],
            'generated_by' => $userId,
        ];

        // Dicari dengan whereDate karena kolom di-cast `date` sehingga pada SQLite
        // tersimpan sebagai "Y-m-d H:i:s" — perbandingan persis tidak cocok.
        $report = self::ofType($type)->whereDate('report_date', $start->toDateString())->first();

        if ($report) {
            $report->update($values);

            return $report;
        }

        return self::create($values + [
            'report_type' => $type,
            'report_date' => $start->toDateString(),
        ]);
    }

    /**
     * Rincian per layanan pada periode laporan ini — dihitung ulang saat dibuka.
     *
     * @return Collection<int, object>
     */
    public function serviceBreakdown(): Collection
    {
        [$start, $end] = $this->range();

        return Reservation::betweenDates($start->toDateString(), $end->toDateString())
            ->join('services', 'services.id', '=', 'reservations.service_id')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total')
            ->selectRaw('services.name as service_name, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', [Reservation::STATUS_COMPLETED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled', [Reservation::STATUS_CANCELLED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected', [Reservation::STATUS_REJECTED])
            ->get();
    }

    /**
     * Tren jumlah reservasi per hari sepanjang periode, untuk grafik batang.
     * Hari tanpa reservasi tetap ikut (nilai 0) supaya sumbu waktunya utuh.
     *
     * @return Collection<int, object>
     */
    public function dailyTrend(): Collection
    {
        [$start, $end] = $this->range();

        $perDay = Reservation::betweenDates($start->toDateString(), $end->toDateString())
            ->selectRaw('reservation_date, status, COUNT(*) as jumlah')
            ->groupBy('reservation_date', 'status')
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->reservation_date)->toDateString());

        $trend = collect();

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $statuses = ($perDay[$day->toDateString()] ?? collect())->pluck('jumlah', 'status');

            $total = (int) $statuses->sum();
            $completed = (int) $statuses->get(Reservation::STATUS_COMPLETED, 0);
            $cancelled = (int) $statuses->get(Reservation::STATUS_CANCELLED, 0);
            $rejected = (int) $statuses->get(Reservation::STATUS_REJECTED, 0);

            $trend->push((object) [
                'date' => $day->copy(),
                'total' => $total,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'rejected' => $rejected,
                'pending' => $total - $completed - $cancelled - $rejected,
            ]);
        }

        return $trend;
    }

    /**
     * Query reservasi yang tercakup laporan ini (bukan relasi Eloquent).
     */
    public function reservationsQuery(): Builder
    {
        [$start, $end] = $this->range();

        return Reservation::betweenDates($start->toDateString(), $end->toDateString())
            // approvedBy/rejectedBy & petugas loket ikut dimuat karena dipakai
            // kolom jejak audit di ekspor CSV.
            ->with([
                'user', 'service', 'approvedBy', 'rejectedBy',
                'queueDetail.calledBy', 'queueDetail.attendedBy',
            ])
            ->orderBy('reservation_date')
            ->orderBy('reservation_time');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function range(): array
    {
        return self::periodRange($this->report_type, $this->report_date->toDateString());
    }

    // --- Accessors ---

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->report_type] ?? $this->report_type;
    }

    public function getFormattedDateAttribute(): string
    {
        return Carbon::parse($this->report_date)
            ->locale('id')
            ->translatedFormat('j F Y');
    }

    /**
     * Label periode terbaca: harian satu tanggal, mingguan rentang, bulanan nama bulan.
     */
    public function getPeriodLabelAttribute(): string
    {
        [$start, $end] = $this->range();

        return match ($this->report_type) {
            self::TYPE_MONTHLY => $start->locale('id')->translatedFormat('F Y'),
            self::TYPE_WEEKLY => $start->locale('id')->translatedFormat('j M').' – '
                .$end->locale('id')->translatedFormat('j M Y'),
            default => $start->locale('id')->translatedFormat('l, j F Y'),
        };
    }

    /**
     * Persentase reservasi yang selesai dilayani.
     */
    public function getCompletionRateAttribute(): float
    {
        return $this->rateOf($this->total_completed);
    }

    /**
     * Persentase reservasi yang dibatalkan warga sendiri.
     */
    public function getCancellationRateAttribute(): float
    {
        return $this->rateOf($this->total_cancelled);
    }

    /**
     * Persentase berkas yang ditolak petugas — angka mutu layanan, dipisah
     * dari pembatalan warga yang bukan urusan KUA.
     */
    public function getRejectionRateAttribute(): float
    {
        return $this->rateOf($this->total_rejected);
    }

    /**
     * Reservasi yang belum selesai, belum dibatalkan, dan belum ditolak.
     */
    public function getTotalPendingAttribute(): int
    {
        return max(0, $this->total_reservations
            - $this->total_completed
            - $this->total_cancelled
            - $this->total_rejected);
    }

    /**
     * Persentase satu angka terhadap total reservasi periode ini.
     */
    private function rateOf(int $jumlah): float
    {
        if ($this->total_reservations <= 0) {
            return 0.0;
        }

        return round($jumlah / $this->total_reservations * 100, 2);
    }
}
