<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * App-wide display timestamps: 12-hour clock (AM/PM) in the configured timezone.
 */
final class AppTime
{
    public const TIME = 'g:i A';

    public const TIME_SEC = 'g:i:s A';

    public const DATETIME = 'M j, Y g:i A';

    public const DATETIME_SEC = 'M j, Y g:i:s A';

    public const CHART = 'g:i A';

    public const CHART_SEC = 'g:i:s A';

    public const CHART_DAY = 'M j g:i A';

    public static function timezone(): string
    {
        try {
            if (function_exists('app') && app()->bound(\Modules\Settings\Services\SettingService::class)) {
                $tz = app(\Modules\Settings\Services\SettingService::class)->get('app_timezone');
                if (is_string($tz) && $tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
                    return $tz;
                }
            }
        } catch (Throwable) {
            // fall through
        }

        $config = config('app.timezone');

        return is_string($config) && $config !== '' ? $config : 'UTC';
    }

    public static function format(mixed $value, string $pattern = self::DATETIME): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            if ($value instanceof CarbonInterface) {
                $dt = $value->copy()->timezone(self::timezone());
            } elseif ($value instanceof DateTimeInterface) {
                $dt = Carbon::instance($value)->timezone(self::timezone());
            } else {
                $dt = Carbon::parse((string) $value)->timezone(self::timezone());
            }
        } catch (Throwable) {
            return is_string($value) ? $value : '—';
        }

        return $dt->format($pattern);
    }

    public static function timeOfDay(mixed $value, bool $withSeconds = false): string
    {
        return self::format($value, $withSeconds ? self::TIME_SEC : self::TIME);
    }

    public static function chartLabel(mixed $value, bool $withSeconds = false): string
    {
        return self::format($value, $withSeconds ? self::CHART_SEC : self::CHART);
    }
}
