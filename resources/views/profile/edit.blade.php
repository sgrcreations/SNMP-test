<x-monitor-layout
    title="Profile"
    header="Profile"
    subheader="Manage your account details"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Profile'],
    ]"
>
    <div class="mx-auto max-w-3xl space-y-4">
        <div class="sgr-card p-5 sm:p-7">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="sgr-card p-5 sm:p-7">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="sgr-card p-5 sm:p-7">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-monitor-layout>
