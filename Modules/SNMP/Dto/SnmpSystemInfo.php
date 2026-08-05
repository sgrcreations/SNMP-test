<?php

namespace Modules\SNMP\Dto;

readonly class SnmpSystemInfo
{
    public function __construct(
        public ?string $hostname = null,
        public ?string $description = null,
        public ?string $uptime = null,
        public ?string $location = null,
        public ?string $contact = null,
    ) {}
}
