<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Admin Dashboard</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    @include('layouts.partials.theme')
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10" style="background: radial-gradient(circle at 50% 0%, rgba(247, 200, 20, 0.16), transparent 24rem), linear-gradient(135deg, #172a49 0%, #213a61 48%, #0f203b 100%);">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center text-white">
            <div class="ui-brand-mark h-20 w-20 rounded-3xl flex items-center justify-center mx-auto mb-6 font-extrabold text-3xl">B</div>
            <h1 class="text-4xl font-extrabold tracking-tight text-white">Ben's Appliances</h1>
        </div>

        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-extrabold text-gray-900">Welcome Back</h2>
                <p class="mt-2 text-sm text-gray-600">Sign in to your account to continue</p>
            </div>
            
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf
                
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}"
                               class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                               placeholder="Enter your email"
                               required>
                        <i class="fas fa-envelope absolute left-3 top-3.5 text-gray-400"></i>
                    </div>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password"
                               class="w-full px-4 py-3 pl-10 pr-12 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror"
                               placeholder="Enter your password"
                               required>
                        <i class="fas fa-lock absolute left-3 top-3.5 text-gray-400"></i>
                        <button type="button" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 hover:text-gray-700" data-toggle-login-password aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="remember" 
                               name="remember" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm font-medium text-gray-700">
                            Remember me
                        </label>
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-md focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition duration-150 ease-in-out">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Sign In
                </button>
            </form>
<!--
            <div class="mt-6 text-center rounded-md border border-slate-200 bg-slate-50/80 p-4">
                <p class="text-xs leading-5 text-gray-600">
                    Demo Credentials:<br>
                    <strong>Admin:</strong> admin@example.com / password<br>
                    <strong>User:</strong> user@example.com / password
                </p>
            </div> -->
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <script>
        // Configure Toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };
        
        // Display flash messages
        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
        
        @if(session('error'))
            toastr.error('{{ session('error') }}');
        @endif
        
        @if($errors->any())
            @foreach($errors->all() as $error)
                toastr.error('{{ $error }}');
            @endforeach
        @endif

        $('[data-toggle-login-password]').on('click', function () {
            const input = document.getElementById('password');
            const icon = $(this).find('i');
            const showing = input.type === 'text';

            input.type = showing ? 'password' : 'text';
            icon.toggleClass('fa-eye', showing).toggleClass('fa-eye-slash', !showing);
            $(this).attr('aria-label', showing ? 'Show password' : 'Hide password');
        });
    </script>
</body>
</html>
