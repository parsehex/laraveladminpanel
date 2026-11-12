@props([
    'name' => 'status',
    'options' => [],
    'value' => '',
    'label' => null,
    'placeholder' => 'Select status',
    'required' => false,
    'id' => null,
    'allowEmpty' => true,
    'disabled' => false,
])

@php
    $id = $id ?: $name;
    $optionList = collect($options)->map(fn ($option) => (string) $option)->filter()->values()->all();
    $palette = \App\Support\InventoryStatusAppearance::palette();
    $initialValue = old($name, $value);
    $initialValue = filled($initialValue) ? (string) $initialValue : '';
@endphp

<div
    data-status-select="{{ $id }}"
    @if($disabled) disabled @endif
    x-data="{
        open: false,
        value: @js($initialValue),
        options: @js($optionList),
        disabled: @js((bool) $disabled),
        palette: @js($palette['colors']),
        defaultColors: @js($palette['default']),
        init() {
            const syncDisabled = () => {
                this.disabled = this.$el.hasAttribute('disabled')
                    || this.$el.getAttribute('data-disabled') === 'true';
            };

            syncDisabled();
            new MutationObserver(syncDisabled).observe(this.$el, {
                attributes: true,
                attributeFilter: ['disabled', 'data-disabled'],
            });
        },
        chipStyle(status) {
            const key = String(status || '').trim().toLowerCase() || 'triage';
            const colors = this.palette[key] || this.defaultColors;

            return {
                backgroundColor: colors.background,
                color: colors.text,
            };
        },
        select(status) {
            if (this.disabled) {
                return;
            }

            this.value = status;
            this.open = false;
            this.$nextTick(() => this.$refs.input?.dispatchEvent(new Event('change', { bubbles: true })));
        },
        setOptions(list, selected = null) {
            this.options = Array.isArray(list) ? list.map(String) : [];
            if (selected !== null && selected !== undefined) {
                this.value = String(selected);
            } else if (this.value !== '' && ! this.options.includes(this.value)) {
                this.value = '';
            }
            this.$nextTick(() => this.$refs.input?.dispatchEvent(new Event('change', { bubbles: true })));
        },
        setValue(status) {
            this.select(status == null ? '' : String(status));
        },
    }"
    x-modelable="value"
    @status-select-set.window="
        if ($event.detail?.id === @js($id)) {
            if (Array.isArray($event.detail.options)) {
                setOptions($event.detail.options, $event.detail.value ?? null);
            } else if ($event.detail?.value !== undefined) {
                setValue($event.detail.value);
            }
        }
    "
    {{ $attributes->class(['relative']) }}
>
    @if(filled($label))
        <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-gray-700">{{ $label }}</label>
    @endif

    <input
        type="hidden"
        name="{{ $name }}"
        id="{{ $id }}"
        x-ref="input"
        x-model="value"
        :disabled="disabled"
        @if($required) required @endif
    >

    <button
        type="button"
        @click="if (! disabled) open = !open"
        :disabled="disabled"
        :aria-expanded="open.toString()"
        class="flex w-full min-h-[42px] items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm text-gray-800 shadow-sm hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:opacity-60"
    >
        <span class="min-w-0">
            <template x-if="value">
                <span
                    class="inline-flex items-center whitespace-nowrap rounded-full border border-slate-900/20 px-2.5 py-1 text-xs font-extrabold leading-none"
                    :style="chipStyle(value)"
                    x-text="value"
                ></span>
            </template>
            <template x-if="! value">
                <span class="truncate text-gray-500" x-text="@js($placeholder)"></span>
            </template>
        </span>
        <i class="fas fa-chevron-down shrink-0 text-xs text-blue-600"></i>
    </button>

    <div
        x-cloak
        x-show="open && ! disabled"
        @click.outside="open = false"
        class="absolute top-[calc(100%+0.5rem)] left-0 z-[100001] w-full min-w-[12rem] max-w-[calc(100vw-2rem)] rounded-lg border border-gray-200 bg-white p-2 shadow-2xl ring-1 ring-black/5 max-h-72 overflow-y-auto"
    >
        @if($allowEmpty)
            <button
                type="button"
                class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm text-gray-500 hover:bg-blue-50"
                @click="select('')"
            >
                {{ $placeholder }}
            </button>
        @endif

        <template x-for="option in options" :key="option">
            <button
                type="button"
                class="flex w-full items-center rounded-md px-3 py-2 text-left hover:bg-blue-50"
                @click="select(option)"
            >
                <span
                    class="inline-flex items-center whitespace-nowrap rounded-full border border-slate-900/20 px-2.5 py-1 text-xs font-extrabold leading-none"
                    :style="chipStyle(option)"
                    x-text="option"
                ></span>
            </button>
        </template>
    </div>
</div>
