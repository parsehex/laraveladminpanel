<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.edit') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user');
        $permissionsTable = config('permission.table_names.permissions', 'permissions');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'max:255', Rule::in(Role::query()->where('guard_name', 'web')->pluck('name'))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'direct_permissions' => ['nullable', 'array'],
            'direct_permissions.*' => [
                'string',
                Rule::exists($permissionsTable, 'name')->where(fn ($q) => $q->where('guard_name', 'web')),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->password)) {
            $this->request->remove('password');
            $this->request->remove('password_confirmation');
        }
    }
}
