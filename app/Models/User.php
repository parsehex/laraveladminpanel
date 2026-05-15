<?php

namespace App\Models;

use App\Traits\HasPermissionHelpers;
use Hash;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasPermissionHelpers;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function setPasswordAttribute($value)
    {
        if (! empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Whether the user may access the admin area (staff).
     * Keeps compatibility with the legacy `users.role` column.
     */
    public function isStaff(): bool
    {
        if ($this->relationLoaded('roles')) {
            $names = $this->roles->pluck('name')->all();
        } else {
            $names = $this->getRoleNames()->toArray();
        }

        foreach (config('authorization.staff_roles', []) as $staffRole) {
            if (in_array($staffRole, $names, true)) {
                return true;
            }
        }

        return in_array($this->role, config('authorization.legacy_admin_role_values', ['admin']), true);
    }

    /**
     * @deprecated Prefer isStaff() or permission checks. Kept for backward compatibility.
     */
    public function isAdmin(): bool
    {
        return $this->isStaff();
    }

    public function isUser(): bool
    {
        return ! $this->isStaff();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('authorization.super_admin_role', 'Super Admin'));
    }
}
