<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Support\AttendanceService;
use App\Support\DariDate;
use App\Support\Geo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * POST /api/check-in
     *
     * Server-side rules (never trust the client):
     *  - reject fake/mock GPS
     *  - reject poor GPS accuracy (> 50 m)
     *  - recompute distance from cluster coordinates, reject outside geofence
     *  - status computed from server time (Asia/Kabul)
     *  - one check-in per day (returns the existing record on duplicate)
     */
    public function checkIn(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'is_mocked' => ['nullable', 'boolean'],
            'device_time' => ['nullable', 'date'],
            'offline' => ['nullable', 'boolean'],
            'client_uuid' => ['nullable', 'string', 'max:64'],
        ]);

        $midwife = $request->user()->load('cluster');
        $cluster = $midwife->cluster;

        if (! $cluster) {
            return response()->json([
                'success' => false,
                'message' => 'شما به هیچ کلستری متصل نیستید. با سوپروایزر تماس بگیرید.',
            ], 422);
        }

        // 1) Fake GPS rejection
        if (! empty($data['is_mocked'])) {
            return response()->json([
                'success' => false,
                'message' => 'موقعیت جعلی (GPS جعلی) شناسایی شد. ثبت حضور با GPS جعلی ممکن نیست.',
                'error_code' => 'mocked_gps',
            ], 422);
        }

        // 2) Accuracy gate
        $accuracy = isset($data['accuracy']) ? (float) $data['accuracy'] : null;
        $maxAccuracy = (float) config('midwife.max_gps_accuracy', 50);
        if ($accuracy !== null && $accuracy > $maxAccuracy) {
            return response()->json([
                'success' => false,
                'message' => 'دقت GPS کافی نیست ('.DariDate::en((string) round($accuracy)).' متر خطا). به فضای باز بروید و دوباره تلاش کنید.',
                'error_code' => 'low_accuracy',
            ], 422);
        }

        // 3) Distance — recomputed server-side from cluster center
        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];
        $distance = Geo::distanceMeters($lat, $lng, (float) $cluster->lat, (float) $cluster->lng);
        $radius = (int) $cluster->radius_m;

        if ($distance > $radius) {
            return response()->json([
                'success' => false,
                'message' => 'خارج از ساحه کلستر هستید. فاصله شما تا مرکز کلستر '.DariDate::en((string) round($distance)).' متر است (حد مجاز '.DariDate::en((string) $radius).' متر).',
                'error_code' => 'outside_geofence',
                'data' => ['distance_m' => $distance, 'radius_m' => $radius],
            ], 422);
        }

        // 4) One check-in per day (server date, Asia/Kabul)
        $now = AttendanceService::now();
        $today = $now->toDateString();

        $existing = Attendance::where('midwife_id', $midwife->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'already_recorded' => true,
                'message' => 'حضور امروز شما قبلاً ثبت شده است.',
                'data' => ['attendance' => $this->attendancePayload($existing, $cluster)],
            ]);
        }

        // 4b) Work-hours lock — after the day's end time (set by the admin)
        // check-in is closed: the day is over and she is absent for it.
        if ($now->greaterThan(AttendanceService::workEnd())) {
            return response()->json([
                'success' => false,
                'message' => 'ساعت کاری امروز تمام شده است — ثبت حضور قفل است.',
                'error_code' => 'checkin_locked',
            ], 422);
        }

        // 5) Status from server time
        $status = AttendanceService::statusFor($now);

        $attendance = Attendance::create([
            'midwife_id' => $midwife->id,
            'cluster_id' => $cluster->id,
            'attendance_date' => $today,
            'checked_at_server' => $now,
            'device_time' => isset($data['device_time']) ? Carbon::parse($data['device_time']) : $now,
            'lat' => $lat,
            'lng' => $lng,
            'distance_m' => $distance,
            'gps_accuracy_m' => $accuracy,
            'is_mocked' => false,
            'status' => $status['status'],
            'late_minutes' => $status['late_minutes'],
            'offline' => (bool) ($data['offline'] ?? false),
            'tracking_code' => AttendanceService::generateTrackingCode(),
            'client_uuid' => $data['client_uuid'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'already_recorded' => false,
            'message' => $status['status'] === 'on_time'
                ? 'حضور شما با موفقیت ثبت شد.'
                : 'حضور شما با '.DariDate::en((string) $status['late_minutes']).' دقیقه تاخیر ثبت شد.',
            'data' => ['attendance' => $this->attendancePayload($attendance, $cluster)],
        ]);
    }

    public static function attendancePayload(Attendance $attendance, ?object $cluster = null): array
    {
        $cluster = $cluster ?: $attendance->cluster;

        return [
            'id' => $attendance->id,
            'date' => DariDate::full($attendance->checked_at_server),
            'date_iso' => $attendance->attendance_date->toDateString(),
            'time' => DariDate::time24($attendance->checked_at_server),
            'time_display' => DariDate::time($attendance->checked_at_server),
            'status' => $attendance->status,
            'status_label' => $attendance->status === 'on_time' ? 'حاضر به‌وقت' : 'دیرکرد',
            'late_minutes' => $attendance->late_minutes,
            'late_label' => $attendance->status === 'late'
                ? 'دیرکرد '.DariDate::en((string) $attendance->late_minutes).' دقیقه'
                : 'حاضر به‌وقت',
            'distance_m' => round((float) $attendance->distance_m),
            'distance_label' => DariDate::en((string) round((float) $attendance->distance_m)).' متر',
            'cluster' => $cluster ? [
                'number' => DariDate::en((string) (int) preg_replace('/\D+/', '', $cluster->code)),
                'village' => $cluster->village,
                'display' => 'کلستر '.DariDate::en((string) (int) preg_replace('/\D+/', '', $cluster->code)).' — '.$cluster->village,
            ] : null,
            'offline' => (bool) $attendance->offline,
            'tracking_code' => DariDate::en($attendance->tracking_code),
        ];
    }
}
