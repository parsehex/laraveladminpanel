<?php

namespace App\Policies;

use App\Models\Part;
use App\Models\User;

class PartPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('parts.view');
    }

    public function view(User $user, Part $part): bool
    {
        return $user->can('parts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('parts.create');
    }

    public function update(User $user, Part $part): bool
    {
        return $user->can('parts.edit');
    }

    public function delete(User $user, Part $part): bool
    {
        return $user->can('parts.delete');
    }
}
