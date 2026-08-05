<?php

namespace App\Core\Enums;

enum DeviceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Online = 'online';
    case Offline = 'offline';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::Unknown => 'Unknown',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active, self::Online => 'emerald',
            self::Inactive => 'slate',
            self::Offline => 'rose',
            self::Unknown => 'amber',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach ([self::Active, self::Inactive] as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
