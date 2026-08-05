<?php

namespace Modules\Settings\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Authentication\Services\AuditLogService;
use Modules\Devices\Services\DeviceService;
use Modules\Settings\Requests\PublishAgentReleaseRequest;
use Modules\Settings\Services\AgentReleasePublisher;
use Modules\Settings\Services\SnmpAgentClient;
use Throwable;

class AgentUpdateController
{
    public function __construct(
        private readonly SnmpAgentClient $agent,
        private readonly AuditLogService $auditLogs,
        private readonly AgentReleasePublisher $releases,
    ) {}

    public function show(): View
    {
        abort_unless(auth()->user()?->can('settings.view'), 403);

        $status = null;
        $health = null;
        $error = null;

        if ($this->agent->configured()) {
            try {
                $health = $this->agent->health();
                $status = $this->agent->updateStatus();
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $latest = $this->releases->latest('linux', 'amd64');
        $channelUrl = $latest['manifest_url'] ?? url('/updates/snmp-agent/linux-amd64/manifest.json');

        return view('settings::agent', [
            'configured' => $this->agent->configured(),
            'health' => $health,
            'status' => $status,
            'error' => $error,
            'latestRelease' => $latest,
            'channelUrl' => $channelUrl,
            'canPublish' => $this->releases->canPublish(),
        ]);
    }

    public function publish(PublishAgentReleaseRequest $request): RedirectResponse
    {
        try {
            $result = $this->releases->publish(
                binary: $request->file('binary'),
                version: (string) $request->validated('version'),
                notes: (string) ($request->validated('notes') ?? ''),
                os: (string) ($request->validated('os') ?? 'linux'),
                arch: (string) ($request->validated('arch') ?? 'amd64'),
            );

            $this->auditLogs->log(
                event: 'agent.release_published',
                newValues: $result,
                description: 'Published snmp-agent release '.$result['version'],
            );

            // Point the on-prem agent at this Laravel channel when connected.
            if ($this->agent->configured()) {
                try {
                    $this->agent->setUpdateChannel($result['manifest_url']);
                } catch (Throwable $e) {
                    return redirect()
                        ->route('settings.agent')
                        ->with('success', 'Published '.$result['version'].'. Set agent channel manually: '.$result['manifest_url'])
                        ->with('error', 'Could not push channel URL to agent: '.$e->getMessage());
                }
            }

            return redirect()
                ->route('settings.agent')
                ->with('success', 'Published '.$result['version'].'. Use Check for updates → Apply update on the agent.');
        } catch (Throwable $e) {
            return redirect()
                ->route('settings.agent')
                ->with('error', $e->getMessage());
        }
    }

    public function pushChannel(): RedirectResponse
    {
        abort_unless(auth()->user()?->can('settings.update'), 403);

        $latest = $this->releases->latest('linux', 'amd64');
        $url = $latest['manifest_url'] ?? url('/updates/snmp-agent/linux-amd64/manifest.json');

        try {
            $this->agent->setUpdateChannel($url);

            return redirect()
                ->route('settings.agent')
                ->with('success', 'Agent channel URL set to '.$url);
        } catch (Throwable $e) {
            return redirect()
                ->route('settings.agent')
                ->with('error', $e->getMessage());
        }
    }

    public function check(): RedirectResponse
    {
        abort_unless(auth()->user()?->can('settings.update'), 403);

        try {
            $result = $this->agent->checkForUpdates();
            $this->auditLogs->log(
                event: 'agent.update_check',
                newValues: $result,
                description: 'Checked snmp-agent for updates.',
            );

            if (! empty($result['update_available'])) {
                return redirect()
                    ->route('settings.agent')
                    ->with('success', 'Update available: '.$result['latest_version'].' (current '.$result['current_version'].').')
                    ->with('check_result', $result);
            }

            return redirect()
                ->route('settings.agent')
                ->with('success', 'Agent is up to date ('.$result['current_version'].').')
                ->with('check_result', $result);
        } catch (Throwable $e) {
            return redirect()
                ->route('settings.agent')
                ->with('error', $e->getMessage());
        }
    }

    public function apply(): RedirectResponse
    {
        abort_unless(auth()->user()?->can('settings.update'), 403);

        try {
            $result = $this->agent->applyUpdate();
            $this->auditLogs->log(
                event: 'agent.update_apply',
                newValues: $result,
                description: 'Applied snmp-agent update.',
            );

            return redirect()
                ->route('settings.agent')
                ->with('success', $result['message'] ?? 'Update applied. Agent is restarting (≈2–5s downtime). Refresh in a few seconds.');
        } catch (Throwable $e) {
            return redirect()
                ->route('settings.agent')
                ->with('error', $e->getMessage());
        }
    }

    public function syncDevices(DeviceService $devices): RedirectResponse
    {
        abort_unless(auth()->user()?->can('settings.update'), 403);

        $result = $devices->syncAllToAgent();

        if ($result['skipped']) {
            return redirect()
                ->route('settings.agent')
                ->with('error', $result['errors'][0] ?? 'Agent not configured.');
        }

        $this->auditLogs->log(
            event: 'agent.devices_sync',
            newValues: [
                'synced' => $result['synced'],
                'failed' => $result['failed'],
            ],
            description: 'Pushed Laravel devices to snmp-agent.',
        );

        $msg = "Synced {$result['synced']} device(s) to agent.";
        if ($result['failed'] > 0) {
            $msg .= " {$result['failed']} failed.";

            return redirect()->route('settings.agent')->with('error', $msg);
        }

        return redirect()->route('settings.agent')->with('success', $msg);
    }
}
