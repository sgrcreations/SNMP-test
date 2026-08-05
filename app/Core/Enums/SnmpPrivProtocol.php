<?php

namespace App\Core\Enums;

enum SnmpPrivProtocol: string
{
    case DES = 'des';
    case AES = 'aes';
    case AES192 = 'aes192';
    case AES256 = 'aes256';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::DES => 'DES',
            self::AES => 'AES',
            self::AES192 => 'AES-192',
            self::AES256 => 'AES-256',
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
