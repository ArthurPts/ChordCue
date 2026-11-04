<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-medium text-ink">
            Pengaturan Profil
        </h1>
    </x-slot>

    <div class="py-24">
        <div class="max-w-content mx-auto px-16 md:px-24 space-y-24">
            <div class="p-24 bg-surface-1 border border-subtle rounded-xl">
                <div class="max-w-dialog">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-24 bg-surface-1 border border-subtle rounded-xl">
                <div class="max-w-dialog">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-24 bg-surface-1 border border-subtle rounded-xl">
                <div class="max-w-dialog">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
