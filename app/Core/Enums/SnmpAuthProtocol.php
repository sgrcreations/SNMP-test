<?php

namespace App\Core\Enums;

enum SnmpAuthProtocol: string
{
    case MD5 = 'md5';
    case SHA = 'sha';
    case SHA256 = 'sha256';
    case SHA512 = 'sha512';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::MD5 => 'MD5',
            self::SHA => 'SHA',
            self::SHA256 => 'SHA-256',
            self::SHA512 => 'SHA-512',
            self::None => 'None',
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
