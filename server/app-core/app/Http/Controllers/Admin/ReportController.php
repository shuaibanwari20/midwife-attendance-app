<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Cluster;
use App\Models\Midwife;
use App\Support\DariDate;
use App\Support\XlsxExporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $query = $this->query($filters);

        $rows = $query->with(['midwife.cluster', 'cluster'])
            ->orderByDesc('attendance_date')
            ->orderByDesc('checked_at_server')
            ->paginate(25)
            ->withQueryString();

        // Monthly summary per midwife for the selected range
        $summary = $this->query($filters)
            ->selectRaw('midwife_id, status, COUNT(*) as total')
            ->groupBy('midwife_id', 'status')
            ->get()
            ->groupBy('midwife_id');

        $summaryRows = Midwife::with('cluster')->orderBy('code')->get()->map(function ($midwife) use ($summary, $filters) {
            $stats = $summary->get($midwife->id, collect());
            $onTime = (int) ($stats->firstWhere('status', 'on_time')->total ?? 0);
            $late = (int) ($stats->firstWhere('status', 'late')->total ?? 0);

            return [
                'midwife' => $midwife,
                'on_time' => $onTime,
                'late' => $late,
                'total' => $onTime + $late,
            ];
        })->filter(fn ($r) => $r['total'] > 0 || empty($filters['midwife_id']) && empty($filters['cluster_id']) && empty($filters['status']));

        return view('admin.reports', [
            'rows' => $rows,
            'summaryRows' => $summaryRows->values(),
            'clusters' => Cluster::orderBy('code')->get(),
            'midwives' => Midwife::orderBy('code')->get(),
            'filters' => $filters,
        ]);
    }

    /**
     * Print-ready monthly report (Feature 3) — one row per midwife with
     * present / late / absent / leave counted exactly like the app history
     * (AttendanceService::dayStatus), plus totals and signature blocks.
     * The admin prints it or saves it as PDF from the browser.
     */
    public function monthlyPrint(Request $request): View
    {
        $monthParam = (string) $request->query('month', '');
        $start = preg_match('/^\d{4}-\d{2}$/', $monthParam)
            ? Carbon::createFromFormat('Y-m', $monthParam, 'Asia/Kabul')->startOfMonth()
            : \App\Support\AttendanceService::now()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $today = \App\Support\AttendanceService::now()->startOfDay();
        $lastDay = $end->lessThan($today) ? $end : $today;

        $attendances = Attendance::whereDate('attendance_date', '>=', $start->toDateString())
            ->whereDate('attendance_date', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn ($a) => $a->midwife_id.'|'.Carbon::parse($a->attendance_date)->toDateString());

        $rows = [];
        $totals = ['on_time' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0];
        foreach (Midwife::with('cluster')->orderBy('code')->get() as $midwife) {
            $counts = ['on_time' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0];
            for ($day = $start->copy(); $day->lessThanOrEqualTo($lastDay); $day->addDay()) {
                $attendance = $attendances->get($midwife->id.'|'.$day->toDateString());
                $status = \App\Support\AttendanceService::dayStatus($midwife, $day->copy(), $attendance);
                if (isset($counts[$status])) {
                    $counts[$status]++;
                }
            }
            $attended = $counts['on_time'] + $counts['late'];
            $obligated = $attended + $counts['absent'];
            $counts['percent'] = $obligated > 0 ? (int) round($attended / $obligated * 100) : 100;
            foreach ($totals as $k => $_) {
                $totals[$k] += $counts[$k];
            }
            $rows[] = ['midwife' => $midwife] + $counts;
        }
        $totAttended = $totals['on_time'] + $totals['late'];
        $totObligated = $totAttended + $totals['absent'];
        $totals['percent'] = $totObligated > 0 ? (int) round($totAttended / $totObligated * 100) : 100;

        return view('admin.reports-monthly-print', [
            'rows' => $rows,
            'totals' => $totals,
            'monthStart' => $start,
            'monthEnd' => $lastDay,
            'generatedAt' => \App\Support\AttendanceService::now(),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->query($filters)
            ->with(['midwife.cluster', 'cluster'])
            ->orderBy('attendance_date')
            ->orderBy('checked_at_server')
            ->get();

        $headers = [
            'تاریخ', 'روز هفته', 'کد قابله', 'نام قابله', 'کلستر', 'ساعت ثبت',
            'وضعیت', 'دقیقه تاخیر', 'فاصله (متر)', 'دقت GPS (متر)', 'نوع ثبت',
            'کد پیگیری',
        ];

        $body = $rows->map(function ($a) {
            return [
                DariDate::dayMonthYear($a->attendance_date),
                DariDate::weekday($a->attendance_date),
                DariDate::en($a->midwife->code ?? ''),
                $a->midwife->full_name ?? '',
                $a->cluster ? $a->cluster->displayLabel() : '',
                DariDate::time($a->checked_at_server),
                $a->status === 'on_time' ? 'حاضر به‌وقت' : 'دیرکرد',
                DariDate::en((string) $a->late_minutes),
                DariDate::en((string) round((float) $a->distance_m)),
                $a->gps_accuracy_m !== null ? DariDate::en((string) round((float) $a->gps_accuracy_m)) : '',
                $a->offline ? 'آفلاین' : 'آنلاین',
                DariDate::en($a->tracking_code),
            ];
        })->all();

        $filenameBase = 'attendance-report-'.now()->format('Y-m-d-His');

        if (($request->query('format', 'csv')) === 'xlsx') {
            $binary = XlsxExporter::build($headers, $body);

            return response($binary, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$filenameBase.'.xlsx"',
            ]);
        }

        // CSV with UTF-8 BOM (opens correctly in Excel)
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', array_map(fn ($h) => '"'.str_replace('"', '""', $h).'"', $headers))."\r\n";
        foreach ($body as $row) {
            $csv .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $row))."\r\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filenameBase.'.csv"',
        ]);
    }

    private function filters(Request $request): array
    {
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        return [
            'from' => $from,
            'to' => $to,
            'cluster_id' => $request->query('cluster_id') ?: null,
            'midwife_id' => $request->query('midwife_id') ?: null,
            'status' => $request->query('status') ?: null,
        ];
    }

    private function query(array $filters)
    {
        return Attendance::query()
            ->whereDate('attendance_date', '>=', $filters['from'])
            ->whereDate('attendance_date', '<=', $filters['to'])
            ->when($filters['cluster_id'], fn ($q) => $q->where('cluster_id', $filters['cluster_id']))
            ->when($filters['midwife_id'], fn ($q) => $q->where('midwife_id', $filters['midwife_id']))
            ->when($filters['status'], fn ($q) => $q->where('status', $filters['status']));
    }
}
