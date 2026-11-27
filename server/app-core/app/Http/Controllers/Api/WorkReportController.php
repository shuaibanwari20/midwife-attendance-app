<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonthlyWorkReport;
use App\Support\AttendanceService;
use App\Support\DariDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
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
        $photos = [];
        $reviews = [];
        foreach (MonthlyWorkReport::PHOTO_KINDS as $kind) {
            $photos[$kind] = $report->photoPath($kind) !== null;
            $reviews[$kind] = [
                'state' => $photos[$kind] ? $report->photoReview($kind) : 'missing',
                'note' => $report->photoNote($kind),
            ];
        }

        return [
            'month' => $report->month,
            'status' => $report->status,
            'photos' => $photos,
            'photo_reviews' => $reviews,
            'supervisor_note' => $report->supervisor_note,
            'submitted_at' => $report->submitted_at?->toIso8601String(),
            'reviewed_at' => $report->reviewed_at?->toIso8601String(),
            'updated_at' => $report->updated_at?->toIso8601String(),
        ];
    }

    /** Why this one photo cannot be changed right now (null = editable). */
    private function photoLockReason(MonthlyWorkReport $report, string $kind): ?string
    {
        if ($report->photoEditable($kind)) {
            return null;
        }
        if ($report->photoReview($kind) === 'approved' && $report->status !== 'draft') {
            return 'این عکس توسط دفتر تأیید شده و قفل است.';
        }

        return 'این راپور ارسال شده و قفل است؛ برای اصلاح باید دفتر آن را برگشت بدهد.';
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

    /** One month: the report (if any) with its photo state. */
    public function current(Request $request): JsonResponse
    {
        $month = $this->monthOf($request);
        $report = MonthlyWorkReport::where('midwife_id', $request->user()->id)
            ->where('month', $month)
            ->first();

        $carbon = Carbon::createFromFormat('Y-m-d', $month . '-01', 'Asia/Kabul');

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'month_label' => DariDate::monthYear($carbon),
                'report' => $report ? $this->present($report) : null,
            ],
        ]);
    }

    /** Stream the midwife's OWN report photo (kind = opv | opd | fp). */
    public function showPhoto(Request $request, string $kind)
    {
        if (!in_array($kind, MonthlyWorkReport::PHOTO_KINDS, true)) {
            abort(404);
        }
        $month = $this->monthOf($request);
        $report = MonthlyWorkReport::where('midwife_id', $request->user()->id)
            ->where('month', $month)
            ->first();
        $path = $report?->photoPath($kind);
        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($path));
    }

    /** Upload (or replace) one report photo: kind = opv | opd | fp. */
    public function photo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'kind' => ['required', 'in:opv,opd,fp'],
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
        if ($reason = $this->photoLockReason($report, $data['kind'])) {
            return response()->json(['success' => false, 'message' => $reason], 422);
        }

        $old = $report->photoPath($data['kind']);
        $name = 'work-reports/' . $midwifeId . '/' . $data['month'] . '-' . $data['kind'] . '-' . Str::random(8) . '.jpg';
        $request->file('photo')->storeAs(dirname($name), basename($name), 'local');
        if ($old) {
            Storage::disk('local')->delete($old);
        }
        // A (re)sent photo goes back to "pending review" with no office note.
        $report->update([
            'photo_' . $data['kind'] => $name,
            'review_' . $data['kind'] => null,
            'note_' . $data['kind'] => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['report' => $this->present($report->fresh())],
        ]);
    }

    /** Remove one report photo (while the report is not locked). */
    public function deletePhoto(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'kind' => ['required', 'in:opv,opd,fp'],
        ]);

        $report = MonthlyWorkReport::where('midwife_id', $request->user()->id)
            ->where('month', $data['month'])
            ->first();
        if (!$report) {
            return response()->json(['success' => false, 'message' => 'راپور یافت نشد.'], 404);
        }
        if ($reason = $this->photoLockReason($report, $data['kind'])) {
            return response()->json(['success' => false, 'message' => $reason], 422);
        }

        $old = $report->photoPath($data['kind']);
        if ($old) {
            Storage::disk('local')->delete($old);
        }
        $report->update([
            'photo_' . $data['kind'] => null,
            'review_' . $data['kind'] => null,
            'note_' . $data['kind'] => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['report' => $this->present($report->fresh())],
        ]);
    }

    /** Submit the month's three report photos to the office. */
    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $report = MonthlyWorkReport::where('midwife_id', $request->user()->id)
            ->where('month', $data['month'])
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'اول عکس‌های راپور را بفرستید، بعد ارسال کنید.',
            ], 422);
        }
        if ($report->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'این راپور قبلاً تأیید شده است.',
            ], 422);
        }
        if ($report->status === 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'این راپور قبلاً ارسال شده و در انتظار بررسی است.',
            ], 422);
        }

        $missing = [];
        foreach (MonthlyWorkReport::PHOTO_KINDS as $kind) {
            if (!$report->photoPath($kind)) {
                $missing[] = MonthlyWorkReport::PHOTO_LABELS[$kind];
            }
        }
        if ($missing !== []) {
            return response()->json([
                'success' => false,
                'message' => 'عکس این راپورها هنوز فرستاده نشده: ' . implode('، ', $missing),
            ], 422);
        }

        // A photo the office returned must be re-sent before resubmitting.
        $stillReturned = [];
        foreach (MonthlyWorkReport::PHOTO_KINDS as $kind) {
            if ($report->photoReview($kind) === 'returned') {
                $stillReturned[] = MonthlyWorkReport::PHOTO_LABELS[$kind];
            }
        }
        if ($stillReturned !== []) {
            return response()->json([
                'success' => false,
                'message' => 'عکس برگشت‌خورده را اول دوباره بفرستید: ' . implode('، ', $stillReturned),
            ], 422);
        }

        $report->update([
            'status' => 'submitted',
            'submitted_at' => AttendanceService::now(),
            'supervisor_note' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => ['report' => $this->present($report->fresh())],
        ]);
    }
}
