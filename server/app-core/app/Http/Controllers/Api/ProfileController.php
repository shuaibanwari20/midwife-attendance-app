<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Support\AttendanceService;
use App\Support\DariDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /** GET /api/me — profile + cluster + today's attendance + unread badge count. */
    public function me(Request $request): JsonResponse
    {
        $midwife = $request->user()->load('cluster');
        $today = AttendanceService::now()->toDateString();

        $todayAttendance = Attendance::where('midwife_id', $midwife->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $unread = \App\Models\Notification::query()
            ->where(function ($q) use ($midwife) {
                $q->where('target', 'all')
                    ->orWhere(function ($q2) use ($midwife) {
                        $q2->where('target', 'cluster')->where('cluster_id', $midwife->cluster_id);
                    })
                    ->orWhere(function ($q2) use ($midwife) {
                        $q2->where('target', 'midwife')->where('midwife_id', $midwife->id);
                    });
            })
            ->whereDoesntHave('readers', function ($q) use ($midwife) {
                $q->where('midwife_id', $midwife->id)->whereNotNull('notification_reads.read_at');
            })
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'midwife' => [
                    'id' => $midwife->id,
                    'code' => DariDate::en($midwife->code),
                    'full_name' => $midwife->full_name,
                    'phone' => DariDate::en((string) $midwife->phone),
                    'leave_balance' => $midwife->leave_balance,
                    'initial' => mb_substr($midwife->full_name, 0, 1),
                ],
                'cluster' => $midwife->cluster ? [
                    'id' => $midwife->cluster->id,
                    'number' => DariDate::en((string) (int) preg_replace('/\D+/', '', $midwife->cluster->code)),
                    'name' => $midwife->cluster->name,
                    'village' => $midwife->cluster->village,
                    'display' => $midwife->cluster->displayLabel(),
                    'lat' => (float) $midwife->cluster->lat,
                    'lng' => (float) $midwife->cluster->lng,
                    'radius_m' => (int) $midwife->cluster->radius_m,
                ] : null,
                'today_attendance' => $todayAttendance ? [
                    'status' => $todayAttendance->status,
                    'status_label' => $todayAttendance->status === 'on_time' ? 'حاضر به‌وقت' : 'دیرکرد',
                    'time' => DariDate::time24($todayAttendance->checked_at_server),
                    'time_display' => DariDate::time($todayAttendance->checked_at_server),
                    'tracking_code' => DariDate::en($todayAttendance->tracking_code),
                    'offline' => $todayAttendance->offline,
                    'checkout_time' => $todayAttendance->checkout_at ? DariDate::time24($todayAttendance->checkout_at) : null,
                    'checkout_distance_m' => $todayAttendance->checkout_at ? round((float) $todayAttendance->checkout_distance_m) : null,
                    'checkout_offline' => (bool) $todayAttendance->checkout_offline,
                    'checkout_state' => AttendanceService::checkoutState(AttendanceService::now()->startOfDay(), $todayAttendance),
                ] : null,
                'unread_notifications' => $unread,
                // Fingerprint-key binding state for the app.
                'biometric' => [
                    'state' => \App\Support\BiometricGuard::state($midwife),
                ],
            ],
        ]);
    }
}
