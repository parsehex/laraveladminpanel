<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-6 py-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
        </div>
        
        <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <div class="relative">
                <!-- <button class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-bell text-xl"></i>
                </button> -->
            </div>
            
            <!-- User Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                        <i class="fas fa-user"></i>
                    </div>
                    <span>{{ auth()->user()->name }}</span>
                    <i class="fas fa-chevron-down text-sm"></i>
                </button>
                
                <div x-show="open" @click.away="open = false" 
                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                    <!-- <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-user mr-2"></i>Profile
                    </a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-cog mr-2"></i>Settings
                    </a> -->
                    <div class="border-t border-gray-100"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>