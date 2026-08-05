<?php

namespace Modules\Settings\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Devices\Models\Device;
use RuntimeException;

/**
 * Server-side HTTP client for the on-prem Go snmp-agent.
 * Never call the agent from the browser; always proxy through Laravel.
 */
class SnmpAgentClient
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        return $this->request('get', '/healthz', auth: false);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateStatus(): array
    {
        return $this->request('get', '/v1/updates/status');
    }

    /**
     * @return array<string, mixed>
     */
    public function checkForUpdates(): array
    {
        return $this->request('post', '/v1/updates/check');
    }

    /**
     * @return array<string, mixed>
     */
    public function applyUpdate(): array
    {
        return $this->request('post', '/v1/updates/apply');
    }

    /**
     * Persist channel URL on the agent (used after publishing a release from Laravel).
     *
     * @return array<string, mixed>
     */
    public function setUpdateChannel(string $channelUrl): array
    {
        return $this->request('post', '/v1/updates/channel', body: [
            'channel_url' => $channelUrl,
            'enabled' => true,
        ]);
    }

    /**
     * Create or update a device on the agent using Laravel device id as external_id.
     *
     * @return array<string, mixed>
     */
    public function upsertDevice(Device $device): array
    {
        return $this->request('post', '/v1/devices', body: $this->devicePayload($device));
    }

    /**
     * Sync device to agent then run SNMP test on the agent host.
     *
     * @return array{connected: bool, message: string, system: ?array<string, mixed>, interfaces_count: int}
     */
    public function testDevice(Device $device): array
    {
        $upserted = $this->upsertDevice($device);
        $agentId = (int) ($upserted['id'] ?? 0);
        if ($agentId < 1) {
            throw new RuntimeException('Agent upsert did not return a device id.');
        }

        /** @var array<string, mixed> $raw */
        $raw = $this->request('post', '/v1/devices/'.$agentId.'/test');

        $system = null;
        if (is_array($raw['system'] ?? null)) {
            /** @var array<string, mixed> $sys */
            $sys = $raw['system'];
            $system = [
                'hostname' => (string) ($sys['name'] ?? $sys['hostname'] ?? ''),
                'description' => (string) ($sys['description'] ?? ''),
                'uptime' => (int) ($sys['uptime'] ?? 0),
                'location' => (string) ($sys['location'] ?? ''),
                'contact' => (string) ($sys['contact'] ?? ''),
            ];
        }

        return [
            'connected' => (bool) ($raw['connected'] ?? false),
            'message' => (string) ($raw['message'] ?? 'Unknown agent response'),
            'system' => $system,
            'interfaces_count' => (int) ($raw['interfaces_count'] ?? 0),
        ];
    }

    /**
     * Poll on the agent and return a Laravel-shaped traffic snapshot.
     *
     * @return array{
     *   cpu: ?float,
     *   memory: ?float,
     *   temperature: null,
     *   rx_bytes: string,
     *   tx_bytes: string,
     *   uptime: ?string,
     *   hostname: ?string,
     *   location: ?string,
     *   description: ?string,
     *   interfaces: list<array<string, mixed>>,
     *   vlans?: list<array{vlan_id: int, name?: string, status?: string, member_ports?: int}>
     * }
     */
    public function pollDeviceSnapshot(Device $device): array
    {
        $upserted = $this->upsertDevice($device);
        $agentId = (int) ($upserted['id'] ?? 0);
        if ($agentId < 1) {
            throw new RuntimeException('Agent upsert did not return a device id.');
        }

        /** @var array<string, mixed> $polled */
        $polled = $this->request('post', '/v1/devices/'.$agentId.'/poll', timeout: 90);
        if (empty($polled['ok'])) {
            throw new RuntimeException((string) ($polled['error'] ?? 'Agent poll failed'));
        }

        /** @var array<string, mixed> $agentDevice */
        $agentDevice = is_array($polled['device'] ?? null) ? $polled['device'] : [];
        if (($agentDevice['reachability'] ?? '') === 'offline') {
            throw new RuntimeException('Agent reported device offline (SNMP failed on agent host)');
        }

        /** @var array<string, mixed> $ifacePayload */
        $ifacePayload = $this->request('get', '/v1/devices/'.$agentId.'/interfaces');
        $rows = is_array($ifacePayload['data'] ?? null) ? $ifacePayload['data'] : [];

        $interfaces = [];
        $rxTotal = '0';
        $txTotal = '0';
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rx = (string) ($row['rx_bytes'] ?? 0);
            $tx = (string) ($row['tx_bytes'] ?? 0);
            $rxTotal = $this->bcAdd($rxTotal, $rx);
            $txTotal = $this->bcAdd($txTotal, $tx);
            $interfaces[] = [
                'if_index' => (int) ($row['if_index'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'description' => (string) ($row['description'] ?? $row['alias'] ?? ''),
                'oper_status' => (string) ($row['oper_status'] ?? 'unknown'),
                'speed' => (int) ($row['speed'] ?? 0),
                'rx_bytes' => $rx,
                'tx_bytes' => $tx,
                'errors' => (int) ($row['errors'] ?? 0),
            ];
        }

        $vlans = [];
        if (is_array($polled['vlans'] ?? null)) {
            foreach ($polled['vlans'] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $vlans[] = [
                    'vlan_id' => (int) ($row['vlan_id'] ?? 0),
                    'name' => isset($row['name']) ? (string) $row['name'] : null,
                    'status' => (string) ($row['status'] ?? 'active'),
                    'member_ports' => (int) ($row['member_ports'] ?? 0),
                ];
            }
        }

        $uptime = $agentDevice['sys_uptime'] ?? null;

        return [
            'cpu' => isset($agentDevice['last_cpu']) ? (float) $agentDevice['last_cpu'] : null,
            'memory' => isset($agentDevice['last_memory']) ? (float) $agentDevice['last_memory'] : null,
            'temperature' => isset($agentDevice['last_temperature']) ? (float) $agentDevice['last_temperature'] : null,
            'rx_bytes' => $rxTotal,
            'tx_bytes' => $txTotal,
            'uptime' => $uptime !== null ? (string) $uptime : null,
            'hostname' => isset($agentDevice['hostname']) ? (string) $agentDevice['hostname'] : null,
            'location' => isset($agentDevice['location']) ? (string) $agentDevice['location'] : null,
            'description' => isset($agentDevice['description']) ? (string) $agentDevice['description'] : null,
            'interfaces' => $interfaces,
            'vlans' => $vlans,
        ];
    }

    public function deleteDeviceByExternalId(int|string $externalId): void
    {
        $this->request('delete', '/v1/devices/by-external/'.$externalId, expectJson: false);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDevices(): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->request('get', '/v1/devices');
        $data = $payload['data'] ?? [];

        return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
    }

    /**
     * Hot metrics from the agent (system of record when agent is configured).
     *
     * @return list<array<string, mixed>>
     */
    public function deviceMetrics(Device $device, ?string $from = null, ?string $to = null, int $limit = 2000): array
    {
        $query = http_build_query(array_filter([
            'from' => $from,
            'to' => $to,
            'limit' => $limit,
        ], static fn ($v) => $v !== null && $v !== ''));

        $path = '/v1/devices/by-external/'.$device->id.'/metrics'.($query !== '' ? '?'.$query : '');

        /** @var array<string, mixed> $payload */
        $payload = $this->request('get', $path);
        $data = $payload['data'] ?? [];

        return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
    }

    public function configured(): bool
    {
        return filled($this->baseUrl()) && filled($this->apiKey());
    }

    /**
     * @return array<string, mixed>
     */
    private function devicePayload(Device $device): array
    {
        $payload = [
            'external_id' => (string) $device->id,
            'name' => $device->name,
            'vendor' => $this->enumValue($device->vendor),
            'device_type' => $this->enumValue($device->device_type),
            'model' => $device->model ?? '',
            'hostname' => $device->hostname ?? '',
            'ip_address' => $device->ip_address,
            'port' => (int) $device->port,
            'snmp_version' => $this->enumValue($device->snmp_version) ?: 'v2c',
            'username' => $device->username ?? '',
            'auth_protocol' => $this->enumValue($device->auth_protocol),
            'priv_protocol' => $this->enumValue($device->priv_protocol),
            'location' => $device->location ?? '',
            'area' => $device->area ?? '',
            'description' => $device->description ?? '',
            'polling_interval' => (int) $device->polling_interval,
            'status' => $this->enumValue($device->status) ?: 'active',
        ];

        // Encrypted casts decrypt on attribute access.
        if (filled($device->community)) {
            $payload['community'] = $device->community;
        }
        if (filled($device->auth_password)) {
            $payload['auth_password'] = $device->auth_password;
        }
        if (filled($device->priv_password)) {
            $payload['priv_password'] = $device->priv_password;
        }

        return $payload;
    }

    private function enumValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_object($value) && property_exists($value, 'value')) {
            return (string) $value->value;
        }

        return (string) $value;
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    private function request(
        string $method,
        string $path,
        bool $auth = true,
        ?array $body = null,
        bool $expectJson = true,
        int $timeout = 30,
    ): array {
        if (! $this->configured()) {
            throw new RuntimeException('Configure snmp_agent_url and snmp_agent_api_key in Settings first.');
        }

        try {
            $pending = $this->client($auth, $timeout);
            $url = $this->url($path);
            $response = match ($method) {
                'get' => $pending->get($url),
                'post' => $pending->asJson()->post($url, $body ?? []),
                'put' => $pending->asJson()->put($url, $body ?? []),
                'delete' => $pending->delete($url),
                default => throw new RuntimeException("Unsupported method {$method}"),
            };
        } catch (ConnectionException $e) {
            throw new RuntimeException('Cannot reach snmp-agent: '.$e->getMessage(), 0, $e);
        }

        return $this->decode($response, $expectJson);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response, bool $expectJson): array
    {
        if ($response->status() === 204) {
            return [];
        }

        if ($response->failed()) {
            $message = $response->json('error') ?? $response->body() ?: 'HTTP '.$response->status();
            throw new RuntimeException((string) $message, $response->status());
        }

        if (! $expectJson) {
            return [];
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return $json;
    }

    private function client(bool $auth, int $timeout = 30): PendingRequest
    {
        $pending = Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders(['User-Agent' => 'SGR-SNMP-Monitor/1.0']);

        if ($auth) {
            $pending = $pending->withToken((string) $this->apiKey());
        }

        return $pending;
    }

    private function bcAdd(string $a, string $b): string
    {
        if (function_exists('bcadd')) {
            return bcadd($a, $b, 0);
        }

        return (string) ((int) $a + (int) $b);
    }

    private function url(string $path): string
    {
        return rtrim((string) $this->baseUrl(), '/').'/'.ltrim($path, '/');
    }

    private function baseUrl(): mixed
    {
        return $this->settings->get('snmp_agent_url');
    }

    private function apiKey(): mixed
    {
        return $this->settings->get('snmp_agent_api_key');
    }
}
