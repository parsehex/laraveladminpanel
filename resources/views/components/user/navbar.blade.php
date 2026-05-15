<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-6 py-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
        </div>
        
        <div class="flex items-center space-x-4">
            <!-- User Info -->
            <div class="flex items-center space-x-2 text-gray-700">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white">
                    <i class="fas fa-user"></i>
                </div>
                <span>{{ auth()->user()->name }}</span>
            </div>
            
            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </div>
</header>