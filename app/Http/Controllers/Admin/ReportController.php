<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRequest;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [SISTEM KUA] Laporan rekap reservasi (harian/mingguan/bulanan). Lihat PROGRESS.md.
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->string('type')->toString();
        $type = array_key_exists($type, Report::TYPES) ? $type : Report::TYPE_DAILY;

        $date = $request->date('date')?->toDateString() ?: today()->toDateString();

        [$start, $end] = Report::periodRange($type, $date);

        return view('admin.reports.index', [
            'type' => $type,
            'date' => $date,
            'periodStart' => $start,
            'periodEnd' => $end,
            // Pratinjau angka periode terpilih supaya admin tahu isinya sebelum menyimpan.
            'preview' => Report::statsBetween($start->toDateString(), $end->toDateString()),
            'reports' => Report::ofType($type)
                ->with('generatedBy')
                ->latestFirst()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function store(ReportRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $report = Report::generateFor($data['report_type'], $data['report_date'], $request->user()->id);

        return redirect()
            ->route('admin.reports.show', $report)
            ->with('status', "Laporan {$report->type_label} periode {$report->period_label} berhasil dibuat.");
    }

    public function show(Report $report): View
    {
        [$start, $end] = $report->range();

        return view('admin.reports.show', [
            'report' => $report->load('generatedBy'),
            'periodStart' => $start,
            'periodEnd' => $end,
            'breakdown' => $report->serviceBreakdown(),
            'reservations' => $report->reservationsQuery()->paginate(15),
        ]);
    }

    /**
     * Unduh daftar reservasi periode laporan sebagai CSV.
     */
    public function export(Report $report): StreamedResponse
    {
        $filename = 'laporan-'.$report->report_type.'-'.$report->report_date->toDateString().'.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 supaya Excel membaca huruf beraksen & tanda baca dengan benar.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Tanggal', 'Jam', 'Nama Warga', 'Layanan', 'Status', 'No. Antrean']);

            $report->reservationsQuery()->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $reservation) {
                    fputcsv($handle, [
                        $reservation->reservation_date->toDateString(),
                        substr((string) $reservation->reservation_time, 0, 5),
                        $reservation->user?->name,
                        $reservation->service?->name,
                        $reservation->status_label,
                        $reservation->queueDetail?->queue_number ?? '-',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(Report $report): RedirectResponse
    {
        $report->delete();

        return redirect()
            ->route('admin.reports.index', ['type' => $report->report_type])
            ->with('status', 'Laporan dihapus.');
    }
}
