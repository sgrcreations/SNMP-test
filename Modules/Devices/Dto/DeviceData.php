<?php

namespace Modules\Devices\Dto;

readonly class DeviceData
{
    public function __construct(
        public string $name,
        public string $vendor,
        public ?string $model,
        public ?string $hostname,
        public string $ipAddress,
        public string $snmpVersion,
        public ?string $community,
        public ?string $username,
        public ?string $authProtocol,
        public ?string $authPassword,
        public ?string $privProtocol,
        public ?string $privPassword,
        public int $port,
        public ?string $location,
        public ?string $description,
        public int $pollingInterval,
        public string $status,
        public ?int $createdBy = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            vendor: $data['vendor'],
            model: $data['model'] ?? null,
            hostname: $data['hostname'] ?? null,
            ipAddress: $data['ip_address'],
            snmpVersion: $data['snmp_version'],
            community: $data['community'] ?? null,
            username: $data['username'] ?? null,
            authProtocol: $data['auth_protocol'] ?? null,
            authPassword: $data['auth_password'] ?? null,
            privProtocol: $data['priv_protocol'] ?? null,
            privPassword: $data['priv_password'] ?? null,
            port: (int) ($data['port'] ?? 161),
            location: $data['location'] ?? null,
            description: $data['description'] ?? null,
            pollingInterval: (int) ($data['polling_interval'] ?? 60),
            status: $data['status'] ?? 'active',
            createdBy: isset($data['created_by']) ? (int) $data['created_by'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'vendor' => $this->vendor,
            'model' => $this->model,
            'hostname' => $this->hostname,
            'ip_address' => $this->ipAddress,
            'snmp_version' => $this->snmpVersion,
            'community' => $this->community,
            'username' => $this->username,
            'auth_protocol' => $this->authProtocol,
            'auth_password' => $this->authPassword,
            'priv_protocol' => $this->privProtocol,
            'priv_password' => $this->privPassword,
            'port' => $this->port,
            'location' => $this->location,
            'description' => $this->description,
            'polling_interval' => $this->pollingInterval,
            'status' => $this->status,
            'created_by' => $this->createdBy,
        ], static fn ($value) => $value !== null);
    }
}
