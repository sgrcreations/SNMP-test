<?php

namespace Modules\Settings\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Settings\Services\AgentReleasePublisher;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgentUpdateChannelController
{
    public function __construct(
        private readonly AgentReleasePublisher $releases,
    ) {}

    public function manifest(string $channel): BinaryFileResponse
    {
        $this->assertChannel($channel);
        $path = $this->releases->manifestPath($channel);
        abort_unless($path, 404);

        return Response::file($path, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function binary(string $channel, string $filename): BinaryFileResponse
    {
        $this->assertChannel($channel);
        $path = $this->releases->binaryPath($channel, $filename);
        abort_unless($path, 404);

        return Response::file($path, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function assertChannel(string $channel): void
    {
        abort_unless((bool) preg_match('/^[a-z0-9]+-[a-z0-9]+$/', $channel), 404);
    }
}
