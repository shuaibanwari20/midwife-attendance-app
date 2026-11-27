<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkReportDailyEntry extends Model
{
    protected $fillable = ['midwife_id', 'entry_date', 'payload'];

    protected $casts = [
        'payload' => 'array',
        'entry_date' => 'date',
    ];

    public function midwife(): BelongsTo
    {
        return $this->belongsTo(Midwife::class);
    }

    /** Sum the daily counters of one month into a monthly payload. */
    public static function monthSums(int $midwifeId, string $month): array
    {
        $sums = [];
        $entries = self::where('midwife_id', $midwifeId)
            ->where('entry_date', 'like', $month . '%')
            ->get();
        foreach ($entries as $entry) {
            foreach ((array) $entry->payload as $key => $value) {
                if (is_numeric($value)) {
                    $sums[$key] = ($sums[$key] ?? 0) + (int) $value;
                }
            }
        }

        return $sums;
    }
}
