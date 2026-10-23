<?php

return [
    /*
    | A student below this attendance percentage (once enough sessions have been held)
    | gets a low-attendance warning when a session closes.
    */
    'low_threshold' => (int) env('ATTENDANCE_LOW_THRESHOLD', 75),

    'min_sessions_for_alert' => (int) env('ATTENDANCE_MIN_SESSIONS_FOR_ALERT', 4),

    'alert_cooldown_days' => (int) env('ATTENDANCE_ALERT_COOLDOWN_DAYS', 7),
];
