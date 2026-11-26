<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    protected $fillable = ['midwife_id', 'month', 'satisfaction', 'problems', 'comment'];

    protected $casts = [
        'problems' => 'array',
        'satisfaction' => 'integer',
    ];

    /** Problem keys the app may send, with their Dari labels (panel + app share the keys). */
    public const PROBLEM_LABELS = [
        'internet' => 'اینترنت',
        'gps' => 'GPS / موقعیت',
        'phone' => 'گوشی',
        'app' => 'خود اپ',
        'none' => 'هیچ مشکلی نداشتم',
        'other' => 'دیگر',
    ];

    public function midwife(): BelongsTo
    {
        return $this->belongsTo(Midwife::class);
    }
}
