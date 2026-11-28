<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricKey extends Model
{
    protected $fillable = [
        'midwife_id', 'public_key', 'status', 'enrolled_at', 'invalidated_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'invalidated_at' => 'datetime',
    ];

    public function midwife(): BelongsTo
    {
        return $this->belongsTo(Midwife::class);
    }
}
