<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('roles.create') ?? false;
    }

    public function rules(): array
    {
        $rolesTable = config('permission.table_names.roles', 'roles');
        $permissionsTable = config('permission.table_names.permissions', 'permissions');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($rolesTable, 'name')->where(
                    fn ($q) => $q->where('guard_name', $this->input('guard_name', 'web'))
                ),
            ],
            'guard_name' => ['nullable', 'string', 'max:50', Rule::in(array_keys(config('auth.guards')))],
            'description' => ['nullable', 'string', 'max:2000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists($permissionsTable, 'name')->where(
                    fn ($q) => $q->where('guard_name', $this->input('guard_name', 'web'))
                ),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'guard_name' => $this->input('guard_name', 'web'),
        ]);
    }
}
