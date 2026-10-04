<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserPreference;

class UserPreferences
{
    public static function get(User $user, string $key): mixed
    {
        $preference = UserPreference::query()
            ->where('user_id', $user->id)
            ->where('key', $key)
            ->first();

        return $preference?->value;
    }

    public static function put(User $user, string $key, mixed $value): void
    {
        UserPreference::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'key' => $key,
            ],
            [
                'value' => $value,
            ],
        );
    }

    public static function forget(User $user, string $key): void
    {
        UserPreference::query()
            ->where('user_id', $user->id)
            ->where('key', $key)
            ->delete();
    }
}
