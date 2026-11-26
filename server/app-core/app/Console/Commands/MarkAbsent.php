<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Midwife;
use App\Support\AttendanceService;
use Illuminate\Console\Command;

/**
 * After the late window (08:30 Kabul), any active midwife without a check-in
 * and without approved leave (Fridays excluded) is treated as غایب for the day.
 *
 * Absence is derived (no row is written) — the dashboard/history compute it on
 * the fly, this command only reports and can be used for daily monitoring.
 */
class MarkAbsent extends Command
{
    protected $signature = 'attendance:mark-absent {--date= : Override date (Y-m-d, Kabul)}';

    protected $description = 'Report midwives absent for the day (no check-in by LATE_UNTIL, no approved leave, Fridays excluded)';

    public function handle(): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'), AttendanceService::timezone())->startOfDay()
            : AttendanceService::now()->startOfDay();

        if ($date->isFriday()) {
            $this->info('Friday — workday skipped (روز رخصتی).');

            return self::SUCCESS;
        }

        // For today, absence is only final once the work day has ended
        // (check-in locks at work_end, set by the admin).
        if ($date->isSameDay(AttendanceService::now()->startOfDay())
            && AttendanceService::now()->lessThanOrEqualTo(AttendanceService::workEnd())) {
            $this->info('Work day still open — check-in window has not closed yet.');

            return self::SUCCESS;
        }

        $checkedIds = Attendance::whereDate('attendance_date', $date->toDateString())
            ->pluck('midwife_id')
            ->all();

        $leaveIds = \App\Models\Leave::where('status', 'approved')
            ->whereDate('from_date', '<=', $date->toDateString())
            ->whereDate('to_date', '>=', $date->toDateString())
            ->pluck('midwife_id')
            ->all();

        $absent = Midwife::where('active', true)
            ->whereNotIn('id', $checkedIds)
            ->whereNotIn('id', $leaveIds)
            ->get()
            // Accounts created after this day's cutoff start attending the
            // next day — they are not absent for a day that predates them.
            ->filter(fn ($midwife) => AttendanceService::hasJoinedBy($midwife, $date))
            ->values();

        foreach ($absent as $midwife) {
            $this->line(sprintf('غایب: %s (%s)', $midwife->full_name, $midwife->code));
        }

        $this->info('Absent count for '.$date->toDateString().': '.$absent->count());

        return self::SUCCESS;
    }
}
