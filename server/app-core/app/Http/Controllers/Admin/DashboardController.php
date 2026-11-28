<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Midwife;
use App\Support\AttendanceService;
use App\Support\DariDate;
use Illuminate\View\View;

/**
 * Today's live dashboard for all 42 midwives.
 * Statuses: on_time (سبز) | late (زرد) | not_arrived/absent (سرخ) | leave (خاکستری) | friday
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $now = AttendanceService::now();
        $today = $now->toDateString();
        $lateUntil = AttendanceService::lateUntil();
        $isFriday = $now->isFriday();

        $attendances = Attendance::with('cluster')
            ->whereDate('attendance_date', $today)
            ->get()
            ->keyBy('midwife_id');

        $leaveIds = Leave::where('status', 'approved')
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->pluck('midwife_id')
            ->all();

        $rows = [];
        $counts = ['on_time' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0, 'friday' => 0, 'before_join' => 0, 'not_arrived' => 0];

        $midwives = Midwife::with('cluster')->orderBy('code')->get();

        foreach ($midwives as $midwife) {
            $attendance = $attendances->get($midwife->id);

            if (! $attendance && ! AttendanceService::hasJoinedBy($midwife, $now)) {
                // Account created today after the cutoff (or later) — her
                // attendance starts on her first obligated day, not today.
                $counts['before_join']++;
                $rows[] = [
                    'midwife' => $midwife,
                    'attendance' => null,
                    'state' => 'before_join',
                    'label' => 'تازه ثبت شده',
                    'time' => '—',
                    'checkout' => '—',
                    'distance' => '—',
                ];
                continue;
            }

            if ($attendance) {
                $state = $attendance->status; // on_time | late
                $label = $attendance->status === 'on_time' ? 'حاضر به‌وقت' : 'دیرکرد';
            } elseif ($isFriday) {
                $state = 'friday';
                $label = 'روز رخصتی (جمعه)';
            } elseif (in_array($midwife->id, $leaveIds, true)) {
                $state = 'leave';
                $label = 'رخصتی';
            } elseif ($now->greaterThan($lateUntil)) {
                $state = 'absent';
                $label = 'غایب';
            } else {
                $state = 'not_arrived';
                $label = 'هنوز نرسیده';
            }

            // 'not_arrived' keeps its OWN bucket — she is counted absent
            // only after work_end passes without a check-in (his rule).
            $counts[$state] = ($counts[$state] ?? 0) + 1;

            // Check-out (حاضری دوم): time, or its state for today.
            $checkout = '—';
            if ($attendance && $attendance->checkout_at) {
                $checkout = DariDate::time($attendance->checkout_at);
            } elseif ($attendance) {
                $coState = AttendanceService::checkoutState($now->copy()->startOfDay(), $attendance);
                $checkout = match ($coState) {
                    'missed' => 'ثبت نشده',
                    'waiting' => 'در انتظار بازه',
                    'open' => 'بازه باز است',
                    default => '—',
                };
            }

            $rows[] = [
                'midwife' => $midwife,
                'attendance' => $attendance,
                'state' => $state,
                'label' => $label,
                'time' => $attendance ? DariDate::time($attendance->checked_at_server) : '—',
                'checkout' => $checkout,
                'distance' => $attendance ? DariDate::en((string) round((float) $attendance->distance_m)).' متر' : '—',
            ];
        }

        return view('admin.dashboard', [
            'rows' => $rows,
            'counts' => $counts,
            'total' => $midwives->count(),
            'now' => $now,
            'todayLabel' => DariDate::full($now),
            'timeLabel' => DariDate::time($now),
        ]);
    }
}
