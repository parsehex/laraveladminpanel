@php
    $year = now()->year;
@endphp

<footer class="shrink-0 border-t border-slate-800 bg-slate-950 shadow-[0_-14px_34px_rgba(15,23,42,0.08)]">
    <div class="flex flex-col gap-2 px-5 py-3 text-xs text-slate-300 sm:flex-row sm:items-center sm:justify-between lg:px-8">
        <p>&copy; {{ $year }} Ben's Appliances. All rights reserved.</p>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <span>Admin Panel</span>
        </div>
    </div>
</footer>
