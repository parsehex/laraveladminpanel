<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="ui-sidebar fixed inset-y-0 left-0 z-50 w-72 max-w-[86vw] -translate-x-full flex-shrink-0 overflow-y-auto text-white transition-transform duration-200 ease-out lg:static lg:w-64 lg:max-w-none lg:translate-x-0">
    <div class="flex items-center h-20 px-5">
        <div class="ui-brand-mark h-11 w-11 rounded-2xl flex items-center justify-center font-extrabold text-lg">B</div>
        <div class="ml-3 min-w-0 leading-tight">
            <h1 class="text-lg font-extrabold tracking-tight text-white">Ben's Appliances</h1>
            <!-- <p class="text-xs font-medium text-white/55">Unified system</p> -->
        </div>
        <button type="button" @click="sidebarOpen = false" class="ml-auto flex h-10 w-10 items-center justify-center rounded-xl text-white/70 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close menu">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="mt-4 space-y-1">

        @canAccess('admin.dashboard')
        <a href="{{ route('admin.dashboard') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
            <i class="fas fa-gauge mr-3 w-5 text-center"></i>
            <span>Admin Dashboard</span>
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

        @canAccess('models.view')
        <a href="{{ route('admin.models.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.models.*') ? 'is-active' : '' }}">
            <i class="fas fa-cube mr-3 w-5 text-center"></i>
            <span>Models</span>
        </a>
        @endcanAccess
        
        <!-- @canAccess('deliveries.view')
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
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.inventory.*') ? 'is-active' : '' }}">
            <i class="fas fa-boxes-stacked mr-3 w-5 text-center"></i>
            <span>Inventory</span>
        </a>
        @endcanAccess

        @canAccess('kits.view')
        <a href="{{ route('admin.kits.index') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.kits.*') ? 'is-active' : '' }}">
            <i class="fas fa-toolbox mr-3 w-5 text-center"></i>
            <span>Kits</span>
        </a>
        @endcanAccess -->
       

        <a href="{{ route('admin.profile.edit') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.profile.edit') ? 'is-active' : '' }}">
            <i class="fas fa-user mr-3 w-5 text-center"></i>
            <span>Profile</span>
        </a>

        <a href="{{ route('admin.profile.password.edit') }}" @click="sidebarOpen = false"
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.profile.password.*') ? 'is-active' : '' }}">
            <i class="fas fa-lock mr-3 w-5 text-center"></i>
            <span>Change Password</span>
        </a>

        <div class="border-t border-white/10 mt-8 pt-4 mx-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="ui-nav-link flex items-center w-full px-4 py-3 text-left text-sm font-semibold">
                    <i class="fas fa-sign-out-alt mr-3 w-5 text-center"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </nav>
</aside>
