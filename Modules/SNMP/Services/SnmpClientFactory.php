<?php

namespace Modules\SNMP\Services;

use App\Core\Enums\SnmpAuthProtocol;
use App\Core\Enums\SnmpPrivProtocol;
use App\Core\Enums\SnmpVersion;
use FreeDSx\Snmp\SnmpClient;
use Modules\Devices\Models\Device;
use Modules\Settings\Services\SettingService;

class SnmpClientFactory
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    public function make(Device $device): SnmpClient
    {
        $timeout = max(1, (int) $this->settings->get('snmp_timeout', 3));
        $retries = max(0, (int) $this->settings->get('snmp_retries', 1));

        $options = [
            'host' => $device->ip_address,
            'port' => $device->port,
            'timeout_connect' => $timeout,
            'timeout_read' => $timeout,
            'udp_retry' => $retries,
        ];

        if ($device->snmp_version === SnmpVersion::V3) {
            $options = array_merge($options, $this->v3Options($device));
        } else {
            $options['version'] = 2;
            $options['community'] = (string) ($device->community ?: 'public');
        }

        return new SnmpClient($options);
    }

    /**
     * @return array<string, mixed>
     */
    private function v3Options(Device $device): array
    {
        $options = [
            'version' => 3,
            'user' => (string) $device->username,
            'use_auth' => false,
            'use_priv' => false,
        ];

        $auth = $device->auth_protocol;
        if ($auth && $auth !== SnmpAuthProtocol::None && filled($device->auth_password)) {
            $options['use_auth'] = true;
            $options['auth_mech'] = $this->mapAuthMech($auth);
            $options['auth_pwd'] = (string) $device->auth_password;
        }

        $priv = $device->priv_protocol;
        if ($priv && $priv !== SnmpPrivProtocol::None && filled($device->priv_password)) {
            $options['use_priv'] = true;
            $options['priv_mech'] = $this->mapPrivMech($priv);
            $options['priv_pwd'] = (string) $device->priv_password;
        }

        return $options;
    }

    private function mapAuthMech(SnmpAuthProtocol $protocol): string
    {
        return match ($protocol) {
            SnmpAuthProtocol::MD5 => 'md5',
            SnmpAuthProtocol::SHA => 'sha1',
            SnmpAuthProtocol::SHA256 => 'sha256',
            SnmpAuthProtocol::SHA512 => 'sha512',
            SnmpAuthProtocol::None => 'md5',
        };
    }

    private function mapPrivMech(SnmpPrivProtocol $protocol): string
    {
        return match ($protocol) {
            SnmpPrivProtocol::DES => 'des',
            SnmpPrivProtocol::AES => 'aes128',
            SnmpPrivProtocol::AES192 => 'aes192',
            SnmpPrivProtocol::AES256 => 'aes256',
            SnmpPrivProtocol::None => 'des',
        };
    }
}
