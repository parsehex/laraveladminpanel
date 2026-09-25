<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="ui-sidebar fixed inset-y-0 left-0 z-50 w-72 max-w-[86vw] -translate-x-full flex-shrink-0 text-white transition-[width,transform] duration-200 ease-out lg:relative lg:w-64 lg:max-w-none lg:translate-x-0 lg:transform-none lg:min-h-0">
    <div class="sidebar-header flex h-20 items-center px-5">
        <a href="{{ route(\App\Support\PanelRedirector::routeNameFor(auth()->user())) }}"
           @click="sidebarOpen = false"
           class="flex min-w-0 items-center rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-white/40">
            <div class="ui-brand-mark h-11 w-11 rounded-2xl flex items-center justify-center font-extrabold text-lg flex-shrink-0">B</div>
            <div class="ml-3 min-w-0 leading-tight sidebar-brand-text">
                <h1 class="text-lg font-extrabold text-white tracking-tight">Ben's Appliances</h1>
            </div>
        </a>
        <button type="button" @click="sidebarOpen = false" class="ml-auto flex h-10 w-10 items-center justify-center rounded-xl text-white/70 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close menu">
            <i class="fas fa-times"></i>
        </button>
        <button type="button" @click="sidebarCollapsed = !sidebarCollapsed"
                class="sidebar-collapse-btn ml-auto hidden h-10 w-10 items-center justify-center rounded-xl text-white/70 hover:bg-white/10 hover:text-white lg:flex"
                :aria-label="sidebarCollapsed ? 'Expand menu' : 'Collapse menu'"
                title="Toggle sidebar">
            <i class="sidebar-icon-collapse fas fa-angles-left"></i>
            <i class="sidebar-icon-expand fas fa-angles-right hidden"></i>
        </button>
    </div>

    <nav class="mt-4 space-y-1">
        @canAccess('admin.dashboard')
        <a href="{{ route('admin.dashboard') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
            <i class="fas fa-chart-pie mr-3 w-5 text-center"></i>
            <span>Dashboard</span>
        </a>
        @endcanAccess

        @canAccess('executive-dashboard.view')
        <a href="{{ route('admin.executive-dashboard.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.executive-dashboard.*') ? 'is-active' : '' }}">
            <i class="fas fa-chart-line mr-3 w-5 text-center"></i>
            <span>Executive Dashboard</span>
        </a>
        @endcanAccess

        @canAccess('inventory.view')
        <x-admin.nav-folder
            id="inventory"
            label="Inventory"
            icon="fa-warehouse"
            :active="isInventoryNavFolderActive()">
            <a href="{{ route('admin.inventory.index') }}" @click="sidebarOpen = false"
               class="ui-nav-link flex items-center px-4 py-2.5 text-sm font-semibold {{ isApplianceInventoryNavActive() ? 'is-active' : '' }}">
                <i class="fas fa-boxes-stacked mr-3 w-5 text-center"></i>
                <span>Appliances</span>
            </a>
            <a href="{{ route('admin.inventory.scan') }}" @click="sidebarOpen = false"
               class="ui-nav-link flex items-center px-4 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.inventory.scan*') ? 'is-active' : '' }}">
                <i class="fas fa-qrcode mr-3 w-5 text-center"></i>
                <span>Scan</span>
            </a>
        </x-admin.nav-folder>
        @endcanAccess

        @canAccess('trucks.view')
        <a href="{{ route('admin.trucks.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.trucks.*') ? 'is-active' : '' }}">
            <i class="fas fa-truck mr-3 w-5 text-center"></i>
            <span>Trucks</span>
        </a>
        @endcanAccess

        @canAccess('models.view')
        <a href="{{ route('admin.models.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.models.*') ? 'is-active' : '' }}">
            <i class="fas fa-cube mr-3 w-5 text-center"></i>
            <span>Models</span>
        </a>
        @endcanAccess

        @canAccess('parts.view')
        <a href="{{ route('admin.parts.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.parts.*') ? 'is-active' : '' }}">
            <i class="fas fa-cogs mr-3 w-5 text-center"></i>
            <span>Parts</span>
        </a>
        @endcanAccess

        @canAccess('deliveries.view')
        <a href="{{ route('admin.deliveries.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.deliveries.*') ? 'is-active' : '' }}">
            <i class="fas fa-route mr-3 w-5 text-center"></i>
            <span>Deliveries</span>
        </a>
        @endcanAccess

        @canAccess('sales.view')
        <a href="{{ route('admin.sales.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.sales.*') ? 'is-active' : '' }}">
            <i class="fas fa-cash-register mr-3 w-5 text-center"></i>
            <span>Sales</span>
        </a>
        @endcanAccess

        @php
            $showKitsLink = canAccess('kits.view');
            $showKitParts = canAccess('kit-parts.view');
        @endphp
        @if($showKitsLink && $showKitParts)
        <x-admin.nav-folder
            id="kits"
            label="Kits"
            icon="fa-toolbox"
            :href="route('admin.kits.index')"
            :active="request()->routeIs('admin.kits.*') || isKitsNavFolderActive()"
            :link-active="request()->routeIs('admin.kits.*')">
            <a href="{{ route('admin.kit-parts.index') }}" @click="sidebarOpen = false"
               class="ui-nav-link flex items-center px-4 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.kit-parts.*') ? 'is-active' : '' }}">
                <i class="fas fa-screwdriver-wrench mr-3 w-5 text-center"></i>
                <span>Kit Parts</span>
            </a>
        </x-admin.nav-folder>
        @elseif($showKitsLink)
        <a href="{{ route('admin.kits.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.kits.*') ? 'is-active' : '' }}">
            <i class="fas fa-toolbox mr-3 w-5 text-center"></i>
            <span>Kits</span>
        </a>
        @elseif($showKitParts)
        <a href="{{ route('admin.kit-parts.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.kit-parts.*') ? 'is-active' : '' }}">
            <i class="fas fa-screwdriver-wrench mr-3 w-5 text-center"></i>
            <span>Kit Parts</span>
        </a>
        @endif

        @php
            $showProcedures = canAccess('deman-flows.manage') || canAccess('testing-flows.manage');
            $showUsersLink = canAccess('users.view');
            $showUserChildren = canAccess('roles.view')
                || canAccess('notification-settings.manage')
                || canAccess('user-actions.view');
            $showManageFolder = $showProcedures
                || $showUsersLink
                || $showUserChildren
                || canAccess('inventory-statuses.manage')
                || canAccess('inventory-locations.manage');
        @endphp

        @if($showManageFolder)
        <x-admin.nav-folder id="manage" label="Manage" icon="fa-gears" :active="isManageNavFolderActive()">
            @if($showProcedures)
            <x-admin.nav-folder id="procedures" label="Procedures" icon="fa-clipboard-list" :active="isProceduresNavFolderActive()">
                @canAccess('deman-flows.manage')
                <a href="{{ route('admin.deman-flows.index') }}" @click="sidebarOpen = false"
                   class="ui-nav-link flex items-center px-4 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.deman-flows.*') ? 'is-active' : '' }}">
                    <i class="fas fa-recycle mr-3 w-5 text-center"></i>
                    <span>Demanufacture</span>
                </a>
                @endcanAccess

                @canAccess('testing-flows.manage')
                <a href="{{ route('admin.testing-flows.index') }}" @click="sidebarOpen = false"
                   class="ui-nav-link flex items-center px-4 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.testing-flows.*') ? 'is-active' : '' }}">
                    <i class="fas fa-clipboard-check mr-3 w-5 text-center"></i>
                    <span>Testing</span>
                </a>
                @endcanAccess
            </x-admin.nav-folder>
            @endif

            @if($showUsersLink && ! $showUserChildren)
            <a href="{{ route('admin.users.index') }}" @click="sidebarOpen = false"
               class="ui-nav-link flex items-center px-4 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
                <i class="fas fa-users mr-3 w-5 text-center"></i>
                <span>Users</span>
            </a>
            @elseif($showUsersLink || $showUserChildren)
            <x-admin.nav-folder
                id="users"
                label="Users"
                icon="fa-users"
                :href="$showUsersLink ? route('admin.users.index') : null"
                :active="($showUsersLink && request()->routeIs('admin.users.*')) || isUsersNavFolderActive()"
                :link-active="request()->routeIs('admin.users.*')">
                @canAccess('roles.view')
                <a href="{{ route('admin.roles.index') }}" @click="sidebarOpen = false"
                   class="ui-nav-link flex items-center px-4 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}">
                    <i class="fas fa-user-shield mr-3 w-5 text-center"></i>
                    <span>Roles</span>
                </a>
                @endcanAccess

                @canAccess('notification-settings.manage')
                <a href="{{ route('admin.notification-settings.index') }}" @click="sidebarOpen = false"
                   class="ui-nav-link flex items-center px-4 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.notification-settings.*') ? 'is-active' : '' }}">
                    <i class="fas fa-bell mr-3 w-5 text-center"></i>
                    <span>Notifications</span>
                </a>
                @endcanAccess

                @canAccess('user-actions.view')
                <a href="{{ route('admin.user-actions.index') }}" @click="sidebarOpen = false"
                   class="ui-nav-link flex items-center px-4 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.user-actions.*') ? 'is-active' : '' }}">
                    <i class="fas fa-list-ul mr-3 w-5 text-center"></i>
                    <span>User Actions</span>
                </a>
                @endcanAccess
            </x-admin.nav-folder>
            @endif

            @canAccess('inventory-locations.manage')
            <a href="{{ route('admin.inventory-locations.index') }}" @click="sidebarOpen = false"
               class="ui-nav-link flex items-center px-4 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.inventory-locations.*') ? 'is-active' : '' }}">
                <i class="fas fa-map-marker-alt mr-3 w-5 text-center"></i>
                <span>Locations</span>
            </a>
            @endcanAccess

            @canAccess('inventory-statuses.manage')
            <a href="{{ route('admin.inventory-statuses.index') }}" @click="sidebarOpen = false"
               class="ui-nav-link flex items-center px-4 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.inventory-statuses.*') ? 'is-active' : '' }}">
                <i class="fas fa-tags mr-3 w-5 text-center"></i>
                <span>Statuses</span>
            </a>
            @endcanAccess
        </x-admin.nav-folder>
        @endif

        <div class="mx-3 mt-8 border-t border-white/10"></div>
        <form method="POST" action="{{ route('logout') }}" class="pt-4">
            @csrf
            <button type="submit" class="ui-nav-link flex items-center px-4 py-3 text-left text-sm font-semibold">
                <i class="fas fa-sign-out-alt mr-3 w-5 text-center"></i>
                <span>Logout</span>
            </button>
        </form>
    </nav>
</aside>
