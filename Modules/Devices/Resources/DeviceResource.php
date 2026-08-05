<?php

namespace Modules\Devices\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Modules\Devices\Models\Device */
class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vendor' => $this->vendor?->value,
            'vendor_label' => $this->vendor?->label(),
            'model' => $this->model,
            'hostname' => $this->hostname,
            'ip_address' => $this->ip_address,
            'snmp_version' => $this->snmp_version?->value,
            'username' => $this->username,
            'auth_protocol' => $this->auth_protocol?->value,
            'priv_protocol' => $this->priv_protocol?->value,
            'port' => $this->port,
            'location' => $this->location,
            'description' => $this->description,
            'polling_interval' => $this->polling_interval,
            'status' => $this->status?->value,
            'reachability' => $this->reachability?->value,
            'last_polled_at' => $this->last_polled_at?->toIso8601String(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'endpoint' => $this->displayEndpoint(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
