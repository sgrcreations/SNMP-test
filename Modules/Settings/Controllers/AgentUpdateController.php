<?php

namespace Modules\Settings\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Authentication\Services\AuditLogService;
use Modules\Devices\Services\DeviceService;
use Modules\Settings\Services\SnmpAgentClient;
use Throwable;

class AgentUpdateController
{
    public function __construct(
        private readonly SnmpAgentClient $agent,
        private readonly AuditLogService $auditLogs,
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

        return view('settings::agent', [
            'configured' => $this->agent->configured(),
            'health' => $health,
            'status' => $status,
            'error' => $error,
        ]);
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
