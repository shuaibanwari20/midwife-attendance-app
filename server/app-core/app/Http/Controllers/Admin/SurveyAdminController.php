<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Midwife;
use App\Models\SurveyResponse;
use App\Support\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurveyAdminController extends Controller
{
    /** Feature 49 — monthly survey results: averages, problem tallies, comments. */
    public function index(Request $request): View
    {
        $monthParam = (string) $request->query('month', '');
        $month = preg_match('/^\d{4}-\d{2}$/', $monthParam)
            ? $monthParam
            : AttendanceService::now()->format('Y-m');

        $responses = SurveyResponse::with('midwife.cluster')
            ->where('month', $month)
            ->latest()
            ->get();

        $problemCounts = [];
        foreach (SurveyResponse::PROBLEM_LABELS as $key => $label) {
            $problemCounts[$key] = ['label' => $label, 'count' => 0];
        }
        foreach ($responses as $r) {
            foreach ($r->problems ?? [] as $p) {
                if (isset($problemCounts[$p])) {
                    $problemCounts[$p]['count']++;
                }
            }
        }

        return view('admin.surveys', [
            'month' => $month,
            'monthDate' => Carbon::createFromFormat('Y-m', $month, 'Asia/Kabul'),
            'responses' => $responses,
            'totalMidwives' => Midwife::count(),
            'avgSatisfaction' => $responses->count() ? round($responses->avg('satisfaction'), 1) : null,
            'problemCounts' => $problemCounts,
        ]);
    }
}
