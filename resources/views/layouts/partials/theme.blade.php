<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="icon" type="image/png" href="{{ asset('Screenshot 2026-05-19 224605.png') }}">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --ui-ink: #0f172a;
        --ui-muted: #64748b;
        --ui-line: #e2e8f0;
        --ui-soft: #f8fafc;
        --ui-panel: rgba(255, 255, 255, 0.88);
        --ui-navy: #172a49;
        --ui-navy-2: #213a61;
        --ui-accent: #f7c814;
        --ui-accent-soft: #fff7cc;
        --ui-brand: #2563eb;
        --ui-success: #059669;
        --ui-danger: #dc2626;
        --ui-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        --ui-shadow-soft: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    html {
        scroll-behavior: smooth;
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    body {
        font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        color: var(--ui-ink);
        background:
            radial-gradient(circle at top left, rgba(247, 200, 20, 0.08), transparent 32rem),
            linear-gradient(135deg, #f8fafc 0%, #eef4fb 100%);
        text-rendering: optimizeLegibility;
        -webkit-font-smoothing: antialiased;
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    ::selection {
        background: rgba(247, 200, 20, 0.35);
        color: var(--ui-ink);
    }

    [x-cloak] {
        display: none !important;
    }

    .app-shell main {
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 28rem),
            linear-gradient(180deg, #f8fafc 0%, #eef4fb 100%);
    }

    .ui-sidebar {
        background:
            linear-gradient(180deg, rgba(33, 58, 97, 0.98) 0%, rgba(23, 42, 73, 1) 52%, rgba(15, 32, 59, 1) 100%);
        box-shadow: 18px 0 45px rgba(15, 23, 42, 0.16);
    }

    .ui-brand-mark {
        background: linear-gradient(135deg, #ffe45c 0%, var(--ui-accent) 100%);
        color: #16243b;
        box-shadow: 0 14px 34px rgba(247, 200, 20, 0.28);
    }

    .ui-nav-link {
        position: relative;
        margin: 0 0.75rem;
        border-radius: 0.875rem;
        color: rgba(255, 255, 255, 0.74);
        transition: color 180ms ease, background 180ms ease, transform 180ms ease;
    }

    .ui-nav-link:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
        transform: translateX(2px);
    }

    .ui-nav-link.is-active {
        background: linear-gradient(135deg, rgba(247, 200, 20, 0.18), rgba(255, 255, 255, 0.08));
        color: #ffffff;
        box-shadow: inset 3px 0 0 var(--ui-accent);
    }

    .ui-topbar {
        background: rgba(255, 255, 255, 0.82);
        border-color: rgba(226, 232, 240, 0.82);
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
        backdrop-filter: blur(18px);
    }

    /* Collapsed (icon-only) sidebar */
    .ui-sidebar.is-collapsed .sidebar-brand-text {
        display: none;
    }

    .ui-sidebar.is-collapsed .ui-nav-link {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    .ui-sidebar.is-collapsed .ui-nav-link i {
        margin-right: 0;
    }

    .ui-sidebar.is-collapsed .ui-nav-link > span {
        position: absolute;
        left: 100%;
        top: 50%;
        margin-left: 0.75rem;
        transform: translateY(-50%);
        white-space: nowrap;
        background: #0f1f38;
        color: #fff;
        padding: 0.45rem 0.85rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.28);
        opacity: 0;
        pointer-events: none;
        transition: opacity 120ms ease;
        z-index: 60;
    }

    .ui-sidebar.is-collapsed .ui-nav-link:hover > span {
        opacity: 1;
    }

    /* Compact table density */
    .table-density-compact table th,
    .table-density-compact table td {
        padding: 0.35rem 0.6rem !important;
        font-size: 0.78rem !important;
    }

    .table-density-compact table a[class*="h-8"],
    .table-density-compact table button[class*="h-8"] {
        height: 1.75rem !important;
        width: 1.75rem !important;
    }

    .table-density-compact .appliance-status-chip,
    .table-density-compact .status-chip {
        padding: 0.15rem 0.5rem !important;
        font-size: 0.7rem !important;
    }

    .bg-white.rounded-lg,
    .bg-white.rounded-md {
        background: var(--ui-panel) !important;
        border: 1px solid rgba(226, 232, 240, 0.78);
        box-shadow: var(--ui-shadow-soft) !important;
        backdrop-filter: blur(14px);
    }

    .rounded-lg {
        border-radius: 1rem !important;
    }

    .rounded-md {
        border-radius: 0.75rem !important;
    }

    h1, h2, h3 {
        letter-spacing: 0;
        color: var(--ui-ink);
    }

    main h1,
    main h2 {
        font-weight: 800;
    }

    label {
        color: #334155 !important;
    }

    input:not([type="checkbox"]):not([type="radio"]),
    select,
    textarea {
        min-height: 2.75rem;
        border-color: #dbe3ee !important;
        background: rgba(255, 255, 255, 0.92) !important;
        color: var(--ui-ink);
        border-radius: 0.75rem !important;
        transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease;
    }

    input:not([type="checkbox"]):not([type="radio"]):focus,
    select:focus,
    textarea:focus {
        border-color: rgba(37, 99, 235, 0.55) !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10) !important;
    }

    input[type="checkbox"] {
        border-radius: 0.35rem !important;
        border-color: #cbd5e1;
        color: var(--ui-brand);
    }

    button,
    a {
        transition: color 160ms ease, background-color 160ms ease, border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
    }

    .bg-blue-600,
    .bg-green-600 {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.18);
    }

    .bg-blue-600:hover,
    .hover\:bg-blue-700:hover,
    .bg-green-600:hover,
    .hover\:bg-green-700:hover {
        background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%) !important;
        transform: translateY(-1px);
        box-shadow: 0 16px 30px rgba(37, 99, 235, 0.24);
    }

    .bg-gray-500,
    .bg-gray-600 {
        background: #334155 !important;
    }

    .bg-gray-500:hover,
    .bg-gray-600:hover,
    .hover\:bg-gray-600:hover,
    .hover\:bg-gray-700:hover {
        background: #1e293b !important;
        transform: translateY(-1px);
    }

    table {
        border-collapse: separate;
        border-spacing: 0;
    }

    thead.bg-gray-50 {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%) !important;
    }

    th {
        color: #475569 !important;
        font-weight: 700 !important;
        letter-spacing: 0.02em !important;
    }

    td {
        color: #475569;
    }

    tbody tr {
        transition: background 150ms ease;
    }

    tbody tr:hover {
        background: rgba(248, 250, 252, 0.95) !important;
    }

    .divide-gray-200 > :not([hidden]) ~ :not([hidden]),
    .border-gray-200 {
        border-color: rgba(226, 232, 240, 0.82) !important;
    }

    .text-gray-900 {
        color: var(--ui-ink) !important;
    }

    .text-gray-600,
    .text-gray-500 {
        color: var(--ui-muted) !important;
    }

    .bg-gray-300 {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%) !important;
    }

    .toast {
        border-radius: 0.9rem !important;
        box-shadow: var(--ui-shadow) !important;
    }

    img,
    video,
    canvas,
    svg {
        max-width: 100%;
        height: auto;
    }

    main {
        min-width: 0;
        max-width: 100%;
    }

    main > * {
        min-width: 0;
        max-width: 100%;
    }

    .app-shell,
    .app-shell > *,
    .app-shell .flex-1,
    main .space-y-6,
    main form,
    main fieldset,
    main .grid,
    main .flex {
        min-width: 0;
    }

    main .bg-white.rounded-lg,
    main .bg-white.rounded-md,
    main .shadow,
    main .rounded-lg,
    main .rounded-md {
        max-width: 100%;
    }

    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        max-width: 100%;
        overscroll-behavior-x: contain;
    }

    .overflow-x-auto > table {
        width: 100%;
        min-width: 100%;
    }

    td,
    th {
        vertical-align: middle;
    }

    td {
        overflow-wrap: anywhere;
    }

    main a[class*="bg-"],
    main button[class*="bg-"],
    main a[class*="border"],
    main button[class*="border"] {
        min-height: 2.5rem;
    }

    .select2-container,
    .select2-selection,
    .select2-dropdown {
        max-width: 100% !important;
    }

    .select2-dropdown {
        z-index: 100000 !important;
    }

    .swal2-popup {
        max-width: calc(100vw - 1.5rem) !important;
    }

    @media (max-width: 1023px) {
        .ui-sidebar {
            box-shadow: 24px 0 60px rgba(15, 23, 42, 0.24);
        }
    }

    @media (max-width: 767px) {
        body {
            background: linear-gradient(180deg, #f8fafc 0%, #eef4fb 100%);
        }

        .app-shell main {
            background: linear-gradient(180deg, #f8fafc 0%, #eef4fb 100%);
        }

        main .space-y-6 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 1rem !important;
        }

        .bg-white.rounded-lg,
        .bg-white.rounded-md {
            border-radius: 0.85rem !important;
        }

        main .p-6 {
            padding: 1rem !important;
        }

        main .px-6 {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        main .py-4 {
            padding-top: 0.85rem !important;
            padding-bottom: 0.85rem !important;
        }

        main .mb-6 {
            margin-bottom: 1rem !important;
        }

        main .text-3xl {
            font-size: 1.65rem !important;
            line-height: 2rem !important;
        }

        main .text-2xl {
            font-size: 1.35rem !important;
            line-height: 1.8rem !important;
        }

        main .text-xl {
            font-size: 1.15rem !important;
            line-height: 1.65rem !important;
        }

        main .flex.items-center.justify-between,
        main .flex.justify-between.items-center {
            align-items: stretch !important;
            flex-direction: column !important;
            gap: 0.75rem !important;
        }

        main .flex.justify-end,
        main .flex.items-end,
        main .flex.gap-2,
        main .flex.gap-3,
        main .flex.gap-6 {
            flex-wrap: wrap;
        }

        main form .flex.items-end > *,
        main form .flex.justify-end > *,
        main form .flex.gap-2 > *,
        main form .flex.gap-3 > * {
            flex: 1 1 10rem;
        }

        main form[class*="grid"],
        main .grid[class*="md:grid-cols"],
        main .grid[class*="lg:grid-cols"],
        main .grid[class*="xl:grid-cols"] {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        main form[class*="grid"] > *,
        main .grid[class*="md:grid-cols"] > *,
        main .grid[class*="lg:grid-cols"] > *,
        main .grid[class*="xl:grid-cols"] > * {
            grid-column: auto / span 1 !important;
            min-width: 0;
        }

        main input:not([type="checkbox"]):not([type="radio"]),
        main select,
        main textarea {
            width: 100%;
            max-width: 100%;
        }

        main button,
        main a {
            white-space: normal;
        }

        main a[class*="bg-"],
        main button[class*="bg-"],
        main a[class*="border"],
        main button[class*="border"] {
            justify-content: center;
            text-align: center;
        }

        table {
            font-size: 0.82rem;
        }

        .overflow-x-auto > table {
            min-width: max-content;
        }

        th,
        td {
            padding: 0.7rem 0.85rem !important;
        }

        td:last-child,
        th:last-child {
            white-space: normal !important;
        }

        .toast-top-right {
            top: 0.75rem !important;
            right: 0.75rem !important;
            left: 0.75rem !important;
            width: auto !important;
        }
    }

    @media (max-width: 480px) {
        main .grid {
            gap: 0.85rem !important;
        }

        main .rounded-lg,
        main .rounded-md {
            border-radius: 0.75rem !important;
        }

        main .inline-flex,
        main .flex {
            min-width: 0;
        }

        main .flex-wrap > a[class*="bg-"],
        main .flex-wrap > button[class*="bg-"],
        main .flex-wrap > form,
        main .flex-wrap > label {
            flex: 1 1 100%;
        }

        main .flex-wrap > form > button,
        main .flex-wrap > form > a,
        main .flex-wrap > a[class*="bg-"],
        main .flex-wrap > button[class*="bg-"] {
            width: 100%;
        }

        main .space-x-2 > :not([hidden]) ~ :not([hidden]),
        main .space-x-3 > :not([hidden]) ~ :not([hidden]) {
            margin-left: 0.25rem !important;
        }

        main .text-right {
            text-align: left;
        }

        main td.text-right,
        main th.text-right {
            text-align: right;
        }
    }
</style>
