<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'midwife_id', 'cluster_id', 'attendance_date', 'checked_at_server',
        'device_time', 'lat', 'lng', 'distance_m', 'gps_accuracy_m',
        'is_mocked', 'status', 'late_minutes', 'offline', 'tracking_code',
        'client_uuid',
        'checkout_at', 'checkout_lat', 'checkout_lng', 'checkout_distance_m',
        'checkout_gps_accuracy_m', 'checkout_offline', 'checkout_client_uuid',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'checked_at_server' => 'datetime',
        'device_time' => 'datetime',
        'lat' => 'float',
        'lng' => 'float',
        'distance_m' => 'float',
        'gps_accuracy_m' => 'float',
        'is_mocked' => 'boolean',
        'late_minutes' => 'integer',
        'offline' => 'boolean',
        'checkout_at' => 'datetime',
        'checkout_lat' => 'float',
        'checkout_lng' => 'float',
        'checkout_distance_m' => 'float',
        'checkout_gps_accuracy_m' => 'float',
        'checkout_offline' => 'boolean',
    ];

    public function midwife(): BelongsTo
    {
        return $this->belongsTo(Midwife::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }
}
