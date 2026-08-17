<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard')</title>
    <script>
        // Apply persisted UI prefs before render to avoid a flash of the wrong layout.
        if (localStorage.getItem('tableDensity') === 'compact') {
            document.documentElement.classList.add('table-density-compact');
        }
        if (localStorage.getItem('sidebarCollapsed') === '1') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    @include('layouts.partials.theme')
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .sticky-action {
            position: sticky;
            right: 0;
            z-index: 20;
            background-color: #fff;
            background-clip: padding-box;
            box-shadow: -8px 0 12px -12px rgba(15, 23, 42, 0.45);
        }

        thead .sticky-action {
            z-index: 30;
            background-color: #f9fafb;
        }

        .overflow-x-auto table th:last-child,
        .overflow-x-auto table td:last-child {
            position: sticky;
            right: 0;
            z-index: 15;
            background-color: #fff;
            background-clip: padding-box;
            box-shadow: -8px 0 12px -12px rgba(15, 23, 42, 0.45);
        }

        .overflow-x-auto table thead th:last-child {
            z-index: 25;
            background-color: #f9fafb;
        }

        @media (max-width: 640px) {
            main {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            .overflow-x-auto table th:last-child,
            .overflow-x-auto table td:last-child,
            .sticky-action {
                min-width: 108px;
                max-width: 132px;
            }

            .overflow-x-auto table td:last-child a,
            .overflow-x-auto table td:last-child button,
            .sticky-action a,
            .sticky-action button {
                margin-bottom: 0.25rem;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-100">
    <div x-data="{ sidebarOpen: false, sidebarCollapsed: document.documentElement.classList.contains('sidebar-collapsed') }"
         x-effect="document.body.classList.toggle('overflow-hidden', sidebarOpen); document.documentElement.classList.toggle('sidebar-collapsed', sidebarCollapsed); localStorage.setItem('sidebarCollapsed', sidebarCollapsed ? '1' : '0')"
         class="flex h-screen flex-col overflow-hidden">
        <div class="app-shell flex min-h-0 flex-1 overflow-hidden">
            <div x-cloak x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/55 backdrop-blur-sm lg:hidden"></div>

            <!-- Sidebar -->
            <x-admin.sidebar />

            <!-- Main Content -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Navbar -->
                <x-admin.navbar />

                <!-- Page Content -->
                <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 sm:p-6 lg:p-8">
                    @yield('content')
                </main>
            </div>
        </div>
        <x-admin.footer />
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // Shared table density control, used in the admin navbar
        window.applyTableDensity = function (mode) {
            document.documentElement.classList.toggle('table-density-compact', mode === 'compact');
        };

        window.setTableDensity = function (mode) {
            applyTableDensity(mode);
            localStorage.setItem('tableDensity', mode);
        };

        window.addEventListener('storage', function (event) {
            if (event.key !== 'tableDensity') {
                return;
            }

            applyTableDensity(event.newValue === 'compact' ? 'compact' : 'comfortable');
        });
    </script>
    <script>
        document.querySelectorAll('.caps').forEach(input => {
        // While typing
        input.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });

        // When leaving field / saving pasted text
        input.addEventListener('change', function () {
            this.value = this.value.toUpperCase();
        });

        });
    </script>
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

        @if(session('warning'))
            toastr.warning('{{ session('warning') }}');
        @endif

        @if(session('info'))
            toastr.info('{{ session('info') }}');
        @endif

        // Display validation errors
        @if($errors->any())
            @foreach($errors->all() as $error)
                toastr.error('{{ $error }}');
            @endforeach
        @endif
    </script>

    @stack('scripts')
</body>
</html>
