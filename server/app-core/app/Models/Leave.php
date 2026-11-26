<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    protected $table = 'leaves';

    protected $fillable = [
        'midwife_id', 'from_date', 'to_date', 'reason', 'status',
        'reviewed_by', 'review_note', 'source',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function midwife(): BelongsTo
    {
        return $this->belongsTo(Midwife::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Number of calendar days covered (inclusive). */
    public function durationDays(): int
    {
        return (int) $this->from_date->diffInDays($this->to_date) + 1;
    }
}
