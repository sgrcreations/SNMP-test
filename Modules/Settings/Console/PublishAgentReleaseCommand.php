<?php

namespace Modules\Settings\Console;

use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Modules\Settings\Services\AgentReleasePublisher;
use Modules\Settings\Services\SnmpAgentClient;
use Throwable;

class PublishAgentReleaseCommand extends Command
{
    protected $signature = 'agent:publish-release
        {version : Semver like 0.1.3}
        {binary : Path to snmpd-linux-amd64 (or arm64) binary}
        {--arch=amd64 : amd64 or arm64}
        {--notes= : Release notes}
        {--base-url= : Public site URL, e.g. https://isp.sgrcreations.com}
        {--push-channel : Push Laravel channel URL to the configured agent}';

    protected $description = 'Sign and publish an snmp-agent binary to the Laravel update channel (for git/CI deploys).';

    public function handle(AgentReleasePublisher $publisher, SnmpAgentClient $agent): int
    {
        $path = (string) $this->argument('binary');
        if (! is_file($path)) {
            $this->error("Binary not found: {$path}");
            $this->printUsageExample();

            return self::FAILURE;
        }

        $uploaded = new UploadedFile(
            path: $path,
            originalName: basename($path),
            mimeType: 'application/octet-stream',
            error: null,
            test: true,
        );

        try {
            $result = $publisher->publish(
                binary: $uploaded,
                version: (string) $this->argument('version'),
                notes: (string) ($this->option('notes') ?: ''),
                os: 'linux',
                arch: (string) $this->option('arch'),
                baseUrl: $this->option('base-url') ? (string) $this->option('base-url') : null,
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Published '.$result['version']);
        $this->line('Manifest: '.$result['manifest_url']);
        $this->line('Binary:   '.$result['binary_url']);
        $this->line('SHA256:   '.$result['sha256']);

        if ($this->option('push-channel') && $agent->configured()) {
            try {
                $agent->setUpdateChannel($result['manifest_url']);
                $this->info('Agent channel URL updated.');
            } catch (Throwable $e) {
                $this->warn('Published, but could not push channel to agent: '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function printUsageExample(): void
    {
        $this->line('Example:');
        $this->line('  php artisan agent:publish-release 0.1.3 ~/snmpd-linux-amd64 \\');
        $this->line('    --base-url=https://isp.sgrcreations.com --push-channel');
    }
}
