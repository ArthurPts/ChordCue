<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl sm:text-2xl font-extrabold text-ink tracking-tight">
            Pengaturan Profil
        </h1>
    </x-slot>

    <div class="py-24 sm:py-32 pb-[88px]">
        <div class="max-w-content mx-auto px-16 md:px-24 space-y-20">
            <div class="p-20 sm:p-24 bg-surface-1 border border-subtle rounded-lg shadow-sm">
                <div class="max-w-dialog">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-20 sm:p-24 bg-surface-1 border border-subtle rounded-lg shadow-sm">
                <div class="max-w-dialog">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-20 sm:p-24 bg-surface-1 border border-subtle rounded-lg shadow-sm">
                <div class="max-w-dialog">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
