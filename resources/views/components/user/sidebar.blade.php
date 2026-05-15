<div class="bg-blue-800 text-white w-64 flex-shrink-0">
    <div class="flex items-center justify-center h-16 bg-blue-900">
        <h1 class="text-xl font-bold">User Panel</h1>
    </div>
    
    <nav class="mt-8">
        <a href="{{ route('user.dashboard') }}" 
           class="flex items-center px-6 py-3 text-blue-200 hover:bg-blue-700 hover:text-white {{ request()->routeIs('user.dashboard') ? 'bg-blue-700 text-white border-r-4 border-blue-300' : '' }}">
            <i class="fas fa-tachometer-alt mr-3"></i>
            Dashboard
        </a>
            {{-- {{ dd(request()->route()) }} --}}
        <a href="{{ route("user.account.edit",['account' => encrypt(auth()->user()->id)]) }}" 
           class="flex items-center px-6 py-3 text-blue-200 hover:bg-blue-700 hover:text-white {{ request()->is('user/account/*') ? 'bg-blue-700 text-white border-r-4 border-blue-300' : '' }}">
            <i class="fas fa-user mr-3"></i>
            Profile
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