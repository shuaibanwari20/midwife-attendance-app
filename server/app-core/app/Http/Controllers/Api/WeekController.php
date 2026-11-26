<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Support\AttendanceService;
use App\Support\DariDate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeekController extends Controller
{
    /**
     * GET /api/week — current week (شنبه تا جمعه) with per-day status.
     */
    public function index(Request $request): JsonResponse
    {
        $midwife = $request->user()->load('cluster');
        $now = AttendanceService::now();
        $today = $now->copy()->startOfDay();

        // Afghan week starts Saturday. Carbon: Saturday = 6 (0=Sunday..6=Saturday)
        $daysSinceSaturday = ((int) $now->dayOfWeek + 1) % 7;
        $saturday = $today->copy()->subDays($daysSinceSaturday);
        $friday = $saturday->copy()->addDays(6);

        $attendances = Attendance::where('midwife_id', $midwife->id)
            ->whereBetween('attendance_date', [$saturday->toDateString(), $friday->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->attendance_date->toDateString());

        $days = [];
        $attended = 0;
        $elapsedWorkdays = 0;

        for ($i = 0; $i < 7; $i++) {
            $d = $saturday->copy()->addDays($i);
            $attendance = $attendances->get($d->toDateString());
            $isFriday = $d->isFriday();
            $isFuture = $d->greaterThan($today);
            $isToday = $d->isSameDay($today);
            // Days before the account existed are not workdays for her:
            // rendered muted (like future days) and never counted.
            $beforeJoin = ! AttendanceService::hasJoinedBy($midwife, $d);

            if (! $isFuture && ! $isFriday && ! $beforeJoin) {
                $elapsedWorkdays++;
                if ($attendance) {
                    $attended++;
                }
            }

            $days[] = [
                'date_iso' => $d->toDateString(),
                'day' => (int) $d->format('j'),
                'weekday' => DariDate::weekday($d),
                'is_today' => $isToday,
                'is_friday' => $isFriday,
                'is_future' => $isFuture || $beforeJoin,
                'before_join' => $beforeJoin,
                'attended' => (bool) $attendance,
                'status' => $attendance
                    ? $attendance->status
                    : ($beforeJoin ? 'before_join' : ($isFuture ? 'future' : ($isFriday ? 'friday' : 'absent'))),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'week_start' => $saturday->toDateString(),
                'summary' => [
                    'attended' => $attended,
                    'workdays' => $elapsedWorkdays,
                    'label' => DariDate::en((string) $attended).' روز حاضر از '.DariDate::en((string) $elapsedWorkdays).' روز کاری',
                ],
                'days' => $days,
            ],
        ]);
    }
}
