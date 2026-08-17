<div class="hidden md:flex flex-col rounded-xl border border-slate-200 bg-white/80 px-3 py-2 shadow-sm"
     x-data="{ density: localStorage.getItem('tableDensity') === 'compact' ? 'compact' : 'comfortable' }"
     x-init="setTableDensity(density)"
     @storage.window="if ($event.key === 'tableDensity') { density = $event.newValue === 'compact' ? 'compact' : 'comfortable' }">
    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Table Density</span>
    <div class="mt-0.5 inline-flex rounded-md border border-slate-200 bg-slate-100 p-0.5">
        <button type="button"
                @click="density = 'comfortable'; setTableDensity('comfortable')"
                :class="density === 'comfortable' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:bg-white/60'"
                class="rounded px-2.5 py-1 text-xs font-semibold">
            <i class="fas fa-bars mr-1"></i>Comfortable
        </button>
        <button type="button"
                @click="density = 'compact'; setTableDensity('compact')"
                :class="density === 'compact' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:bg-white/60'"
                class="rounded px-2.5 py-1 text-xs font-semibold">
            <i class="fas fa-minus mr-1"></i>Compact
        </button>
    </div>
</div>
