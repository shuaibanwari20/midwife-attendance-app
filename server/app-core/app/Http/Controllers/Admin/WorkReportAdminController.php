<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Midwife;
use App\Models\MonthlyWorkReport;
use App\Support\AttendanceService;
use App\Support\DariDate;
use App\Support\XlsxExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WorkReportAdminController extends Controller
{
    private function monthOf(Request $request): string
    {
        $month = (string) $request->query('month', '');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)
            ? $month
            : AttendanceService::now()->format('Y-m');
    }

    /** Monthly work reports: status board (photo-based reports). */
    public function index(Request $request): View
    {
        $month = $this->monthOf($request);
        $monthDate = Carbon::createFromFormat('Y-m-d', $month . '-01', 'Asia/Kabul');

        $midwives = Midwife::with('cluster')->orderBy('code')->get();
        $reports = MonthlyWorkReport::where('month', $month)
            ->get()
            ->keyBy('midwife_id');

        $counts = ['missing' => 0, 'draft' => 0, 'submitted' => 0, 'returned' => 0, 'approved' => 0];
        foreach ($midwives as $mw) {
            $status = $reports->get($mw->id)?->status ?? 'missing';
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return view('admin.work-reports', [
            'month' => $month,
            'monthDate' => $monthDate,
            'midwives' => $midwives,
            'reports' => $reports,
            'counts' => $counts,
        ]);
    }

    /** One report: the three report photos + review actions. */
    public function show(int $id): View
    {
        $report = MonthlyWorkReport::with('midwife.cluster')->findOrFail($id);
        $monthDate = Carbon::createFromFormat('Y-m-d', $report->month . '-01', 'Asia/Kabul');

        return view('admin.work-report-show', [
            'report' => $report,
            'monthDate' => $monthDate,
            'photoKinds' => MonthlyWorkReport::PHOTO_KINDS,
            'photoLabels' => MonthlyWorkReport::PHOTO_LABELS,
        ]);
    }

    /** Stream one report photo (kind = opv | opd | fp), stored on the local disk. */
    public function photo(int $id, string $kind)
    {
        if (!in_array($kind, MonthlyWorkReport::PHOTO_KINDS, true)) {
            abort(404);
        }
        $report = MonthlyWorkReport::findOrFail($id);
        $path = $report->photoPath($kind);
        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($path));
    }

    /**
     * Per-photo review (his order 2026-11-27): approve ONE report photo.
     * The report status follows automatically (all approved = approved).
     */
    public function approvePhoto(Request $request, int $id, string $kind)
    {
        if (!in_array($kind, MonthlyWorkReport::PHOTO_KINDS, true)) {
            abort(404);
        }
        $report = MonthlyWorkReport::findOrFail($id);
        if (!$report->photoPath($kind)) {
            return back()->withErrors(['این عکس هنوز فرستاده نشده است.']);
        }
        if (!in_array($report->status, ['submitted', 'returned', 'approved'], true)) {
            return back()->withErrors(['اول باید قابله راپور را ارسال کند.']);
        }
        $report->{'review_' . $kind} = 'approved';
        $report->{'note_' . $kind} = null;
        $report->reviewed_at = AttendanceService::now();
        $report->reviewed_by = $request->user()->id;
        $report->recomputeStatus();
        $report->save();

        return back()->with('ok', 'عکس ' . MonthlyWorkReport::PHOTO_LABELS[$kind] . ' تأیید شد.');
    }

    /** Per-photo review: return ONE report photo for correction, with a note. */
    public function returnPhoto(Request $request, int $id, string $kind)
    {
        if (!in_array($kind, MonthlyWorkReport::PHOTO_KINDS, true)) {
            abort(404);
        }
        $data = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ], [
            'note.required' => 'برای برگشت‌دادن عکس، نوشتن دلیل لازم است.',
        ]);
        $report = MonthlyWorkReport::findOrFail($id);
        if (!$report->photoPath($kind)) {
            return back()->withErrors(['این عکس هنوز فرستاده نشده است.']);
        }
        if (!in_array($report->status, ['submitted', 'returned', 'approved'], true)) {
            return back()->withErrors(['اول باید قابله راپور را ارسال کند.']);
        }
        $report->{'review_' . $kind} = 'returned';
        $report->{'note_' . $kind} = $data['note'];
        $report->reviewed_at = AttendanceService::now();
        $report->reviewed_by = $request->user()->id;
        $report->recomputeStatus();
        $report->save();

        return back()->with('ok', 'عکس ' . MonthlyWorkReport::PHOTO_LABELS[$kind] . ' برای اصلاح برگشت داده شد.');
    }

    /** Excel/CSV: one row per midwife with the report status of the month. */
    public function export(Request $request)
    {
        $month = $this->monthOf($request);
        $midwives = Midwife::with('cluster')->orderBy('code')->get();
        $reports = MonthlyWorkReport::where('month', $month)->get()->keyBy('midwife_id');

        $headers = [
            'کد قابله', 'نام قابله', 'کلستر', 'وضعیت راپور',
            'عکس OPV', 'عکس OPD', 'عکس تنظیم خانواده',
            'تاریخ ارسال', 'تاریخ بررسی',
        ];

        $body = [];
        foreach ($midwives as $mw) {
            $r = $reports->get($mw->id);
            $cell = function (string $kind) use ($r) {
                if (!$r || !$r->photoPath($kind)) {
                    return 'نرسیده';
                }

                return match ($r->photoReview($kind)) {
                    'approved' => 'تأیید ✓',
                    'returned' => 'برگشت ↩',
                    default => 'در انتظار بررسی',
                };
            };
            $body[] = [
                DariDate::en($mw->code ?? ''),
                $mw->full_name ?? '',
                $mw->cluster ? $mw->cluster->displayLabel() : '',
                $r ? (MonthlyWorkReport::STATUS_LABELS[$r->status] ?? $r->status) : 'نرسیده',
                $cell('opv'),
                $cell('opd'),
                $cell('fp'),
                $r?->submitted_at ? DariDate::en($r->submitted_at->format('Y-m-d H:i')) : '',
                $r?->reviewed_at ? DariDate::en($r->reviewed_at->format('Y-m-d H:i')) : '',
            ];
        }

        $filenameBase = 'work-reports-' . $month;
        if (($request->query('format', 'xlsx')) === 'xlsx') {
            $binary = XlsxExporter::build($headers, $body);

            return response($binary, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filenameBase . '.xlsx"',
            ]);
        }

        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', array_map(fn ($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\r\n";
        foreach ($body as $row) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\r\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filenameBase . '.csv"',
        ]);
    }

    /** Printable status report of the month: every midwife + counts + signatures. */
    public function print(Request $request): View
    {
        $month = $this->monthOf($request);
        $monthDate = Carbon::createFromFormat('Y-m-d', $month . '-01', 'Asia/Kabul');

        $midwives = Midwife::with('cluster')->orderBy('code')->get();
        $reports = MonthlyWorkReport::where('month', $month)->get()->keyBy('midwife_id');

        $counts = ['missing' => 0, 'draft' => 0, 'submitted' => 0, 'returned' => 0, 'approved' => 0];
        foreach ($midwives as $mw) {
            $status = $reports->get($mw->id)?->status ?? 'missing';
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return view('admin.work-reports-print', [
            'month' => $month,
            'monthDate' => $monthDate,
            'midwives' => $midwives,
            'reports' => $reports,
            'counts' => $counts,
            'generatedAt' => AttendanceService::now(),
        ]);
    }
}
