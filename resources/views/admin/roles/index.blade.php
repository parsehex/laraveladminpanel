@extends('layouts.admin')

@section('title', 'Roles')
@section('page-title', 'Roles & permissions')

@section('page-actions')
    @canAccess('roles.create')
    <button type="button" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700" data-toggle-create>
        <i class="fas fa-plus mr-2"></i>New role
    </button>
    @endcanAccess
@endsection

@push('styles')
<style>
    th.role-module-th {
        position: relative;
        height: 8rem;
        width: 2.35rem;
        min-width: 2.35rem;
        max-width: 2.35rem;
        padding: 0.5rem 0.15rem 0.4rem;
        vertical-align: bottom;
        text-align: left;
        overflow: visible;
    }

    th.role-module-th .role-module-label {
        display: inline-block;
        max-width: 7.25rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        transform: translate(0.6rem, -0.1rem) rotate(-45deg);
        transform-origin: bottom left;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        text-transform: none;
        color: #64748b;
        line-height: 1.1;
        cursor: default;
    }

    /* Portaled to body in JS so sticky sibling headers cannot clip the flyout. */
    .role-module-flyout {
        position: fixed;
        z-index: 9999;
    }

    .role-module-flyout.is-open {
        opacity: 1;
        visibility: visible;
    }
</style>
@endpush

@section('content')
@php
    $canEditRoles = canAccess('roles.edit');
    $canCreateRoles = canAccess('roles.create');
    $showCreateRow = $errors->any() && old('_form') === 'create';
@endphp

<div class="space-y-4">
    <p class="text-sm text-slate-600">
        Each column is a module. Checking it grants every permission in that module for the role.
    </p>

    <x-admin.data-table id="roles-results" :table="$dataTable">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 sticky-table-head">
                <tr>
                    <x-admin.data-table.th column="name" label="Role" />
                    <x-admin.data-table.th column="description" label="Description" />
                    <x-admin.data-table.th column="all" label="All" align="center" />
                    @foreach($permissionModules as $module => $items)
                        @php
                            $moduleColumnKey = 'module-'.\Illuminate\Support\Str::slug((string) $module);
                            $moduleLabel = \Illuminate\Support\Str::headline((string) $module);
                        @endphp
                        <th data-col="{{ $moduleColumnKey }}"
                            tabindex="0"
                            aria-label="{{ $moduleLabel }}"
                            :class="{ 'hidden': !isColumnVisible('{{ $moduleColumnKey }}') }"
                            class="role-module-th px-1 py-2 text-xs font-medium text-gray-500">
                            <span class="role-module-label">{{ $moduleLabel }}</span>
                        </th>
                    @endforeach
                    <x-admin.data-table.th column="actions" label="Actions" align="right" />
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @if($canCreateRoles)
                <tr id="create-role-row"
                    data-role-permissions
                    class="{{ $showCreateRow ? 'bg-blue-50/60' : 'hidden bg-blue-50/60' }}">
                    <x-admin.data-table.cell column="name">
                        <form id="role-form-create" method="POST" action="{{ route('admin.roles.store') }}">
                            @csrf
                            <input type="hidden" name="_form" value="create">
                            <input type="hidden" name="guard_name" value="web">
                        </form>
                        <input type="text"
                               form="role-form-create"
                               name="name"
                               value="{{ old('_form') === 'create' ? old('name') : '' }}"
                               required
                               placeholder="Role name"
                               class="w-full min-w-[9rem] rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </x-admin.data-table.cell>
                    <x-admin.data-table.cell column="description">
                        <input type="text"
                               form="role-form-create"
                               name="description"
                               value="{{ old('_form') === 'create' ? old('description') : '' }}"
                               placeholder="Optional description"
                               class="w-full min-w-[10rem] rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </x-admin.data-table.cell>
                    <x-admin.data-table.cell column="all" align="center">
                        <label class="inline-flex cursor-pointer items-center justify-center" title="Select all modules">
                            <input type="checkbox" class="js-select-all-permissions h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </label>
                    </x-admin.data-table.cell>
                    @foreach($permissionModules as $module => $items)
                        <x-admin.data-table.cell :column="'module-'.\Illuminate\Support\Str::slug((string) $module)" align="center">
                            @include('admin.roles.partials.module-checkbox', [
                                'formId' => 'role-form-create',
                                'items' => $items,
                                'selected' => old('_form') === 'create' ? old('permissions', []) : [],
                            ])
                        </x-admin.data-table.cell>
                    @endforeach
                    <x-admin.data-table.cell column="actions" align="right">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" class="rounded-md bg-gray-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-gray-600" data-toggle-create>Cancel</button>
                            <button type="submit" form="role-form-create" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700">
                                Create
                            </button>
                        </div>
                    </x-admin.data-table.cell>
                </tr>
                @endif

                @forelse($roles as $role)
                    @php
                        $formId = 'role-form-'.$role->id;
                        $isProtected = in_array($role->name, $protectedRoleNames, true);
                        $selected = old('_form') === 'edit-'.$role->id
                            ? old('permissions', $role->permissions->pluck('name')->all())
                            : $role->permissions->pluck('name')->all();
                    @endphp
                    <tr data-role-permissions class="hover:bg-gray-50 {{ old('_form') === 'edit-'.$role->id && $errors->any() ? 'bg-red-50/40' : '' }}">
                        <x-admin.data-table.cell column="name" class="font-medium text-gray-900">
                            @if($canEditRoles)
                                <form id="{{ $formId }}" method="POST" action="{{ route('admin.roles.update', $role) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="_form" value="edit-{{ $role->id }}">
                                    <input type="hidden" name="guard_name" value="web">
                                </form>
                                <input type="text"
                                       form="{{ $formId }}"
                                       name="name"
                                       value="{{ old('_form') === 'edit-'.$role->id ? old('name', $role->name) : $role->name }}"
                                       required
                                       @readonly($isProtected)
                                       class="w-full min-w-[9rem] rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 {{ $isProtected ? 'bg-gray-50 text-gray-700' : '' }}">
                            @else
                                <span>{{ $role->name }}</span>
                            @endif
                            <p class="mt-1 text-xs font-normal text-gray-500">{{ $role->users_count }} {{ \Illuminate\Support\Str::plural('user', $role->users_count) }}</p>
                        </x-admin.data-table.cell>
                        <x-admin.data-table.cell column="description">
                            @if($canEditRoles)
                                <input type="text"
                                       form="{{ $formId }}"
                                       name="description"
                                       value="{{ old('_form') === 'edit-'.$role->id ? old('description', $role->description) : $role->description }}"
                                       class="w-full min-w-[10rem] rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            @else
                                <span>{{ $role->description ?: '—' }}</span>
                            @endif
                        </x-admin.data-table.cell>
                        <x-admin.data-table.cell column="all" align="center">
                            @if($canEditRoles)
                                <label class="inline-flex cursor-pointer items-center justify-center" title="Select all modules">
                                    <input type="checkbox" class="js-select-all-permissions h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </label>
                            @endif
                        </x-admin.data-table.cell>
                        @foreach($permissionModules as $module => $items)
                            <x-admin.data-table.cell :column="'module-'.\Illuminate\Support\Str::slug((string) $module)" align="center">
                                @include('admin.roles.partials.module-checkbox', [
                                    'formId' => $formId,
                                    'items' => $items,
                                    'selected' => $selected,
                                    'disabled' => ! $canEditRoles,
                                ])
                            </x-admin.data-table.cell>
                        @endforeach
                        <x-admin.data-table.cell column="actions" align="right">
                            <div class="flex items-center justify-end gap-2">
                                @canAccess('roles.edit')
                                <button type="submit" form="{{ $formId }}" class="text-green-600 hover:text-green-900" title="Save">
                                    <i class="fas fa-save"></i>
                                </button>
                                @endcanAccess
                                @canAccess('roles.delete')
                                @unless($isProtected)
                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline" onsubmit="return confirm('Delete the {{ $role->name }} role?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endunless
                                @endcanAccess
                            </div>
                        </x-admin.data-table.cell>
                    </tr>
                @empty
                    @unless($canCreateRoles)
                    <tr>
                        <td colspan="{{ 4 + $permissionModules->count() }}" class="px-6 py-8 text-center text-gray-500">No roles found.</td>
                    </tr>
                    @endunless
                @endforelse
            </tbody>
        </table>
    </x-admin.data-table>
</div>
@endsection

@push('scripts')
<script>
    function rolePermissionRoot(element) {
        return element.closest('[data-role-permissions]');
    }

    function modulePermissionInputs(checkbox) {
        return checkbox.closest('label')?.querySelectorAll('.js-module-permission-values input') ?? [];
    }

    function syncModulePermissions(checkbox) {
        modulePermissionInputs(checkbox).forEach((input) => {
            input.disabled = checkbox.disabled || ! checkbox.checked;
        });
        updateRoleSelectAll(rolePermissionRoot(checkbox));
    }

    function updateRoleSelectAll(root) {
        if (! root) {
            return;
        }

        const selectAll = root.querySelector('.js-select-all-permissions');
        const boxes = [...root.querySelectorAll('.js-module-permission')];

        if (! selectAll || boxes.length === 0) {
            return;
        }

        selectAll.checked = boxes.every((checkbox) => checkbox.checked);
        selectAll.indeterminate = boxes.some((checkbox) => checkbox.checked) && ! selectAll.checked;
    }

    document.addEventListener('change', function (event) {
        const target = event.target;

        if (! (target instanceof HTMLInputElement)) {
            return;
        }

        if (target.matches('.js-module-permission')) {
            syncModulePermissions(target);
        }

        if (target.matches('.js-select-all-permissions')) {
            const root = rolePermissionRoot(target);
            const shouldCheck = target.checked;

            root?.querySelectorAll('.js-module-permission').forEach((checkbox) => {
                if (checkbox.disabled) {
                    return;
                }

                checkbox.checked = shouldCheck;
                syncModulePermissions(checkbox);
            });

            target.checked = shouldCheck;
            target.indeterminate = false;
        }
    });

    document.querySelectorAll('.js-module-permission').forEach(syncModulePermissions);

    $('[data-toggle-create]').on('click', function () {
        const $row = $('#create-role-row').toggleClass('hidden');

        if (! $row.hasClass('hidden')) {
            $row[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            $row.find('input[name="name"]').trigger('focus');
        }
    });

    (function () {
        const flyout = document.createElement('div');
        flyout.className = 'ui-flyout-tooltip role-module-flyout';
        flyout.setAttribute('role', 'tooltip');
        document.body.appendChild(flyout);

        let activeHeader = null;

        function hideRoleModuleTooltip() {
            flyout.classList.remove('is-open');
            flyout.textContent = '';
            activeHeader = null;
        }

        function placeRoleModuleTooltip(th) {
            const label = th.getAttribute('aria-label');

            if (! label) {
                return;
            }

            const rect = th.getBoundingClientRect();
            flyout.textContent = label;
            flyout.style.left = `${rect.left + (rect.width / 2)}px`;
            flyout.style.top = `${rect.top + 10}px`;
            flyout.style.transform = 'translateX(-50%)';
            flyout.classList.add('is-open');
            activeHeader = th;
        }

        document.querySelectorAll('th.role-module-th').forEach((th) => {
            th.addEventListener('mouseenter', () => placeRoleModuleTooltip(th));
            th.addEventListener('mouseleave', hideRoleModuleTooltip);
            th.addEventListener('focus', () => placeRoleModuleTooltip(th));
            th.addEventListener('blur', hideRoleModuleTooltip);
        });

        const reposition = () => {
            if (activeHeader) {
                placeRoleModuleTooltip(activeHeader);
            }
        };

        document.querySelector('#roles-results [data-wide-table-scroll]')?.addEventListener('scroll', reposition, { passive: true });
        document.querySelector('main')?.addEventListener('scroll', reposition, { passive: true });
        window.addEventListener('scroll', reposition, { passive: true });
        window.addEventListener('resize', reposition);
    })();
</script>
@endpush
