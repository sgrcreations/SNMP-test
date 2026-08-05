<?php

namespace Modules\Settings\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Settings\Requests\UpdateSettingsRequest;
use Modules\Settings\Services\SettingService;

class SettingController
{
    public function __construct(
        private readonly SettingService $settings,
    ) {}

    public function edit(): View
    {
        abort_unless(auth()->user()?->can('settings.view'), 403);

        return view('settings::edit', [
            'groups' => $this->settings->allGrouped(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->settings->updateMany($request->validated('settings'));

        return redirect()
            ->route('settings.edit')
            ->with('success', 'Settings updated successfully.');
    }
}
