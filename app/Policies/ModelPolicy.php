<?php

namespace App\Policies;

use App\Models\Model;
use App\Models\User;

class ModelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('models.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can('models.view');
    }

    public function create(User $user): bool
    {
        return $user->can('models.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can('models.edit');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can('models.delete');
    }
}
