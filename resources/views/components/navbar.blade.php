<nav x-data="{ open: false }" class="bg-surface-1 border-b border-subtle sticky top-0 z-sticky w-full">
    <!-- Primary Navigation Menu -->
    <div class="max-w-content mx-auto px-16">
        <div class="flex justify-between h-[64px] items-center">
            <div class="flex items-center gap-24 h-full">
                <!-- Brand Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="text-[20px] font-semibold text-ink tracking-tight focus:outline-none">
                        ChordCue
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <div class="hidden md:flex md:items-center h-full">
                    <!-- Home -->
                    @php
                        $isHomeActive = request()->routeIs('home');
                    @endphp
                    <a href="{{ route('home') }}" class="relative flex items-center gap-8 px-14 h-full text-sm font-medium transition-colors {{ $isHomeActive ? 'text-accent' : 'text-ink-secondary hover:text-ink' }}">
                        <x-icon name="home" class="w-[18px] h-[18px] {{ $isHomeActive ? 'text-accent' : 'text-ink-secondary' }}" />
                        <span>Home</span>
                        @if ($isHomeActive)
                            <div class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-accent"></div>
                        @endif
                    </a>

                    <!-- My Songs (Auth Only) -->
                    @auth
                        @php
                            $isMySongsActive = request()->routeIs('my.songs');
                        @endphp
                        <a href="{{ route('my.songs') }}" class="relative flex items-center gap-8 px-14 h-full text-sm font-medium transition-colors {{ $isMySongsActive ? 'text-accent' : 'text-ink-secondary hover:text-ink' }}">
                            <x-icon name="music" class="w-[18px] h-[18px] {{ $isMySongsActive ? 'text-accent' : 'text-ink-secondary' }}" />
                            <span>My Songs</span>
                            @if ($isMySongsActive)
                                <div class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-accent"></div>
                            @endif
                        </a>
                    @endauth

                    <!-- Pustaka Chord -->
                    @php
                        $isChordsActive = request()->routeIs('chords.index');
                    @endphp
                    <a href="{{ route('chords.index') }}" class="relative flex items-center gap-8 px-14 h-full text-sm font-medium transition-colors {{ $isChordsActive ? 'text-accent' : 'text-ink-secondary hover:text-ink' }}">
                        <x-icon name="books" class="w-[18px] h-[18px] {{ $isChordsActive ? 'text-accent' : 'text-ink-secondary' }}" />
                        <span>Pustaka Chord</span>
                        @if ($isChordsActive)
                            <div class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-accent"></div>
                        @endif
                    </a>

                    <!-- Admin Links -->
                    @auth
                        @if (Auth::user()->role === 'admin')
                            <!-- Divider | -->
                            <div class="h-5 w-[1px] bg-subtle mx-10"></div>

                            <!-- Kelola User -->
                            @php
                                $isUsersActive = request()->routeIs('admin.users.index');
                            @endphp
                            <a href="{{ route('admin.users.index') }}" class="relative flex items-center gap-8 px-14 h-full text-sm font-medium transition-colors {{ $isUsersActive ? 'text-accent' : 'text-ink-secondary hover:text-ink' }}">
                                <x-icon name="user" class="w-[18px] h-[18px] {{ $isUsersActive ? 'text-accent' : 'text-ink-secondary' }}" />
                                <span>Kelola User</span>
                                @if ($isUsersActive)
                                    <div class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-accent"></div>
                                @endif
                            </a>

                            <!-- Kelola Lagu -->
                            @php
                                $isSongsActive = request()->routeIs('admin.songs.index');
                            @endphp
                            <a href="{{ route('admin.songs.index') }}" class="relative flex items-center gap-8 px-14 h-full text-sm font-medium transition-colors {{ $isSongsActive ? 'text-accent' : 'text-ink-secondary hover:text-ink' }}">
                                <x-icon name="music-single" class="w-[18px] h-[18px] {{ $isSongsActive ? 'text-accent' : 'text-ink-secondary' }}" />
                                <span>Kelola Lagu</span>
                                @if ($isSongsActive)
                                    <div class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-accent"></div>
                                @endif
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Right Controls: Profile / Auth -->
            <div class="hidden md:flex md:items-center">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-8 py-6 px-10 rounded-full bg-surface-2 border border-subtle hover:bg-surface-3 transition-colors text-ink cursor-pointer focus:outline-none" aria-label="Menu Pengguna">
                                <x-icon name="user-circle" class="w-[20px] h-[20px] text-ink-secondary" />
                                <x-icon name="chevron-down" class="w-[14px] h-[14px] text-ink-tertiary" />
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-14 py-8 border-b border-subtle">
                                <div class="font-medium text-sm text-ink flex items-center gap-6">
                                    <span>{{ Auth::user()->username }}</span>
                                    @if (Auth::user()->role === 'admin')
                                        <span class="text-[10px] px-6 py-1 rounded-full bg-accent-soft-bg text-accent-soft-text uppercase font-semibold">Admin</span>
                                    @endif
                                </div>
                                <div class="text-xs text-ink-tertiary truncate">{{ Auth::user()->email }}</div>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')" class="flex items-center gap-8">
                                <x-icon name="user" class="w-4 h-4 text-ink-secondary" />
                                <span>Profil</span>
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        class="flex items-center gap-8 text-error hover:bg-error-tint"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    <x-icon name="logout" class="w-4 h-4 text-error" />
                                    <span>Keluar</span>
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center gap-12">
                        <a href="{{ route('login') }}" class="h-[38px] px-14 rounded-lg bg-transparent hover:bg-surface-2 border border-subtle text-ink font-medium text-sm transition-colors inline-flex items-center justify-center">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}" class="h-[38px] px-14 rounded-lg bg-accent hover:bg-accent-hover text-white font-medium text-sm transition-colors inline-flex items-center justify-center">
                            Daftar
                        </a>
                    </div>
                @endauth
            </div>

            <!-- Hamburger Button (Mobile) -->
            <div class="flex items-center md:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center h-[38px] w-[38px] rounded-lg border border-subtle text-ink-secondary hover:text-ink hover:bg-surface-2 transition-colors focus:outline-none" aria-label="Toggle navigation">
                    <x-icon name="chevron-down" class="w-5 h-5" x-show="!open" />
                    <x-icon name="x" class="w-5 h-5" x-show="open" style="display: none;" />
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu (Mobile) -->
    <div x-show="open" class="md:hidden border-t border-subtle bg-surface-1 px-16 py-16 space-y-8" style="display: none;">
        <div class="space-y-4">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                <div class="flex items-center gap-8">
                    <x-icon name="home" class="w-4 h-4" />
                    <span>Home</span>
                </div>
            </x-responsive-nav-link>

            @auth
                <x-responsive-nav-link :href="route('my.songs')" :active="request()->routeIs('my.songs')">
                    <div class="flex items-center gap-8">
                        <x-icon name="music" class="w-4 h-4" />
                        <span>My Songs</span>
                    </div>
                </x-responsive-nav-link>
            @endauth

            <x-responsive-nav-link :href="route('chords.index')" :active="request()->routeIs('chords.index')">
                <div class="flex items-center gap-8">
                    <x-icon name="books" class="w-4 h-4" />
                    <span>Pustaka Chord</span>
                </div>
            </x-responsive-nav-link>

            @auth
                @if (Auth::user()->role === 'admin')
                    <div class="pt-8 border-t border-subtle">
                        <div class="text-xs uppercase text-ink-tertiary px-12 mb-4 font-semibold">Admin Area</div>
                        <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.index')">
                            <div class="flex items-center gap-8">
                                <x-icon name="user" class="w-4 h-4" />
                                <span>Kelola User</span>
                            </div>
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('admin.songs.index')" :active="request()->routeIs('admin.songs.index')">
                            <div class="flex items-center gap-8">
                                <x-icon name="music-single" class="w-4 h-4" />
                                <span>Kelola Lagu</span>
                            </div>
                        </x-responsive-nav-link>
                    </div>
                @endif
            @endauth
        </div>

        <!-- Mobile User Options -->
        <div class="pt-12 border-t border-subtle">
            @auth
                <div class="px-12 py-4 mb-8">
                    <div class="font-medium text-sm text-ink flex items-center gap-6">
                        <span>{{ Auth::user()->username }}</span>
                        @if (Auth::user()->role === 'admin')
                            <span class="text-[10px] px-6 py-1 rounded-full bg-accent-soft-bg text-accent-soft-text uppercase font-semibold">Admin</span>
                        @endif
                    </div>
                    <div class="text-xs text-ink-secondary">{{ Auth::user()->email }}</div>
                </div>

                <div class="space-y-4">
                    <x-responsive-nav-link :href="route('profile.edit')">
                        Profil
                    </x-responsive-nav-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                class="text-error"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                            Keluar
                        </x-responsive-nav-link>
                    </form>
                </div>
            @else
                <div class="flex flex-col gap-8 pt-4">
                    <a href="{{ route('login') }}" class="h-[38px] w-full rounded-lg bg-transparent hover:bg-surface-2 border border-subtle text-ink font-medium text-sm transition-colors inline-flex items-center justify-center">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="h-[38px] w-full rounded-lg bg-accent hover:bg-accent-hover text-white font-medium text-sm transition-colors inline-flex items-center justify-center">
                        Daftar
                    </a>
                </div>
            @endauth
        </div>
    </div>
</nav>

