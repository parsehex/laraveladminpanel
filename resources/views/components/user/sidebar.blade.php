<div class="ui-sidebar text-white w-64 flex-shrink-0">
    <div class="flex items-center h-20 px-5">
        <div class="ui-brand-mark h-11 w-11 rounded-2xl flex items-center justify-center font-extrabold text-lg">H</div>
        <div class="ml-3 leading-tight">
            <h1 class="text-lg font-extrabold tracking-tight">Hey There</h1>
            <p class="text-xs font-medium text-white/55">Unified system</p>
        </div>
    </div>
    
    <nav class="mt-4 space-y-1">
        <a href="{{ route('user.dashboard') }}" 
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->routeIs('user.dashboard') ? 'is-active' : '' }}">
            <i class="fas fa-chart-pie mr-3 w-5 text-center"></i>
            <span>Dashboard</span>
        </a>
            {{-- {{ dd(request()->route()) }} --}}
        <a href="{{ route("user.account.edit",['account' => encrypt(auth()->user()->id)]) }}" 
           class="ui-nav-link flex items-center px-4 py-3 text-sm font-semibold {{ request()->is('user/account/*') ? 'is-active' : '' }}">
            <i class="fas fa-user mr-3 w-5 text-center"></i>
            <span>Profile</span>
        </a>
        
        {{-- <div class="border-t border-blue-700 mt-8 pt-4">
            <form method="POST" action="{{ route('logout') }}" class="px-6">
                @csrf
                <button type="submit" class="flex items-center w-full text-left text-blue-200 hover:text-white">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    Logout
                </button>
            </form>
        </div> --}}
    </nav>
</div>
