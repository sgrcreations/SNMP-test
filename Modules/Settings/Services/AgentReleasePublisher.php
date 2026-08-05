<?php

namespace Modules\Settings\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AgentReleasePublisher
{
    /**
     * @return array{version: string, channel: string, manifest_url: string, binary_url: string, sha256: string}
     */
    public function publish(
        UploadedFile $binary,
        string $version,
        string $notes = '',
        string $os = 'linux',
        string $arch = 'amd64',
        ?string $baseUrl = null,
    ): array {
        $version = ltrim(trim($version), 'vV');
        if ($version === '' || ! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            throw new RuntimeException('Version must be semver like 0.1.1');
        }

        $privateKey = $this->privateKey();
        $os = strtolower($os);
        $arch = strtolower($arch);
        $channel = $os.'-'.$arch;
        $relativeDir = trim((string) config('snmp_updates.storage_path'), '/').'/'.$channel;
        $binaryName = 'snmpd-'.$version;
        $disk = Storage::disk('local');

        $disk->makeDirectory($relativeDir);
        $binaryPath = $relativeDir.'/'.$binaryName;
        $disk->put($binaryPath, file_get_contents($binary->getRealPath()) ?: '');

        $absoluteBinary = $disk->path($binaryPath);
        $sha256 = hash_file('sha256', $absoluteBinary);
        if ($sha256 === false) {
            throw new RuntimeException('Failed to hash uploaded binary.');
        }

        $signature = sodium_crypto_sign_detached(
            strtolower($sha256),
            $privateKey,
        );

        $publicBase = $this->resolvePublicBase($baseUrl);
        $binaryUrl = $publicBase.'/updates/snmp-agent/'.$channel.'/'.$binaryName;
        $manifestUrl = $publicBase.'/updates/snmp-agent/'.$channel.'/manifest.json';
        $manifest = [
            'name' => 'snmpd',
            'version' => $version,
            'channel' => 'stable',
            'os' => $os,
            'arch' => $arch,
            'url' => $binaryUrl,
            'sha256' => $sha256,
            'signature' => base64_encode($signature),
            'released_at' => now()->utc()->toIso8601String(),
            'notes' => $notes,
            'force_update' => false,
        ];

        $disk->put(
            $relativeDir.'/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        // Keep a pointer to latest for UI.
        $disk->put(
            $relativeDir.'/latest.json',
            json_encode([
                'version' => $version,
                'manifest_url' => $manifestUrl,
                'published_at' => now()->utc()->toIso8601String(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        return [
            'version' => $version,
            'channel' => $channel,
            'manifest_url' => $manifestUrl,
            'binary_url' => $binaryUrl,
            'sha256' => $sha256,
        ];
    }

    private function resolvePublicBase(?string $baseUrl): string
    {
        $base = rtrim((string) ($baseUrl ?: config('app.url')), '/');
        if ($base === '' || str_contains($base, 'localhost') || str_contains($base, '127.0.0.1')) {
            throw new RuntimeException(
                'APP_URL is localhost. Fix .env APP_URL=https://isp.sgrcreations.com (then php artisan config:clear), '.
                'or pass --base-url=https://isp.sgrcreations.com'
            );
        }

        return $base;
    }

    /**
     * @return array{version: ?string, manifest_url: ?string, published_at: ?string}|null
     */
    public function latest(string $os = 'linux', string $arch = 'amd64'): ?array
    {
        $channel = strtolower($os).'-'.strtolower($arch);
        $path = trim((string) config('snmp_updates.storage_path'), '/').'/'.$channel.'/latest.json';
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        /** @var array{version?: string, manifest_url?: string, published_at?: string} $data */
        $data = json_decode(Storage::disk('local')->get($path) ?: '[]', true) ?: [];

        return [
            'version' => $data['version'] ?? null,
            'manifest_url' => $data['manifest_url'] ?? url('/updates/snmp-agent/'.$channel.'/manifest.json'),
            'published_at' => $data['published_at'] ?? null,
        ];
    }

    public function manifestPath(string $channel): ?string
    {
        $path = trim((string) config('snmp_updates.storage_path'), '/').'/'.$channel.'/manifest.json';

        return Storage::disk('local')->exists($path) ? Storage::disk('local')->path($path) : null;
    }

    public function binaryPath(string $channel, string $filename): ?string
    {
        if (! preg_match('/^snmpd-[0-9]+\.[0-9]+\.[0-9]+$/', $filename)) {
            return null;
        }
        $path = trim((string) config('snmp_updates.storage_path'), '/').'/'.$channel.'/'.$filename;

        return Storage::disk('local')->exists($path) ? Storage::disk('local')->path($path) : null;
    }

    public function canPublish(): bool
    {
        try {
            $this->privateKey();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function privateKey(): string
    {
        $b64 = trim((string) config('snmp_updates.private_key_b64'));
        if ($b64 === '') {
            throw new RuntimeException('Set SNMP_UPDATE_PRIVATE_KEY_B64 in .env to publish signed agent releases.');
        }

        $raw = base64_decode($b64, true);
        if ($raw === false || strlen($raw) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('SNMP_UPDATE_PRIVATE_KEY_B64 is invalid (need Ed25519 64-byte secret key, base64).');
        }

        return $raw;
    }
}
