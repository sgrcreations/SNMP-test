<?php

use App\Support\AppTime;

if (! function_exists('app_time')) {
    /**
     * Format a timestamp for UI display (12-hour AM/PM in app timezone).
     */
    function app_time(mixed $value, string $pattern = AppTime::DATETIME): string
    {
        return AppTime::format($value, $pattern);
    }
}
