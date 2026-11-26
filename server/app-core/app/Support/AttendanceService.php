<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\Cluster;
use App\Models\Leave;
use App\Models\Midwife;
use App\Models\Setting;
use Carbon\Carbon;

/**
 * Central attendance rules. Server time is always Asia/Kabul.
 *  - Arrival <= work_start       => on_time   (حاضر به‌وقت)
 *  - Arrival  work_start..work_end => late    (دیرکرد) with minutes
 *  - After work_end              => check-in LOCKED — the day is over
 *  - One check-in per day
 *
 * work_start / work_end are set by the admin in the panel settings
 * (settings table); config/midwife.php only holds the defaults.
 */
class AttendanceService
{
    public static function timezone(): string
    {
        return config('midwife.timezone', 'Asia/Kabul');
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    public static function workStart(): Carbon
    {
        return Carbon::parse(
            Setting::get('work_start', config('midwife.work_start', '08:00')),
            self::timezone()
        );
    }

    /** End of the working day: check-in locks after this time. */
    public static function workEnd(): Carbon
    {
        return Carbon::parse(
            Setting::get('work_end', config('midwife.work_end', config('midwife.late_until', '08:30'))),
            self::timezone()
        );
    }

    /** Alias of workEnd() — the late window ends when the day ends. */
    public static function lateUntil(): Carbon
    {
        return self::workEnd();
    }

    /**
     * @return array{status: 'on_time'|'late', late_minutes: int}
     */
    public static function statusFor(Carbon $arrival): array
    {
        $workStart = self::workStart()->setDate(
            (int) $arrival->year, (int) $arrival->month, (int) $arrival->day
        );

        if ($arrival->lessThanOrEqualTo($workStart)) {
            return ['status' => 'on_time', 'late_minutes' => 0];
        }

        return [
            'status' => 'late',
            'late_minutes' => (int) round(abs($arrival->diffInMinutes($workStart))),
        ];
    }

    /** Unique tracking code like "MW-8421". */
    public static function generateTrackingCode(): string
    {
        $prefix = config('midwife.tracking_prefix', 'MW');
        do {
            $code = $prefix.'-'.str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        } while (Attendance::where('tracking_code', $code)->exists());

        return $code;
    }

    /**
     * Is the given calendar day covered by an approved leave for this midwife?
     */
    public static function isOnApprovedLeave(Midwife $midwife, Carbon $day): bool
    {
        return Leave::where('midwife_id', $midwife->id)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $day->toDateString())
            ->whereDate('to_date', '>=', $day->toDateString())
            ->exists();
    }

    /**
     * First day the midwife is obligated to attend: her registration day
     * (Asia/Kabul). If the account was created after the day's cutoff,
     * attendance starts the NEXT day — she cannot be absent for a day that
     * was already over when her account was made. Days before this date
     * are never counted as absent (the account did not exist yet).
     *
     * The cutoff is anchored to the DEFAULT late window in config
     * (08:30), NOT the admin-set work_end: a midwife's start date must
     * never move retroactively when the admin later changes the hours.
     */
    public static function firstObligatedDay(Midwife $midwife): Carbon
    {
        $created = $midwife->created_at->copy()->setTimezone(self::timezone());
        $day = $created->copy()->startOfDay();

        $cutoff = Carbon::parse(config('midwife.late_until', '08:30'), self::timezone())
            ->setDate((int) $created->year, (int) $created->month, (int) $created->day);

        if ($created->greaterThan($cutoff)) {
            $day->addDay();
        }

        return $day;
    }

    /** Has the midwife's attendance obligation started by the given day? */
    public static function hasJoinedBy(Midwife $midwife, Carbon $day): bool
    {
        return $day->copy()->startOfDay()->greaterThanOrEqualTo(self::firstObligatedDay($midwife));
    }

    /**
     * Day status for history rendering:
     * on_time | late | absent | leave | friday | future | before_join | pending
     *
     * His rule (2026-11-27): a midwife is counted ABSENT only when the whole
     * check-in window has closed — for today, while now <= work_end she is
     * 'pending' (هنوز نرسیده), never absent. Past days are final.
     */
    public static function dayStatus(Midwife $midwife, Carbon $day, ?Attendance $attendance): string
    {
        $today = self::now()->startOfDay();

        if ($day->greaterThan($today)) {
            return 'future';
        }
        if ($attendance) {
            return $attendance->status; // on_time | late — a real record always wins
        }
        if (! self::hasJoinedBy($midwife, $day)) {
            return 'before_join';
        }
        if ($day->isFriday()) {
            return 'friday';
        }
        if (self::isOnApprovedLeave($midwife, $day)) {
            return 'leave';
        }
        if ($day->isSameDay($today) && self::now()->lessThanOrEqualTo(self::workEnd())) {
            return 'pending';
        }

        return 'absent';
    }
}
