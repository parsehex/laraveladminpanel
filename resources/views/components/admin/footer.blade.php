@php
    $year = now()->year;
    $user = auth()->user();
@endphp

<footer class="shrink-0 border-t border-slate-200/80 bg-white/90 shadow-[0_-14px_34px_rgba(15,23,42,0.08)] backdrop-blur">
    <div class="w-full">
        <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-amber-300 font-extrabold text-slate-900 shadow-sm">
                    B
                </div>
                <div>
                    <p class="text-sm font-extrabold text-slate-900">Ben's Appliances</p>
                    <p class="mt-1 text-sm text-slate-500">Inventory, truck, parts, and sales operations dashboard.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3 lg:min-w-[520px]">
                <div class="rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Signed In</p>
                    <p class="mt-1 truncate font-semibold text-slate-800">{{ $user?->name ?? 'Staff' }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Workspace</p>
                    <p class="mt-1 font-semibold text-slate-800">@yield('page-title', 'Dashboard')</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Updated</p>
                    <p class="mt-1 font-semibold text-slate-800">{{ now()->format('M d, Y') }}</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-2 border-t border-slate-200/80 bg-slate-950 px-5 py-3 text-xs text-slate-300 sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <p>&copy; {{ $year }} Ben's Appliances. All rights reserved.</p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <span>Laravel Admin Panel</span>
                <span class="hidden text-slate-600 sm:inline">|</span>
                <span>Secure role-based access</span>
            </div>
        </div>
    </div>
</footer>
