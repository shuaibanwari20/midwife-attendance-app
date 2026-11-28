<?php

/*
|--------------------------------------------------------------------------
| Attendance rules (server time is always Asia/Kabul)
|--------------------------------------------------------------------------
*/
return [

    // Work day starts at 08:00 Kabul time. Arrival <= 08:00 = on_time (حاضر به‌وقت).
    'work_start' => env('WORK_START', '08:00'),

    // Arrivals after work_start until work_end are "late" (دیرکرد). After work_end
    // check-in is LOCKED — the day is over. Both times are defaults only: the
    // admin overrides them in the panel settings (settings table).
    'late_until' => env('LATE_UNTIL', '08:30'),
    'work_end' => env('WORK_END', env('LATE_UNTIL', '08:30')),

    // Two-time attendance (his order 2026-11-28, option ب): leaving work is
    // ALSO recorded — check-out is possible only inside this window, set by
    // the admin in the panel settings. Defaults only.
    'checkout_start' => env('CHECKOUT_START', '12:30'),
    'checkout_end' => env('CHECKOUT_END', '13:30'),

    // Check-in refused when GPS accuracy is worse than this (meters).
    'max_gps_accuracy' => (int) env('MAX_GPS_ACCURACY', 50),

    // Tracking code prefix, e.g. MW-8421
    'tracking_prefix' => env('TRACKING_PREFIX', 'MW'),

    // Default geofence radius (meters) for clusters
    'default_radius' => (int) env('DEFAULT_GEOFENCE_RADIUS', 150),

    'timezone' => 'Asia/Kabul',

];
