<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Support\AttendanceService;
use App\Support\DariDate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    /**
     * GET /api/history?month=YYYY-MM  (or month=all)
     */
    public function index(Request $request): JsonResponse
    {
        $midwife = $request->user()->load('cluster');
        $month = $request->query('month', AttendanceService::now()->format('Y-m'));
        $all = $month === 'all';

        if (! $all && ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return response()->json(['success' => false, 'message' => 'ماه نامعتبر است.'], 422);
        }

        if ($all) {
            return $this->allHistory($midwife);
        }

        $start = Carbon::createFromDate((int) substr($month, 0, 4), (int) substr($month, 5, 2), 1, AttendanceService::timezone())->startOfDay();
        $end = $start->copy()->endOfMonth();
        $today = AttendanceService::now()->startOfDay();

        $attendances = Attendance::where('midwife_id', $midwife->id)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->attendance_date->toDateString());

        $leaveDays = [];
        $leaves = \App\Models\Leave::where('midwife_id', $midwife->id)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $end->toDateString())
            ->whereDate('to_date', '>=', $start->toDateString())
            ->get();
        foreach ($leaves as $leave) {
            $d = $leave->from_date->copy()->max($start)->startOfDay();
            $last = $leave->to_date->copy()->min($end)->startOfDay();
            while ($d->lte($last)) {
                $leaveDays[$d->toDateString()] = true;
                $d->addDay();
            }
        }

        $days = [];
        $onTime = 0;
        $late = 0;
        $absent = 0;
        $leaveCount = 0;

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $attendance = $attendances->get($key);
            $status = AttendanceService::dayStatus($midwife, $cursor, $attendance);

            // Days before the account existed are not part of her record at
            // all — no list entry, no absent count.
            if ($status === 'before_join') {
                $cursor->addDay();
                continue;
            }

            if ($status === 'on_time') {
                $onTime++;
            } elseif ($status === 'late') {
                $late++;
            } elseif ($status === 'absent') {
                $absent++;
            } elseif ($status === 'leave') {
                $leaveCount++;
            }

            $days[] = [
                'date_iso' => $key,
                'day' => (int) $cursor->format('j'),
                'month_name' => DariDate::monthName((int) $cursor->format('n')),
                'weekday' => DariDate::weekday($cursor),
                'is_today' => $cursor->isSameDay($today),
                'status' => $status,
                'status_label' => match ($status) {
                    'on_time' => 'حاضر',
                    'late' => 'دیرکرد',
                    'absent' => 'غایب',
                    'leave' => 'رخصتی',
                    'friday' => 'روز رخصتی (جمعه)',
                    'future' => 'آینده',
                    'pending' => 'هنوز نرسیده',
                    default => '—',
                },
                'check_in_time' => $attendance ? DariDate::time24($attendance->checked_at_server) : null,
                'check_in_display' => $attendance ? DariDate::time($attendance->checked_at_server) : null,
                'checkout_time' => $attendance && $attendance->checkout_at ? DariDate::time24($attendance->checkout_at) : null,
                'late_minutes' => $attendance ? $attendance->late_minutes : 0,
                'note' => $attendance && $attendance->status === 'late'
                    ? DariDate::time24($attendance->checked_at_server).' — '.DariDate::en((string) $attendance->late_minutes).' دقیقه دیر'
                    : ($attendance ? 'ثبت حضور: '.DariDate::time24($attendance->checked_at_server) : null),
                'tracking_code' => $attendance ? DariDate::en($attendance->tracking_code) : null,
                'offline' => $attendance ? (bool) $attendance->offline : false,
            ];

            $cursor->addDay();
        }

        // Newest first for the list
        $list = array_reverse($days);

        // حضور به‌وقت = on_time / (on_time + late + absent) — leave days excluded (design shows 90%).
        $percent = (int) round($onTime / max(1, $onTime + $late + $absent) * 100);

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'month_label' => 'ماه '.DariDate::monthName((int) substr($month, 5, 2)).' '.substr($month, 0, 4),
                'stats' => [
                    'on_time' => $onTime,
                    'late' => $late,
                    'absent' => $absent,
                    'leave' => $leaveCount,
                    'on_time_percent' => $percent,
                ],
                'leave_balance' => $midwife->leave_balance,
                'days' => $list,
            ],
        ]);
    }

    private function allHistory($midwife): JsonResponse
    {
        $records = Attendance::where('midwife_id', $midwife->id)
            ->orderByDesc('attendance_date')
            ->limit(120)
            ->get();

        $leaves = \App\Models\Leave::where('midwife_id', $midwife->id)
            ->where('status', 'approved')
            ->orderByDesc('from_date')
            ->limit(20)
            ->get();

        $rows = [];

        foreach ($records as $attendance) {
            $d = $attendance->attendance_date;
            $rows[] = [
                'date_iso' => $d->toDateString(),
                'day' => (int) $d->format('j'),
                'month_name' => DariDate::monthName((int) $d->format('n')),
                'weekday' => DariDate::weekday($d),
                'is_today' => $d->isSameDay(AttendanceService::now()->startOfDay()),
                'status' => $attendance->status,
                'status_label' => $attendance->status === 'on_time' ? 'حاضر' : 'دیرکرد',
                'check_in_time' => DariDate::time24($attendance->checked_at_server),
                'check_in_display' => DariDate::time($attendance->checked_at_server),
                'checkout_time' => $attendance->checkout_at ? DariDate::time24($attendance->checkout_at) : null,
                'late_minutes' => $attendance->late_minutes,
                'note' => $attendance->status === 'late'
                    ? DariDate::time24($attendance->checked_at_server).' — '.DariDate::en((string) $attendance->late_minutes).' دقیقه دیر'
                    : 'ثبت حضور: '.DariDate::time24($attendance->checked_at_server),
                'tracking_code' => DariDate::en($attendance->tracking_code),
                'offline' => (bool) $attendance->offline,
            ];
        }

        foreach ($leaves as $leave) {
            $d = $leave->from_date;
            $rows[] = [
                'date_iso' => $d->toDateString(),
                'day' => (int) $d->format('j'),
                'month_name' => DariDate::monthName((int) $d->format('n')),
                'weekday' => DariDate::weekday($d),
                'is_today' => false,
                'status' => 'leave',
                'status_label' => 'رخصتی',
                'check_in_time' => null,
                'check_in_display' => null,
                'late_minutes' => 0,
                'note' => 'رخصتی تایید شده ('.DariDate::dayMonthYear($leave->from_date).' تا '.DariDate::dayMonthYear($leave->to_date).')',
                'tracking_code' => null,
                'offline' => false,
            ];
        }

        usort($rows, fn ($a, $b) => strcmp($b['date_iso'], $a['date_iso']));

        $onTime = (int) $records->where('status', 'on_time')->count();
        $late = (int) $records->where('status', 'late')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'month' => 'all',
                'month_label' => 'همه سوابق',
                'stats' => [
                    'on_time' => $onTime,
                    'late' => $late,
                    'absent' => 0,
                    'leave' => $leaves->count(),
                    'on_time_percent' => ($onTime + $late) > 0 ? (int) round($onTime / ($onTime + $late) * 100) : 0,
                ],
                'leave_balance' => $midwife->leave_balance,
                'days' => array_slice($rows, 0, 60),
            ],
        ]);
    }
}
