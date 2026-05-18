<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.create') ?? false;
    }

    public function rules(): array
    {
        $permissionsTable = config('permission.table_names.permissions', 'permissions');
        $registrationCode = (string) env('REGISTRATION_CODE', '');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,NULL,id,deleted_at,NULL'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'registration_code' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($registrationCode): void {
                    if ($registrationCode === '' || ! hash_equals($registrationCode, (string) $value)) {
                        $fail('Invalid registration code.');
                    }
                },
            ],
            'role' => ['required', 'string', 'max:255', Rule::in(Role::query()->where('guard_name', 'web')->pluck('name'))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'direct_permissions' => ['nullable', 'array'],
            'direct_permissions.*' => [
                'string',
                Rule::exists($permissionsTable, 'name')->where(fn ($q) => $q->where('guard_name', 'web')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.unique' => 'This email is already taken.',
            'password.required' => 'The password field is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'registration_code.required' => 'The registration code field is required.',
        ];
    }
}
