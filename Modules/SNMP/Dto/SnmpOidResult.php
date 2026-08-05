<?php

namespace Modules\SNMP\Dto;

readonly class SnmpOidResult
{
    public function __construct(
        public string $oid,
        public string $type,
        public mixed $value,
    ) {}

    /**
     * @return array{oid: string, type: string, value: mixed}
     */
    public function toArray(): array
    {
        return [
            'oid' => $this->oid,
            'type' => $this->type,
            'value' => $this->value,
        ];
    }
}
