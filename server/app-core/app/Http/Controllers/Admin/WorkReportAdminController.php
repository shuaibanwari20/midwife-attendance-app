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

    /** Monthly work reports: status board + approved aggregates. */
    public function index(Request $request): View
    {
        $month = $this->monthOf($request);
        $monthDate = Carbon::createFromFormat('Y-m-d', $month . '-01', 'Asia/Kabul');

        $midwives = Midwife::with('cluster')->orderBy('code')->get();
        $reports = MonthlyWorkReport::where('month', $month)
            ->get()
            ->keyBy('midwife_id');

        $counts = ['missing' => 0, 'draft' => 0, 'submitted' => 0, 'returned' => 0, 'approved' => 0];
        $agg = ['vacc_doses' => 0, 'opd_total' => 0, 'fp_clients' => 0, 'deliveries' => 0];
        foreach ($midwives as $mw) {
            $report = $reports->get($mw->id);
            $status = $report?->status ?? 'missing';
            $counts[$status] = ($counts[$status] ?? 0) + 1;
            if ($status === 'approved') {
                $agg['vacc_doses'] += (int) ($report->totals['vacc_doses'] ?? 0);
                $agg['opd_total'] += (int) ($report->totals['opd_total'] ?? 0);
                $agg['fp_clients'] += (int) ($report->totals['fp_new'] ?? 0) + (int) ($report->totals['fp_revisit'] ?? 0);
                $agg['deliveries'] += (int) ($report->payload['deliveries'] ?? 0);
            }
        }

        return view('admin.work-reports', [
            'month' => $month,
            'monthDate' => $monthDate,
            'midwives' => $midwives,
            'reports' => $reports,
            'counts' => $counts,
            'agg' => $agg,
        ]);
    }

    /** One report in full: every section, warnings, photo, review actions. */
    public function show(int $id): View
    {
        $report = MonthlyWorkReport::with('midwife.cluster')->findOrFail($id);
        $monthDate = Carbon::createFromFormat('Y-m-d', $report->month . '-01', 'Asia/Kabul');

        return view('admin.work-report-show', [
            'report' => $report,
            'monthDate' => $monthDate,
            'sections' => MonthlyWorkReport::SECTIONS,
            'warnings' => MonthlyWorkReport::warnings($report->payload ?? []),
        ]);
    }

    /** Stream the register photo (stored on the local disk). */
    public function photo(int $id)
    {
        $report = MonthlyWorkReport::findOrFail($id);
        if (!$report->register_photo || !Storage::disk('local')->exists($report->register_photo)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($report->register_photo));
    }

    public function approve(Request $request, int $id)
    {
        $report = MonthlyWorkReport::findOrFail($id);
        if ($report->status !== 'submitted') {
            return back()->withErrors(['فقط راپور ارسال‌شده قابل تأیید است.']);
        }
        $report->update([
            'status' => 'approved',
            'reviewed_at' => AttendanceService::now(),
            'reviewed_by' => $request->user()->id,
            'supervisor_note' => null,
        ]);

        return redirect()->route('admin.workReports', ['month' => $report->month])
            ->with('ok', 'راپور ' . $report->midwife->full_name . ' تأیید شد.');
    }

    public function returnBack(Request $request, int $id)
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ], [
            'note.required' => 'برای برگشت‌دادن راپور، نوشتن نظر لازم است.',
        ]);
        $report = MonthlyWorkReport::findOrFail($id);
        if (!in_array($report->status, ['submitted', 'approved'], true)) {
            return back()->withErrors(['این راپور قابل برگشت نیست.']);
        }
        $report->update([
            'status' => 'returned',
            'reviewed_at' => AttendanceService::now(),
            'reviewed_by' => $request->user()->id,
            'supervisor_note' => $data['note'],
        ]);

        return redirect()->route('admin.workReports', ['month' => $report->month])
            ->with('ok', 'راپور برای اصلاح به قابله برگشت داده شد.');
    }

    /** Excel/CSV: one row per midwife with the key figures of the month. */
    public function export(Request $request)
    {
        $month = $this->monthOf($request);
        $midwives = Midwife::with('cluster')->orderBy('code')->get();
        $reports = MonthlyWorkReport::where('month', $month)->get()->keyBy('midwife_id');

        $headers = [
            'کد قابله', 'نام قابله', 'کلستر', 'وضعیت راپور', 'روزهای کاری',
            'مجموع مراجعین OPD', 'مجموع دوز واکسین', 'OPV1', 'OPV3',
            'اطفال کاملاً واکسین‌شده', 'تنظیم خانواده — جدید', 'تنظیم خانواده — تکراری',
            'مراقبت حاملگی (اول)', 'ولادت‌های کمک‌شده', 'ارجاع‌ها',
        ];

        $body = [];
        foreach ($midwives as $mw) {
            $r = $reports->get($mw->id);
            $p = $r?->payload ?? [];
            $t = $r?->totals ?? [];
            $v = fn (string $k) => DariDate::en((string) (int) ($p[$k] ?? 0));
            $body[] = [
                DariDate::en($mw->code ?? ''),
                $mw->full_name ?? '',
                $mw->cluster ? $mw->cluster->displayLabel() : '',
                $r ? (MonthlyWorkReport::STATUS_LABELS[$r->status] ?? $r->status) : 'نرسیده',
                $v('working_days'),
                DariDate::en((string) (int) ($t['opd_total'] ?? 0)),
                DariDate::en((string) (int) ($t['vacc_doses'] ?? 0)),
                $v('opv1'),
                $v('opv3'),
                $v('fully_immunized'),
                DariDate::en((string) (int) ($t['fp_new'] ?? 0)),
                DariDate::en((string) (int) ($t['fp_revisit'] ?? 0)),
                $v('anc1'),
                $v('deliveries'),
                $v('referrals'),
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

    /** Printable consolidated report: approved reports summed per section. */
    public function print(Request $request): View
    {
        $month = $this->monthOf($request);
        $monthDate = Carbon::createFromFormat('Y-m-d', $month . '-01', 'Asia/Kabul');

        $approved = MonthlyWorkReport::with('midwife.cluster')
            ->where('month', $month)
            ->where('status', 'approved')
            ->get();

        $sums = [];
        foreach (MonthlyWorkReport::numericKeys() as $key) {
            $sums[$key] = 0;
        }
        foreach ($approved as $r) {
            foreach ((array) $r->payload as $key => $value) {
                if (isset($sums[$key]) && is_numeric($value)) {
                    $sums[$key] += (int) $value;
                }
            }
        }

        $totals = MonthlyWorkReport::computeTotals($sums);

        return view('admin.work-reports-print', [
            'month' => $month,
            'monthDate' => $monthDate,
            'approved' => $approved,
            'sections' => MonthlyWorkReport::SECTIONS,
            'sums' => $sums,
            'totals' => $totals,
            'totalMidwives' => Midwife::count(),
            'generatedAt' => AttendanceService::now(),
        ]);
    }
}
