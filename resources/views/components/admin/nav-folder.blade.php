@props([
    'id',
    'label',
    'icon',
    'href' => null,
    'active' => false,
    'linkActive' => false,
])

<div class="ui-nav-folder"
     data-folder="{{ $id }}"
     x-data="sidebarFolder(@js($id))">
    <div class="ui-nav-link ui-nav-folder-toggle ui-nav-folder-row flex items-center pr-1 text-sm font-semibold {{ $active ? 'is-active' : '' }}">
        @if ($href)
            <a href="{{ $href }}"
               @click="sidebarOpen = false"
               class="ui-nav-folder-label flex min-w-0 flex-1 items-center py-3 pl-4 {{ $linkActive ? 'is-active' : '' }}">
                <i class="fas {{ $icon }} mr-3 w-5 text-center"></i>
                <span>{{ $label }}</span>
            </a>
        @else
            <button type="button"
                    @click="toggle()"
                    class="ui-nav-folder-label flex min-w-0 flex-1 items-center py-3 pl-4 text-left"
                    :aria-expanded="open.toString()">
                <i class="fas {{ $icon }} mr-3 w-5 text-center"></i>
                <span>{{ $label }}</span>
            </button>
        @endif
        <button type="button"
                @click="toggle()"
                class="ui-nav-folder-chevron-btn flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                :aria-expanded="open.toString()"
                aria-controls="sidebar-folder-{{ $id }}-children"
                aria-label="Toggle {{ $label }}">
            <i class="ui-nav-folder-chevron fas fa-chevron-down text-xs opacity-70 transition-transform duration-200"></i>
        </button>
    </div>

    <div id="sidebar-folder-{{ $id }}-children" class="ui-nav-folder-children space-y-1 pb-1">
        {{ $slot }}
    </div>
</div>
