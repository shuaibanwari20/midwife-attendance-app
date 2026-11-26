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
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * POST /api/sync — batch upload of offline check-ins.
     * Each record carries a client_uuid for idempotency. Records are flagged offline=true.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'records' => ['required', 'array', 'max:30'],
            'records.*.client_uuid' => ['required', 'string', 'max:64'],
            'records.*.device_time' => ['required', 'date'],
            'records.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'records.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'records.*.accuracy' => ['nullable', 'numeric', 'min:0'],
            'records.*.is_mocked' => ['nullable', 'boolean'],
        ]);

        $midwife = $request->user()->load('cluster');
        $cluster = $midwife->cluster;

        if (! $cluster) {
            return response()->json([
                'success' => false,
                'message' => 'شما به هیچ کلستری متصل نیستید.',
            ], 422);
        }

        $results = [];

        DB::transaction(function () use ($data, $midwife, $cluster, &$results) {
            foreach ($data['records'] as $record) {
                $uuid = $record['client_uuid'];

                $existing = Attendance::where('client_uuid', $uuid)->first();
                if ($existing) {
                    $results[] = ['client_uuid' => $uuid, 'status' => 'duplicate', 'message' => 'قبلاً همگام شده بود.'];
                    continue;
                }

                if (! empty($record['is_mocked'])) {
                    $results[] = ['client_uuid' => $uuid, 'status' => 'rejected', 'message' => 'GPS جعلی شناسایی شد.'];
                    continue;
                }

                $accuracy = isset($record['accuracy']) ? (float) $record['accuracy'] : null;
                $maxAccuracy = (float) config('midwife.max_gps_accuracy', 50);
                if ($accuracy !== null && $accuracy > max($maxAccuracy, 100)) {
                    $results[] = ['client_uuid' => $uuid, 'status' => 'rejected', 'message' => 'دقت GPS برای ثبت آفلاین کافی نیست.'];
                    continue;
                }

                $distance = Geo::distanceMeters(
                    (float) $record['lat'],
                    (float) $record['lng'],
                    (float) $cluster->lat,
                    (float) $cluster->lng
                );

                if ($distance > (int) $cluster->radius_m) {
                    $results[] = [
                        'client_uuid' => $uuid,
                        'status' => 'rejected',
                        'message' => 'خارج از ساحه کلستر ('.DariDate::en((string) round($distance)).' متر).',
                    ];
                    continue;
                }

                $deviceTime = Carbon::parse($record['device_time']);
                $kabulTime = $deviceTime->copy()->setTimezone(AttendanceService::timezone());
                $date = $kabulTime->toDateString();

                $existingToday = Attendance::where('midwife_id', $midwife->id)
                    ->whereDate('attendance_date', $date)
                    ->first();
                if ($existingToday) {
                    $results[] = ['client_uuid' => $uuid, 'status' => 'duplicate', 'message' => 'برای این روز قبلاً ثبت وجود داشت.'];
                    continue;
                }

                // Work-hours lock for that day: an offline check-in captured
                // after the day's end time does not count — the day was over.
                $workEnd = AttendanceService::workEnd()->setDate(
                    (int) $kabulTime->year, (int) $kabulTime->month, (int) $kabulTime->day
                );
                if ($kabulTime->greaterThan($workEnd)) {
                    $results[] = ['client_uuid' => $uuid, 'status' => 'rejected', 'message' => 'ساعت کاری آن روز تمام شده بود — ثبت حضور قفل است.'];
                    continue;
                }

                $status = AttendanceService::statusFor($kabulTime);

                $attendance = Attendance::create([
                    'midwife_id' => $midwife->id,
                    'cluster_id' => $cluster->id,
                    'attendance_date' => $date,
                    'checked_at_server' => AttendanceService::now(),
                    'device_time' => $deviceTime,
                    'lat' => (float) $record['lat'],
                    'lng' => (float) $record['lng'],
                    'distance_m' => $distance,
                    'gps_accuracy_m' => $accuracy,
                    'is_mocked' => false,
                    'status' => $status['status'],
                    'late_minutes' => $status['late_minutes'],
                    'offline' => true,
                    'tracking_code' => AttendanceService::generateTrackingCode(),
                    'client_uuid' => $uuid,
                ]);

                $results[] = [
                    'client_uuid' => $uuid,
                    'status' => 'synced',
                    'message' => 'ثبت آفلاین همگام شد.',
                    'attendance' => AttendanceController::attendancePayload($attendance, $cluster),
                ];
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'همگام‌سازی انجام شد.',
            'data' => ['results' => $results],
        ]);
    }
}
