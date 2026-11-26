<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            ],
        ]);
    }
}
