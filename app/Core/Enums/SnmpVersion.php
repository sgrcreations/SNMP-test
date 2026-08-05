<?php

namespace App\Core\Enums;

enum SnmpVersion: string
{
    case V2c = 'v2c';
    case V3 = 'v3';

    public function label(): string
    {
        return match ($this) {
            self::V2c => 'SNMP v2c',
            self::V3 => 'SNMP v3',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
