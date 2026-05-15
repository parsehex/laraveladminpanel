<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.edit') ?? false;
    }

    public function rules(): array
    {
        $rolesTable = config('permission.table_names.roles', 'roles');
        $permissionsTable = config('permission.table_names.permissions', 'permissions');
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($rolesTable, 'name')
                    ->where(fn ($q) => $q->where('guard_name', $this->input('guard_name', 'web')))
                    ->ignore($role?->id),
            ],
            'guard_name' => ['nullable', 'string', 'max:50', Rule::in(array_keys(config('auth.guards')))],
            'description' => ['nullable', 'string', 'max:2000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists($permissionsTable, 'name')->where(
                    fn ($q) => $q->where('guard_name', $this->input('guard_name', $role?->guard_name ?? 'web'))
                ),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'guard_name' => $this->input('guard_name', $this->route('role')?->guard_name ?? 'web'),
        ]);
    }
}
