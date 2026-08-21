<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="ui-sidebar fixed inset-y-0 left-0 z-50 w-72 max-w-[86vw] -translate-x-full flex-shrink-0 text-white transition-[width,transform] duration-200 ease-out lg:relative lg:w-64 lg:max-w-none lg:translate-x-0 lg:transform-none">
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

        @canAccess('trucks.view')
        <a href="{{ route('admin.trucks.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.trucks.*') ? 'is-active' : '' }}">
            <i class="fas fa-truck mr-3 w-5 text-center"></i>
            <span>Trucks</span>
        </a>
        @endcanAccess

        @canAccess('parts.view')
        <a href="{{ route('admin.parts.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.parts.*') ? 'is-active' : '' }}">
            <i class="fas fa-cogs mr-3 w-5 text-center"></i>
            <span>Parts</span>
        </a>
        @endcanAccess

        @canAccess('kit-parts.view')
        <a href="{{ route('admin.kit-parts.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.kit-parts.*') ? 'is-active' : '' }}">
            <i class="fas fa-screwdriver-wrench mr-3 w-5 text-center"></i>
            <span>Kit Parts</span>
        </a>
        @endcanAccess

        @canAccess('models.view')
        <a href="{{ route('admin.models.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.models.*') ? 'is-active' : '' }}">
            <i class="fas fa-cube mr-3 w-5 text-center"></i>
            <span>Models</span>
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

        @canAccess('inventory.view')
        <a href="{{ route('admin.inventory.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.inventory.*') && ! request()->routeIs('admin.inventory.scan*') ? 'is-active' : '' }}">
            <i class="fas fa-boxes-stacked mr-3 w-5 text-center"></i>
            <span>Inventory</span>
        </a>
        <a href="{{ route('admin.inventory.scan') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.inventory.scan*') ? 'is-active' : '' }}">
            <i class="fas fa-qrcode mr-3 w-5 text-center"></i>
            <span>Scan</span>
        </a>
        @endcanAccess

        @canAccess('kits.view')
        <a href="{{ route('admin.kits.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.kits.*') ? 'is-active' : '' }}">
            <i class="fas fa-toolbox mr-3 w-5 text-center"></i>
            <span>Kits</span>
        </a>
        @endcanAccess

        @canAccess('users.view')
        <a href="{{ route('admin.users.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
            <i class="fas fa-users mr-3 w-5 text-center"></i>
            <span>Users</span>
        </a>
        @endcanAccess

        @canAccess('roles.view')
        <a href="{{ route('admin.roles.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}">
            <i class="fas fa-user-shield mr-3 w-5 text-center"></i>
            <span>Roles</span>
        </a>
        @endcanAccess

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
