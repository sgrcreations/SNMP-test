<?php

namespace Modules\Api\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Dashboard\Services\DashboardService;

class V1DashboardController
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        $overview = $this->dashboard->overview();

        return response()->json([
            'data' => [
                'stats' => $overview['stats'],
                'polling_enabled' => $overview['polling_enabled'],
            ],
        ]);
    }
}
