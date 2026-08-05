<?php

namespace Modules\SNMP\Dto;

readonly class SnmpConnectionResult
{
    public function __construct(
        public bool $connected,
        public ?string $message = null,
        public ?SnmpSystemInfo $system = null,
        public int $interfacesCount = 0,
    ) {}
}
