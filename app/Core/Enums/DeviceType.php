<?php

namespace App\Core\Enums;

enum DeviceType: string
{
    case Router = 'router';
    case Switch = 'switch';
    case Olt = 'olt';
    case Generic = 'generic';

    public function label(): string
    {
        return match ($this) {
            self::Router => 'Router',
            self::Switch => 'Switch',
            self::Olt => 'OLT',
            self::Generic => 'Generic',
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

    public static function detectFromDescription(?string $description, ?string $vendor = null): self
    {
        $hay = strtolower(($description ?? '').' '.($vendor ?? ''));

        if (str_contains($hay, 'ma580') || str_contains($hay, 'gpon') || str_contains($hay, 'olt') || str_contains($hay, 'optix')) {
            return self::Olt;
        }

        if (str_contains($hay, 's5700') || str_contains($hay, 's5720') || str_contains($hay, 's573') || str_contains($hay, 's6700') || str_contains($hay, 'switch') || str_contains($hay, 'quidway')) {
            return self::Switch;
        }

        if (str_contains($hay, 'routeros') || str_contains($hay, 'ccr') || str_contains($hay, 'hex') || str_contains($hay, 'router') || str_contains($hay, 'mikrotik')) {
            return self::Router;
        }

        return self::Generic;
    }
}
