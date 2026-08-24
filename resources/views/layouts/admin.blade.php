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
        if (localStorage.getItem('sidebarFolder.manage') === '1') {
            document.documentElement.classList.add('sidebar-folder-manage-open');
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

        .wide-table-shell {
            position: relative;
            z-index: 1;
        }

        .wide-table-scroll {
            max-height: 72vh;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .wide-table-scroll > table {
            width: max-content;
            min-width: 100%;
        }

        .wide-table-shell.has-h-scroll .wide-table-scroll {
            overflow-x: auto;
        }

        .wide-table-top-scroll {
            display: none;
            height: 16px;
            overflow-x: auto;
            overflow-y: hidden;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            background: #f8fafc;
        }

        .wide-table-shell.has-h-scroll .wide-table-top-scroll {
            display: block;
        }

        .wide-table-top-scroll > div {
            height: 1px;
        }

        .sticky-table-head th {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #f8fafc !important;
            box-shadow: inset 0 -1px 0 rgba(226, 232, 240, 0.95);
        }

        /* Keep expandable row editors/views within the visible table viewport */
        [data-table-inline-panel] {
            position: sticky;
            left: 0;
            box-sizing: border-box;
            max-width: 100%;
            z-index: 5;
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

    <x-admin.suggestion-fab />

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

        window.adminDataTable = function (storageKey, columnConfig) {
            if (!storageKey || !columnConfig.length) {
                return {
                    init() {},
                    isColumnVisible() {
                        return true;
                    },
                    showAllColumns() {},
                    hideAllColumns() {},
                    resetColumns() {},
                };
            }

            const buildDefaults = () => Object.fromEntries(
                columnConfig.map(({ key, default: isOn }) => [key, isOn !== false])
            );

            return {
                visible: {},
                init() {
                    this.applyStoredColumns(localStorage.getItem(storageKey));

                    this.$watch('visible', (value) => {
                        localStorage.setItem(storageKey, JSON.stringify(value));
                        window.dispatchEvent(new CustomEvent('wide-table-resync'));
                    }, { deep: true });

                    window.addEventListener('storage', (event) => {
                        if (event.key !== storageKey) {
                            return;
                        }

                        this.applyStoredColumns(event.newValue);
                        window.dispatchEvent(new CustomEvent('wide-table-resync'));
                    });
                },
                applyStoredColumns(storedValue) {
                    const defaults = buildDefaults();

                    if (storedValue === null) {
                        this.visible = defaults;

                        return;
                    }

                    try {
                        this.visible = { ...defaults, ...JSON.parse(storedValue) };
                    } catch (error) {
                        this.visible = defaults;
                    }
                },
                isColumnVisible(key) {
                    return this.visible[key] !== false;
                },
                showAllColumns() {
                    this.visible = Object.fromEntries(
                        columnConfig.map(({ key }) => [key, true])
                    );
                },
                hideAllColumns() {
                    this.visible = Object.fromEntries(
                        columnConfig.map(({ key }) => [key, false])
                    );
                },
                resetColumns() {
                    localStorage.removeItem(storageKey);
                    this.visible = buildDefaults();
                },
            };
        };

        window.syncTableInlinePanels = function (root) {
            const scope = root || document;

            $(scope).find('[data-table-inline-panel]').each(function () {
                const scrollParent = this.closest('[data-wide-table-scroll], .overflow-x-auto');

                if (!scrollParent) {
                    this.style.width = '';
                    return;
                }

                this.style.width = scrollParent.clientWidth + 'px';
            });
        };

        window.initWideTables = function () {
            $('[data-wide-table]').each(function () {
                const $shell = $(this);

                if ($shell.data('wideTableInit')) {
                    return;
                }

                $shell.data('wideTableInit', true);

                const $main = $shell.find('[data-wide-table-scroll]');
                const $top = $shell.find('[data-wide-table-top-scroll]');
                const mainEl = $main.get(0);
                const table = $main.find('table').get(0);
                let frame = null;

                function syncWidth() {
                    if (!mainEl || !table) {
                        return;
                    }

                    $top.children().first().width(table.scrollWidth);

                    const yScrollbar = Math.max(0, mainEl.offsetWidth - mainEl.clientWidth);
                    const overflowX = table.scrollWidth - mainEl.clientWidth;
                    const needsScroll = overflowX > yScrollbar + 2;

                    if ($shell.hasClass('has-h-scroll') !== needsScroll) {
                        $shell.toggleClass('has-h-scroll', needsScroll);
                    }

                    syncTableInlinePanels($shell.get(0));
                }

                function scheduleSync() {
                    if (frame) {
                        return;
                    }

                    frame = requestAnimationFrame(function () {
                        frame = null;
                        syncWidth();
                    });
                }

                $main.on('scroll', function () {
                    $top.scrollLeft($main.scrollLeft());
                });

                $top.on('scroll', function () {
                    $main.scrollLeft($top.scrollLeft());
                });

                syncWidth();
                $(window).on('resize', scheduleSync);

                if (window.ResizeObserver && mainEl && table) {
                    const observer = new ResizeObserver(scheduleSync);
                    observer.observe(mainEl);
                    observer.observe(table);
                }

                $shell.data('wideTableSync', scheduleSync);
            });
        };

        $(document).ready(function () {
            initWideTables();
            syncTableInlinePanels();
            $(window).on('resize', function () {
                syncTableInlinePanels();
            });
        });

        window.addEventListener('wide-table-resync', function () {
            $('[data-wide-table]').each(function () {
                const sync = $(this).data('wideTableSync');
                if (typeof sync === 'function') {
                    sync();
                }
            });
            syncTableInlinePanels();
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
