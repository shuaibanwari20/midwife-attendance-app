<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AttendanceService;
use App\Support\DariDate;
use Illuminate\Http\JsonResponse;

class ServerTimeController extends Controller
{
    /** GET /api/server-time — authoritative clock (Asia/Kabul). */
    public function index(): JsonResponse
    {
        $now = AttendanceService::now();

        return response()->json([
            'success' => true,
            'data' => [
                'iso' => $now->toIso8601String(),
                'epoch_ms' => (int) ($now->getTimestampMs()),
                'timezone' => AttendanceService::timezone(),
                'date_iso' => $now->toDateString(),
                'date' => DariDate::full($now),
                'time' => DariDate::time24($now),
                'time_display' => DariDate::time($now),
                'weekday' => DariDate::weekday($now),
                'work_start' => AttendanceService::workStart()->format('H:i'),
                'work_end' => AttendanceService::workEnd()->format('H:i'),
                'late_until' => AttendanceService::workEnd()->format('H:i'),
                // Two-time attendance: the check-out window (بازهٔ خروج).
                'checkout_start' => AttendanceService::checkoutStart()->format('H:i'),
                'checkout_end' => AttendanceService::checkoutEnd()->format('H:i'),
                // Feature 30 — newest published app version (update banner).
                'app_update' => [
                    'latest_version_code' => (int) (Setting::get('app_latest_version_code') ?: 1),
                    'url' => (string) (Setting::get('app_update_url') ?: ''),
                    'note' => (string) (Setting::get('app_update_note') ?: ''),
                ],
            ],
        ]);
    }
}
