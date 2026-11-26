<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use App\Support\AttendanceService;
use App\Support\DariDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SurveyController extends Controller
{
    /** Feature 49 — the current month's survey state for the signed-in midwife. */
    public function current(Request $request): JsonResponse
    {
        $month = AttendanceService::now()->format('Y-m');
        $response = SurveyResponse::where('midwife_id', $request->user()->id)
            ->where('month', $month)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'month_label' => DariDate::monthYear(AttendanceService::now()),
                'answered' => $response !== null,
                'response' => $response ? [
                    'satisfaction' => $response->satisfaction,
                    'problems' => $response->problems ?? [],
                    'comment' => $response->comment,
                ] : null,
            ],
        ]);
    }

    /** Answer (or update) this month's survey. */
    public function respond(Request $request): JsonResponse
    {
        $data = $request->validate([
            'satisfaction' => ['required', 'integer', 'min:1', 'max:5'],
            'problems' => ['nullable', 'array', 'max:6'],
            'problems.*' => [Rule::in(array_keys(SurveyResponse::PROBLEM_LABELS))],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'satisfaction.required' => 'لطفاً میزان رضایت خود را انتخاب کنید.',
            'satisfaction.min' => 'رضایت باید بین 1 تا 5 باشد.',
            'satisfaction.max' => 'رضایت باید بین 1 تا 5 باشد.',
        ]);

        $month = AttendanceService::now()->format('Y-m');
        $response = SurveyResponse::updateOrCreate(
            ['midwife_id' => $request->user()->id, 'month' => $month],
            [
                'satisfaction' => $data['satisfaction'],
                'problems' => $data['problems'] ?? [],
                'comment' => $data['comment'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'نظرت ثبت شد — ممنون!',
            'data' => ['id' => $response->id, 'month' => $month],
        ]);
    }
}
