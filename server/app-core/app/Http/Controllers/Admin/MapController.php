<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Cluster;
use App\Models\Leave;
use App\Models\Midwife;
use App\Support\AttendanceService;
use App\Support\DariDate;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(): View
    {
        return view('admin.map');
    }

    /** JSON feed for the Leaflet map: 42 clusters + today's status per midwife. */
    public function data(): JsonResponse
    {
        $today = AttendanceService::now()->toDateString();

        $attendances = Attendance::whereDate('attendance_date', $today)->get()->keyBy('midwife_id');
        $leaveIds = Leave::where('status', 'approved')
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->pluck('midwife_id')
            ->all();

        $clusters = Cluster::with('midwives')->orderBy('code')->get()->map(function ($cluster) use ($attendances, $leaveIds, $today) {
            $statuses = [];
            foreach ($cluster->midwives as $midwife) {
                $attendance = $attendances->get($midwife->id);
                if ($attendance) {
                    $state = $attendance->status;
                    $label = ($attendance->status === 'on_time' ? 'حاضر به‌وقت' : 'دیرکرد').' — '.DariDate::time($attendance->checked_at_server);
                } elseif (! AttendanceService::hasJoinedBy($midwife, AttendanceService::now())) {
                    $state = 'before_join';
                    $label = 'تازه ثبت شده';
                } elseif (in_array($midwife->id, $leaveIds, true)) {
                    $state = 'leave';
                    $label = 'رخصتی';
                } elseif (AttendanceService::now()->isFriday()) {
                    $state = 'friday';
                    $label = 'روز رخصتی (جمعه)';
                } elseif (AttendanceService::now()->greaterThan(AttendanceService::workEnd())) {
                    $state = 'absent';
                    $label = 'غایب';
                } else {
                    $state = 'not_arrived';
                    $label = 'هنوز نرسیده';
                }
                $statuses[] = [
                    'name' => $midwife->full_name,
                    'code' => DariDate::en($midwife->code),
                    'state' => $state,
                    'label' => $label,
                ];
            }

            return [
                'id' => $cluster->id,
                'code' => DariDate::en($cluster->code),
                'name' => $cluster->name,
                'village' => $cluster->village,
                'lat' => (float) $cluster->lat,
                'lng' => (float) $cluster->lng,
                'radius_m' => (int) $cluster->radius_m,
                'midwives' => $statuses,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => DariDate::full(AttendanceService::now()),
                'clusters' => $clusters,
            ],
        ]);
    }
}
