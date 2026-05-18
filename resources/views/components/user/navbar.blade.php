<header class="ui-topbar relative z-[1000] bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-6 lg:px-8 py-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Workspace</p>
            <h2 class="text-xl font-extrabold text-gray-800">@yield('page-title', 'Dashboard')</h2>
        </div>
        
        <div class="flex items-center space-x-4">
            <!-- User Info -->
            <div class="flex items-center space-x-3 text-gray-700 rounded-full border border-slate-200 bg-white/70 py-1.5 pl-1.5 pr-3">
                <div class="w-9 h-9 bg-gray-300 rounded-full flex items-center justify-center text-slate-700">
                    <i class="fas fa-user"></i>
                </div>
                <span class="text-sm font-semibold">{{ auth()->user()->name }}</span>
            </div>
            
            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="h-10 w-10 rounded-full border border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-white flex items-center justify-center">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </div>
</header>
