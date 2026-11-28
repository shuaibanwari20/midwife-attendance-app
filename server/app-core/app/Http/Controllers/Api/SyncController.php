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
            'records.*.kind' => ['nullable', 'in:checkin,checkout'],
            'records.*.bio_signature' => ['nullable', 'string', 'max:1024'],
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

                // Fingerprint-key gate for offline records: an enrolled
                // midwife's queued records must carry the signature made
                // with her hardware key at capture time.
                $bioState = \App\Support\BiometricGuard::state($midwife);
                if ($bioState === 'invalidated') {
                    $results[] = ['client_uuid' => $uuid, 'status' => 'rejected', 'message' => 'ثبت‌نام اثر انگشت باطل شده است — به دفتر مراجعه کنید.'];
                    continue;
                }
                if ($bioState === 'active') {
                    $payload = \App\Support\BiometricGuard::offlinePayload(
                        $uuid, (string) $record['device_time'], (float) $record['lat'], (float) $record['lng']
                    );
                    if (! \App\Support\BiometricGuard::verifyPayload($midwife, $payload, $record['bio_signature'] ?? null)) {
                        $results[] = ['client_uuid' => $uuid, 'status' => 'rejected', 'message' => 'امضای اثر انگشت آفلاین نامعتبر است.'];
                        continue;
                    }
                }

                // Offline CHECK-OUT (two-time attendance): attaches to the
                // day's existing check-in; the window is judged by the
                // device time, exactly like offline check-in.
                if (($record['kind'] ?? 'checkin') === 'checkout') {
                    if (! $existingToday) {
                        $results[] = ['client_uuid' => $uuid, 'status' => 'rejected', 'message' => 'برای آن روز حضور ثبت نشده بود.'];
                        continue;
                    }
                    if ($existingToday->checkout_at || $existingToday->checkout_client_uuid === $uuid) {
                        $results[] = ['client_uuid' => $uuid, 'status' => 'duplicate', 'message' => 'خروج آن روز قبلاً ثبت شده بود.'];
                        continue;
                    }
                    $winStart = AttendanceService::checkoutStart()->setDate(
                        (int) $kabulTime->year, (int) $kabulTime->month, (int) $kabulTime->day
                    );
                    $winEnd = AttendanceService::checkoutEnd()->setDate(
                        (int) $kabulTime->year, (int) $kabulTime->month, (int) $kabulTime->day
                    );
                    if ($kabulTime->lessThan($winStart) || $kabulTime->greaterThan($winEnd)) {
                        $results[] = ['client_uuid' => $uuid, 'status' => 'rejected', 'message' => 'خروج آفلاین بیرون از بازۀ خروج بود.'];
                        continue;
                    }
                    $existingToday->update([
                        'checkout_at' => $kabulTime,
                        'checkout_lat' => (float) $record['lat'],
                        'checkout_lng' => (float) $record['lng'],
                        'checkout_distance_m' => $distance,
                        'checkout_gps_accuracy_m' => $accuracy,
                        'checkout_offline' => true,
                        'checkout_client_uuid' => $uuid,
                    ]);
                    $results[] = [
                        'client_uuid' => $uuid,
                        'status' => 'synced',
                        'message' => 'خروج آفلاین همگام شد.',
                        'attendance' => AttendanceController::attendancePayload($existingToday->fresh(), $cluster),
                    ];
                    continue;
                }

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
