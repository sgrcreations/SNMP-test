<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Agent update signing key (Ed25519 private, base64)
    |--------------------------------------------------------------------------
    |
    | Must match the public key embedded in snmp-agent (internal/updater/pubkey.go).
    | Keep this secret on the Laravel control plane only — never ship to agent hosts.
    |
    */
    'private_key_b64' => env('SNMP_UPDATE_PRIVATE_KEY_B64', ''),

    /*
    |--------------------------------------------------------------------------
    | Storage folder under storage/app
    |--------------------------------------------------------------------------
    */
    'storage_path' => 'updates/snmp-agent',

    /*
    |--------------------------------------------------------------------------
    | Default target when publishing from the UI
    |--------------------------------------------------------------------------
    */
    'default_os' => 'linux',
    'default_arch' => 'amd64',
];
