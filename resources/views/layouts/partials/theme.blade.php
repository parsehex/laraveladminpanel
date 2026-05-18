<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
    }

    body {
        font-family: Inter, ui-sans-serif, system-ui, sans-serif;
        color: var(--ui-ink);
        background:
            radial-gradient(circle at top left, rgba(247, 200, 20, 0.08), transparent 32rem),
            linear-gradient(135deg, #f8fafc 0%, #eef4fb 100%);
        text-rendering: optimizeLegibility;
        -webkit-font-smoothing: antialiased;
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
</style>
