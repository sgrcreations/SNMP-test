<?php

namespace App\Core\Enums;

enum DeviceVendor: string
{
    case Huawei = 'huawei';
    case MikroTik = 'mikrotik';
    case Cisco = 'cisco';
    case Juniper = 'juniper';
    case Fortinet = 'fortinet';
    case Aruba = 'aruba';
    case Ubiquiti = 'ubiquiti';
    case Generic = 'generic';

    public function label(): string
    {
        return match ($this) {
            self::Huawei => 'Huawei',
            self::MikroTik => 'MikroTik',
            self::Cisco => 'Cisco',
            self::Juniper => 'Juniper',
            self::Fortinet => 'Fortinet',
            self::Aruba => 'Aruba',
            self::Ubiquiti => 'Ubiquiti',
            self::Generic => 'Generic SNMP',
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
