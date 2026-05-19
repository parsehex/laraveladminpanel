<div class="ui-sidebar text-white w-64 flex-shrink-0">
    <div class="flex items-center h-20 px-5">
        <div class="ui-brand-mark h-11 w-11 rounded-2xl flex items-center justify-center font-extrabold text-lg">B</div>
        <div class="ml-3 leading-tight">
            <h1 class="text-lg font-extrabold tracking-tight">Ben's Appliances</h1>
            <p class="text-xs font-medium text-white/55">Unified system</p>
        </div>
    </div>
    
    <nav class="mt-4 space-y-1">
        <a href="{{ route('user.dashboard') }}" 
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('user.dashboard') ? 'is-active' : '' }}">
            <i class="fas fa-chart-pie mr-3 w-5 text-center"></i>
            <span>Dashboard</span>
        </a>

        @canAccess('admin.dashboard')
        <a href="{{ route('admin.dashboard') }}" 
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
            <i class="fas fa-gauge mr-3 w-5 text-center"></i>
            <span>Admin Dashboard</span>
        </a>
        @endcanAccess

        @canAccess('parts.view')
        <a href="{{ route('admin.parts.index') }}" 
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.parts.*') ? 'is-active' : '' }}">
            <i class="fas fa-cogs mr-3 w-5 text-center"></i>
            <span>Parts</span>
        </a>
        @endcanAccess

        @canAccess('models.view')
        <a href="{{ route('admin.models.index') }}" 
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.models.*') ? 'is-active' : '' }}">
            <i class="fas fa-cube mr-3 w-5 text-center"></i>
            <span>Models</span>
        </a>
        @endcanAccess

        @canAccess('trucks.view')
        <a href="{{ route('admin.trucks.index') }}" 
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.trucks.*') ? 'is-active' : '' }}">
            <i class="fas fa-truck mr-3 w-5 text-center"></i>
            <span>Trucks</span>
        </a>
        @endcanAccess

        @canAccess('sales.view')
        <a href="{{ route('admin.sales.index') }}" 
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.sales.*') ? 'is-active' : '' }}">
            <i class="fas fa-cash-register mr-3 w-5 text-center"></i>
            <span>Sales</span>
        </a>
        @endcanAccess

        @canAccess('inventory.view')
        <a href="{{ route('admin.inventory.index') }}" 
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('admin.inventory.*') ? 'is-active' : '' }}">
            <i class="fas fa-boxes-stacked mr-3 w-5 text-center"></i>
            <span>Inventory</span>
        </a>
        @endcanAccess

        <a href="{{ route("user.account.edit",['account' => encrypt(auth()->user()->id)]) }}" 
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->is('user/account/*') ? 'is-active' : '' }}">
            <i class="fas fa-user mr-3 w-5 text-center"></i>
            <span>Profile</span>
        </a>

        <a href="{{ route("user.account.changePassword",['uid' => encrypt(auth()->user()->id)]) }}" 
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('user.account.changePassword') ? 'is-active' : '' }}">
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
</div>
