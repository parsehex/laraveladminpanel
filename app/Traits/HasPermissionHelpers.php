<?php

namespace App\Traits;

trait HasPermissionHelpers
{
    /**
     * Readable alias for Gate / Spatie permission checks.
     */
    public function canAccess(string $permission): bool
    {
        return $this->can($permission);
    }
}
