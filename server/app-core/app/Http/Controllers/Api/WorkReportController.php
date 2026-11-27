<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlyWorkReport;
use App\Models\WorkReportDailyEntry;
use App\Support\AttendanceService;
use App\Support\DariDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class WorkReportController extends Controller
{
    private function monthOf(Request $request): string
    {
        $month = (string) $request->query('month', $request->input('month', ''));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = AttendanceService::now()->format('Y-m');
        }

        return $month;
    }

    private function present(MonthlyWorkReport $report): array
    {
        return [
            'month' => $report->month,
            'status' => $report->status,
            'payload' => $report->payload ?? [],
            'totals' => $report->totals ?? [],
            'supervisor_note' => $report->supervisor_note,
            'has_photo' => $report->register_photo !== null,
            'submitted_at' => $report->submitted_at?->toIso8601String(),
            'reviewed_at' => $report->reviewed_at?->toIso8601String(),
            'updated_at' => $report->updated_at?->toIso8601String(),
        ];
    }

    /** Own reports: the last 12 months with their status (or missing). */
    public function months(Request $request): JsonResponse
    {
        $midwifeId = $request->user()->id;
        $reports = MonthlyWorkReport::where('midwife_id', $midwifeId)
            ->orderByDesc('month')
            ->limit(12)
            ->get()
            ->keyBy('month');

        $items = [];
        $cursor = AttendanceService::now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $key = $cursor->format('Y-m');
            $report = $reports->get($key);
            $items[] = [
                'month' => $key,
                'month_label' => DariDate::monthYear($cursor),
                'status' => $report?->status ?? 'missing',
                'updated_at' => $report?->updated_at?->toIso8601String(),
            ];
            $cursor = $cursor->subMonth();
        }

        return response()->json(['success' => true, 'data' => ['items' => $items]]);
    }

    /** One month: the report (if any) + the sums of its daily entries. */
    public function current(Request $request): JsonResponse
    {
        $month = $this->monthOf($request);
        $midwifeId = $request->user()->id;
        $report = MonthlyWorkReport::where('midwife_id', $midwifeId)
            ->where('month', $month)
            ->first();

        $carbon = Carbon::createFromFormat('Y-m-d', $month . '-01', 'Asia/Kabul');

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'month_label' => DariDate::monthYear($carbon),
                'report' => $report ? $this->present($report) : null,
                'daily_sums' => WorkReportDailyEntry::monthSums($midwifeId, $month),
                'warnings' => $report && $report->payload
                    ? MonthlyWorkReport::warnings($report->payload)
                    : [],
            ],
        ]);
    }

    /** Save (or update) the draft of a month. Locked once submitted. */
    public function saveDraft(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'payload' => ['required', 'array'],
        ], [
            'month.required' => 'ماه راپور مشخص نیست.',
            'payload.required' => 'معلومات راپور خالی است.',
        ]);

        $midwifeId = $request->user()->id;
        $report = MonthlyWorkReport::where('midwife_id', $midwifeId)
            ->where('month', $data['month'])
            ->first();

        if ($report && in_array($report->status, ['submitted', 'approved'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'این راپور ارسال شده و قفل است؛ برای اصلاح باید دفتر آن را برگشت بدهد.',
            ], 422);
        }

        $payload = MonthlyWorkReport::cleanPayload($data['payload']);
        $report = MonthlyWorkReport::updateOrCreate(
            ['midwife_id' => $midwifeId, 'month' => $data['month']],
            [
                'status' => $report?->status === 'returned' ? 'returned' : 'draft',
                'payload' => $payload,
                'totals' => MonthlyWorkReport::computeTotals($payload),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $this->present($report),
                'warnings' => MonthlyWorkReport::warnings($payload),
            ],
        ]);
    }

    /** Submit a saved draft to the office. */
    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $report = MonthlyWorkReport::where('midwife_id', $request->user()->id)
            ->where('month', $data['month'])
            ->first();

        if (!$report || empty($report->payload)) {
            return response()->json([
                'success' => false,
                'message' => 'اول راپور را ذخیره کنید، بعد ارسال کنید.',
            ], 422);
        }
        if ($report->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'این راپور قبلاً تأیید شده است.',
            ], 422);
        }
        if (($report->payload['working_days'] ?? 0) < 1 && ($report->totals['opd_total'] ?? 0) < 1 && ($report->totals['vacc_doses'] ?? 0) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'راپور خالی است؛ حداقل روزهای کاری یا ارقام کارکرد را وارد کنید.',
            ], 422);
        }

        $report->update([
            'status' => 'submitted',
            'submitted_at' => AttendanceService::now(),
            'supervisor_note' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $this->present($report->fresh()),
                'warnings' => MonthlyWorkReport::warnings($report->payload ?? []),
            ],
        ]);
    }

    /** Attach a photo of the paper register / tally sheet for the month. */
    public function photo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'photo' => ['required', 'image', 'max:8192'],
        ], [
            'photo.required' => 'عکس انتخاب نشده است.',
            'photo.image' => 'فایل باید عکس باشد.',
            'photo.max' => 'حجم عکس باید کمتر از 8 مگابایت باشد.',
        ]);

        $midwifeId = $request->user()->id;
        $report = MonthlyWorkReport::firstOrCreate(
            ['midwife_id' => $midwifeId, 'month' => $data['month']],
            ['status' => 'draft']
        );
        if (in_array($report->status, ['submitted', 'approved'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'این راپور ارسال شده و قفل است.',
            ], 422);
        }

        $name = 'work-reports/' . $midwifeId . '/' . $data['month'] . '-' . Str::random(8) . '.jpg';
        $request->file('photo')->storeAs(dirname($name), basename($name), 'local');
        $report->update(['register_photo' => $name]);

        return response()->json([
            'success' => true,
            'data' => ['has_photo' => true],
        ]);
    }

    /** Daily quick entry: upsert one day's counters. */
    public function saveDaily(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'payload' => ['required', 'array'],
        ], [
            'date.required' => 'تاریخ مشخص نیست.',
        ]);

        $date = Carbon::createFromFormat('Y-m-d', $data['date'], 'Asia/Kabul');
        if ($date->gt(AttendanceService::now()->endOfDay()) || $date->lt(AttendanceService::now()->subDays(62)->startOfDay())) {
            return response()->json([
                'success' => false,
                'message' => 'تاریخ باید در دو ماه اخیر باشد.',
            ], 422);
        }

        $clean = MonthlyWorkReport::cleanPayload($data['payload']);
        $daily = array_intersect_key($clean, array_flip(MonthlyWorkReport::dailyKeys()));

        $entry = WorkReportDailyEntry::updateOrCreate(
            ['midwife_id' => $request->user()->id, 'entry_date' => $data['date']],
            ['payload' => $daily]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $data['date'],
                'payload' => $entry->payload ?? [],
                'daily_sums' => WorkReportDailyEntry::monthSums($request->user()->id, $date->format('Y-m')),
            ],
        ]);
    }

    /** Daily entries of one month + their sums. */
    public function daily(Request $request): JsonResponse
    {
        $month = $this->monthOf($request);
        $entries = WorkReportDailyEntry::where('midwife_id', $request->user()->id)
            ->where('entry_date', 'like', $month . '%')
            ->orderBy('entry_date')
            ->get()
            ->map(fn ($e) => [
                'date' => $e->entry_date->format('Y-m-d'),
                'payload' => $e->payload ?? [],
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'entries' => $entries,
                'daily_sums' => WorkReportDailyEntry::monthSums($request->user()->id, $month),
            ],
        ]);
    }
}
