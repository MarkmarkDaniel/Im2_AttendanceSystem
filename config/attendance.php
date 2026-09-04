<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Late Grace Period (minutes)
    |--------------------------------------------------------------------------
    |
    | A student is PRESENT if they check in at or before:
    |     class start_time + late_grace_minutes
    | Otherwise they are marked LATE.
    |
    | With 0 minutes and a class starting 8:00 AM:
    |     7:55 AM -> Present
    |     8:00 AM -> Present
    |     8:01 AM -> Late
    |
    */
    'late_grace_minutes' => (int) env('ATTENDANCE_LATE_GRACE_MINUTES', 0),

    /*
    |--------------------------------------------------------------------------
    | QR Token Length
    |--------------------------------------------------------------------------
    */
    'qr_token_length' => 5,

    /*
    |--------------------------------------------------------------------------
    | Minimum Passing Attendance Rate (%)
    |--------------------------------------------------------------------------
    | Used to colour badges and trigger low-attendance warnings.
    */
    'minimum_rate' => (int) env('ATTENDANCE_MIN_RATE', 75),

    /*
    |--------------------------------------------------------------------------
    | Auto-Absent On Close
    |--------------------------------------------------------------------------
    | When TRUE, closing a session marks every enrolled student without a
    | record as Absent.
    */
    'auto_absent_on_close' => true,

        /*
    |--------------------------------------------------------------------------
    | Low Attendance Warning Cooldown (days)
    |--------------------------------------------------------------------------
    | How long to wait before sending the same student the same low-attendance
    | warning again. Without this, a warning would fire on every single scan.
    */
    'warning_cooldown_days' => (int) env('ATTENDANCE_WARNING_COOLDOWN_DAYS', 7),

        /*
    |--------------------------------------------------------------------------
    | QR Token Rotation (seconds)
    |--------------------------------------------------------------------------
    | How long one QR token stays valid. A short window means a screenshot
    | is useless by the time it reaches someone outside the room.
    |
    | Set to 0 to disable rotation (the token then lasts the whole session).
    */
    'qr_rotate_seconds' => (int) env('ATTENDANCE_QR_ROTATE_SECONDS', 15),

    /*
    |--------------------------------------------------------------------------
    | Allowed Network Prefixes
    |--------------------------------------------------------------------------
    | When set, a scan is only accepted from these IP prefixes -- normally
    | the school Wi-Fi. Leave empty to accept scans from anywhere.
    |
    | Example: ATTENDANCE_ALLOWED_IPS=192.168.1.,192.168.2.,10.0.
    */
    'allowed_ip_prefixes' => array_filter(
        explode(',', (string) env('ATTENDANCE_ALLOWED_IPS', ''))
    ),

];
