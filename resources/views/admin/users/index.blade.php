<x-app-layout>
    <div class="max-w-content mx-auto w-full px-16 md:px-24 py-36">
        <!-- Search and Role Filter Form -->
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-16">
            <button type="submit" class="hidden" aria-hidden="true"></button>

            <!-- Search Bar -->
            <div class="relative flex-1">
                <button type="submit" class="absolute inset-y-0 left-0 pl-14 flex items-center text-ink-secondary hover:text-accent transition-colors cursor-pointer" aria-label="Cari">
                    <x-icon name="search" class="w-4 h-4" />
                </button>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari username atau email..."
                    class="w-full h-[44px] pl-[40px] pr-[36px] bg-surface-2 border border-subtle rounded-xl text-ink placeholder:text-ink-secondary focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent text-sm transition-colors"
                >
                @if (request('q'))
                    <a href="{{ route('admin.users.index', request()->except('q', 'page')) }}" class="absolute inset-y-0 right-0 pr-14 flex items-center text-ink-secondary hover:text-ink transition-colors cursor-pointer" aria-label="Hapus pencarian">
                        <x-icon name="x" class="w-3.5 h-3.5" />
                    </a>
                @endif
            </div>

            <!-- Role Dropdown Filter -->
            <div class="relative w-full sm:w-[180px]">
                <select
                    name="role"
                    onchange="this.form.submit()"
                    class="w-full h-[44px] bg-surface-2 border border-subtle rounded-xl px-14 pr-[36px] text-ink-secondary focus:text-ink text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent cursor-pointer transition-colors"
                >
                    <option value="">Semua Role</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="member" {{ in_array(request('role'), ['member', 'user']) ? 'selected' : '' }}>Member</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-12 flex items-center pointer-events-none text-ink-secondary">
                    <x-icon name="chevron-down" class="w-4 h-4" />
                </div>
            </div>
        </form>

        <!-- Stat Cards Row (2 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-16 md:gap-24 mt-20">
            <!-- Total User -->
            <div class="bg-surface-1 border border-subtle rounded-2xl p-20 flex items-center gap-16">
                <div class="w-[52px] h-[52px] rounded-xl bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                    <x-icon name="user" class="w-6 h-6" />
                </div>
                <div>
                    <span class="text-sm font-medium text-ink-secondary">Total User</span>
                    <div class="mt-4 flex items-center gap-10">
                        <span class="text-xl font-semibold text-ink">{{ $totalUsers }}</span>
                        <div class="h-[6px] w-[60px] rounded-full bg-accent/60"></div>
                    </div>
                </div>
            </div>

            <!-- Admin -->
            <div class="bg-surface-1 border border-subtle rounded-2xl p-20 flex items-center gap-16">
                <div class="w-[52px] h-[52px] rounded-xl bg-surface-2 border border-subtle flex items-center justify-center text-accent shrink-0">
                    <x-icon name="user" class="w-6 h-6" />
                </div>
                <div>
                    <span class="text-sm font-medium text-ink-secondary">Admin</span>
                    <div class="mt-4 flex items-center gap-10">
                        <span class="text-xl font-semibold text-ink">{{ $adminUsers }}</span>
                        <div class="h-[6px] w-[50px] rounded-full bg-accent/60"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session Status Flash Alert -->
        @if (session('status'))
            <div class="mt-20 p-16 rounded-xl bg-accent/15 border border-accent/30 text-accent text-sm flex items-center gap-10">
                <x-icon name="check" class="w-5 h-5 shrink-0" />
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="mt-20 p-16 rounded-xl bg-[#DE6D62]/15 border border-[#DE6D62]/30 text-[#DE6D62] text-sm flex items-center gap-10">
                <x-icon name="alert-circle" class="w-5 h-5 shrink-0" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Users Data Table Container -->
        <div class="mt-24 bg-surface-1 border border-subtle rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-subtle bg-surface-2/40 text-ink-secondary text-xs font-medium">
                            <th scope="col" class="py-16 px-20">Username</th>
                            <th scope="col" class="py-16 px-20">Email</th>
                            <th scope="col" class="py-16 px-20">Role</th>
                            <th scope="col" class="py-16 px-20">Jumlah Lagu</th>
                            <th scope="col" class="py-16 px-20 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-subtle/40">
                        @forelse ($users as $u)
                            <tr class="hover:bg-surface-2/30 transition-colors">
                                <!-- Username -->
                                <td class="py-16 px-20 whitespace-nowrap">
                                    <div class="flex items-center gap-10">
                                        <div class="w-8 h-8 rounded-full bg-surface-2 border border-subtle flex items-center justify-center text-ink-secondary shrink-0">
                                            <x-icon name="user" class="w-4 h-4" />
                                        </div>
                                        <span class="text-sm font-medium text-ink">{{ $u->username }}</span>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="py-16 px-20 whitespace-nowrap text-sm text-ink-secondary">
                                    {{ $u->email }}
                                </td>

                                <!-- Role Badge -->
                                <td class="py-16 px-20 whitespace-nowrap">
                                    <span class="inline-flex items-center px-12 py-3 rounded-md text-xs font-medium border border-subtle {{ $u->role === 'admin' ? 'bg-accent/15 border-accent/40 text-accent' : 'bg-surface-2 text-ink-secondary' }}">
                                        {{ $u->role === 'admin' ? 'Admin' : 'Member' }}
                                    </span>
                                </td>

                                <!-- Jumlah Lagu -->
                                <td class="py-16 px-20 whitespace-nowrap text-sm text-ink-secondary">
                                    {{ $u->songs_count }} songs
                                </td>

                                <!-- Aksi -->
                                <td class="py-16 px-20 whitespace-nowrap text-right">
                                    @if ($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini beserta seluruh lagu miliknya?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-[32px] px-16 rounded-lg bg-[#DE6D62] hover:bg-[#D55F55] text-[#131C2E] font-medium text-xs inline-flex items-center justify-center transition-colors cursor-pointer">
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
                                <td colspan="5" class="py-40 text-center text-sm text-ink-secondary">
                                    Tidak ada user yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Section -->
        <div class="mt-40 mb-20 flex justify-center">
            <x-pagination :paginator="$users" />
        </div>
    </div>
</x-app-layout>
