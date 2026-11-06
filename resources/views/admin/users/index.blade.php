<x-app-layout>
    <div class="max-w-content mx-auto w-full px-16 md:px-24 py-24 sm:py-32 pb-[88px]">
        <!-- Header -->
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-ink tracking-tight">
                Kelola Pengguna
            </h1>
            <p class="text-xs sm:text-sm text-ink-secondary mt-1">
                Kelola akun pengguna dan hak akses di aplikasi ChordCue.
            </p>
        </div>

        <!-- Session Status & Error Flash Alerts -->
        @if (session('status'))
            <div class="mt-16 p-14 rounded-md bg-accent/15 border border-accent/30 text-accent text-xs sm:text-sm flex items-center gap-10">
                <x-icon name="check" class="w-icon-control h-icon-control shrink-0" />
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="mt-16 p-14 rounded-md bg-error-tint/80 border border-error/40 text-error text-xs sm:text-sm flex items-center gap-10">
                <x-icon name="alert-circle" class="w-icon-control h-icon-control shrink-0" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Stat Cards Row (2 Cards: Total User, Admin) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-12 sm:gap-16 mt-20">
            <!-- Total User -->
            <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 flex items-center gap-16">
                <div class="w-[44px] h-[44px] rounded-md bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                    <x-icon name="user" class="w-icon-control h-icon-control" />
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-ink-secondary">Total User</span>
                    <div class="mt-2 flex items-baseline gap-10">
                        <span class="text-2xl font-bold font-mono text-ink">{{ $totalUsers }}</span>
                        <span class="text-[11px] text-ink-tertiary">Terdaftar di sistem</span>
                    </div>
                </div>
            </div>

            <!-- Admin -->
            <div class="bg-surface-1 border border-subtle rounded-lg p-16 sm:p-20 flex items-center gap-16">
                <div class="w-[44px] h-[44px] rounded-md bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                    <x-icon name="lock" class="w-icon-control h-icon-control" />
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-ink-secondary">Admin</span>
                    <div class="mt-2 flex items-baseline gap-10">
                        <span class="text-2xl font-bold font-mono text-ink">{{ $adminUsers }}</span>
                        <span class="text-[11px] text-ink-tertiary">Hak akses administrator</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Role Filter Toolbar -->
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-12 mt-20 p-14 sm:p-16 rounded-lg bg-surface-1 border border-subtle">
            <button type="submit" class="hidden" aria-hidden="true"></button>

            <!-- Search Bar -->
            <div class="relative flex-1">
                <button type="submit" class="absolute inset-y-0 left-0 pl-14 flex items-center text-ink-secondary hover:text-accent transition-colors cursor-pointer" aria-label="Cari">
                    <x-icon name="search" class="w-icon-control h-icon-control" />
                </button>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari username atau email..."
                    class="w-full h-[40px] pl-[38px] pr-[36px] bg-surface-2 border border-subtle rounded-sm text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-xs sm:text-sm transition-colors"
                >
                @if (request('q'))
                    <a href="{{ route('admin.users.index', request()->except('q', 'page')) }}" class="absolute inset-y-0 right-0 pr-14 flex items-center text-ink-secondary hover:text-ink transition-colors cursor-pointer" aria-label="Hapus pencarian">
                        <x-icon name="x" class="w-[14px] h-[14px]" />
                    </a>
                @endif
            </div>

            <!-- Role Dropdown Filter -->
            <div class="relative w-full sm:w-[180px]">
                <select
                    name="role"
                    onchange="this.form.submit()"
                    class="w-full h-[40px] bg-surface-2 border border-subtle rounded-sm px-14 pr-[36px] text-ink text-xs sm:text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                >
                    <option value="">Semua Role</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="member" {{ in_array(request('role'), ['member', 'user']) ? 'selected' : '' }}>Member</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-12 flex items-center pointer-events-none text-ink-secondary">
                    <x-icon name="chevron-down" class="w-icon-control h-icon-control" />
                </div>
            </div>
        </form>

        <!-- Users Data Table Container -->
        <div class="mt-20 bg-surface-1 border border-subtle rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-subtle bg-surface-2/60 text-ink-secondary text-[11px] font-semibold uppercase tracking-wider">
                            <th scope="col" class="py-12 px-16 sm:px-20">Username</th>
                            <th scope="col" class="py-12 px-16 sm:px-20">Email</th>
                            <th scope="col" class="py-12 px-16 sm:px-20">Role</th>
                            <th scope="col" class="py-12 px-16 sm:px-20 font-mono">Jumlah Lagu</th>
                            <th scope="col" class="py-12 px-16 sm:px-20 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-subtle/50">
                        @forelse ($users as $u)
                            <tr class="hover:bg-surface-2/40 transition-colors">
                                <!-- Username with Avatar -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap">
                                    <div class="flex items-center gap-10">
                                        <div class="w-[32px] h-[32px] rounded-full bg-surface-3 border border-subtle flex items-center justify-center text-accent shrink-0 text-xs font-bold uppercase">
                                            {{ strtoupper(substr($u->username, 0, 1)) }}
                                        </div>
                                        <span class="text-xs sm:text-sm font-semibold text-ink">{{ $u->username }}</span>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-xs sm:text-sm text-ink-secondary">
                                    {{ $u->email }}
                                </td>

                                <!-- Role Badge -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap">
                                    @if ($u->role === 'admin')
                                        <span class="inline-flex items-center px-10 py-2 rounded-full text-[11px] font-semibold border border-accent/40 bg-accent-soft-bg text-accent-soft-text">
                                            Admin
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-10 py-2 rounded-full text-[11px] font-medium border border-subtle bg-surface-2 text-ink-secondary">
                                            Member
                                        </span>
                                    @endif
                                </td>

                                <!-- Jumlah Lagu -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-xs sm:text-sm font-mono text-ink-secondary">
                                    {{ $u->songs_count }} songs
                                </td>

                                <!-- Aksi -->
                                <td class="py-14 px-16 sm:px-20 whitespace-nowrap text-right">
                                    @if ($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini beserta seluruh lagu miliknya?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-[32px] px-14 rounded-sm bg-error-tint/70 hover:bg-error-tint border border-error/30 text-error font-semibold text-xs inline-flex items-center justify-center transition-colors cursor-pointer">
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-ink-tertiary italic">Akun Anda</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-40 text-center text-xs sm:text-sm text-ink-secondary">
                                    Tidak ada user yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fixed Pagination Floating Footer -->
        <x-pagination :paginator="$users" />
    </div>
</x-app-layout>
