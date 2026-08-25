<header class="ui-topbar relative z-[1000] bg-white shadow-sm border-b border-gray-200">
    <div class="flex min-w-0 flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8 lg:py-4">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button" @click="sidebarOpen = true" class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white/80 text-slate-700 shadow-sm hover:bg-slate-50 lg:hidden" aria-label="Open menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Workspace</p>
                <h2 class="truncate text-lg font-extrabold text-gray-800 sm:text-xl">@yield('page-title', 'Dashboard')</h2>
            </div>
            @hasSection('page-actions')
                <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
                    @yield('page-actions')
                </div>
            @endif
        </div>
        
        <div class="flex flex-shrink-0 items-center space-x-2 sm:space-x-4">
            <x-admin.table-density-toggle />

            <div class="hidden md:block rounded-xl border border-slate-200 bg-white/80 px-4 py-2 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Signed In</p>
                <p class="max-w-44 truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
            </div>

            <!-- Notifications -->
            <div class="relative" x-data="adminNotifications()" x-init="init()">
                <button type="button"
                        @click="toggle()"
                        class="relative h-10 w-10 rounded-full border border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-white flex items-center justify-center"
                        aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span x-show="unreadCount > 0"
                          x-cloak
                          x-text="unreadCount > 99 ? '99+' : unreadCount"
                          class="absolute -right-1 -top-1 min-w-[1.25rem] rounded-full bg-red-600 px-1.5 py-0.5 text-center text-[10px] font-bold leading-none text-white"></span>
                </button>

                <div x-cloak
                     x-show="open"
                     @click.away="open = false"
                     class="absolute right-0 mt-3 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-md shadow-lg border border-slate-200 z-[1001] overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">Notifications</p>
                        <button type="button"
                                class="text-xs font-semibold text-blue-600 hover:text-blue-800 disabled:text-slate-400"
                                @click="markAllRead()"
                                :disabled="unreadCount === 0 || loading">
                            Mark all read
                        </button>
                    </div>
                    <div class="max-h-80 overflow-y-auto">
                        <p x-show="loading && items.length === 0" x-cloak class="px-4 py-6 text-sm text-slate-500 text-center">Loading…</p>
                        <p x-show="!loading && items.length === 0" x-cloak class="px-4 py-6 text-sm text-slate-500 text-center">No notifications.</p>
                        <template x-for="item in items" :key="item.id">
                            <a :href="item.url || '#'"
                               @click="markRead(item)"
                               class="block border-b border-slate-50 px-4 py-3 hover:bg-slate-50"
                               :class="item.read_at ? 'opacity-70' : ''">
                                <p class="text-sm font-semibold text-slate-900" x-text="item.title"></p>
                                <p class="mt-0.5 text-xs text-slate-600" x-text="item.message"></p>
                                <p class="mt-1 text-[11px] text-slate-400" x-text="item.created_at"></p>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
            
            <!-- User Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 rounded-full border border-slate-200 bg-white/70 py-1.5 pl-1.5 pr-2 sm:space-x-3 sm:pr-3">
                    <div class="w-9 h-9 bg-gray-300 rounded-full flex items-center justify-center text-slate-700">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="hidden max-w-28 truncate text-sm font-semibold sm:inline md:max-w-40">{{ auth()->user()->name }}</span>
                    <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                </button>
                
                <div x-cloak x-show="open" @click.away="open = false"
                     class="absolute right-0 mt-3 w-52 bg-white rounded-md shadow-lg py-2 z-[1001] border border-slate-200">
                    <a href="{{ route('admin.profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-user mr-2"></i>Profile
                    </a>
                    <a href="{{ route('admin.profile.password.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-lock mr-2"></i>Change Password
                    </a>
                    <div class="px-4 py-2">
                        <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="border-t border-gray-100 my-1"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
window.adminNotifications = function () {
    return {
        open: false,
        loading: false,
        items: [],
        unreadCount: 0,
        endpoints: {
            index: @json(route('admin.notifications.index')),
            readAll: @json(route('admin.notifications.read-all')),
            read: @json(url('/admin/notifications')),
        },
        csrf: @json(csrf_token()),
        init() {
            this.refresh();
            setInterval(() => this.refresh(false), 60000);
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.refresh();
            }
        },
        async refresh(showLoading = true) {
            if (showLoading) {
                this.loading = true;
            }

            try {
                const response = await fetch(this.endpoints.index, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    return;
                }
                const data = await response.json();
                this.items = data.notifications || [];
                this.unreadCount = data.unread_count || 0;
            } finally {
                this.loading = false;
            }
        },
        async markRead(item) {
            if (!item || item.read_at) {
                return;
            }

            try {
                await fetch(this.endpoints.read + '/' + encodeURIComponent(item.id) + '/read', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    credentials: 'same-origin',
                });
                item.read_at = new Date().toISOString();
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            } catch (e) {}
        },
        async markAllRead() {
            if (this.unreadCount === 0) {
                return;
            }

            this.loading = true;
            try {
                await fetch(this.endpoints.readAll, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    credentials: 'same-origin',
                });
                this.items = this.items.map((item) => ({ ...item, read_at: item.read_at || new Date().toISOString() }));
                this.unreadCount = 0;
            } finally {
                this.loading = false;
            }
        },
    };
};
</script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
