<?php

namespace Modules\Dashboard\Controllers;

use Illuminate\View\View;
use Modules\Dashboard\Services\DashboardService;

class DashboardController
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('dashboard.view'), 403);

        return view('dashboard::index', $this->dashboard->overview());
    }
}
