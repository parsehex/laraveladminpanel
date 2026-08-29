<?php

namespace App\Legacy;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LegacyUserResolver
{
    /** @var array<string, array<string, mixed>> */
    private array $patches;

    /** @var array<string, int|null> */
    private array $cache = [];

    /** @var list<string> */
    private array $unresolved = [];

    public function __construct(array $patches)
    {
        $this->patches = $patches;
    }

    public function resolve(?string $legacyKey, bool $strict = true): ?int
    {
        if ($legacyKey === null || trim($legacyKey) === '') {
            return null;
        }

        $key = trim($legacyKey);
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $patch = $this->patches[$key] ?? null;
        if ($patch === null) {
            $this->unresolved[] = $key;
            if ($strict) {
                throw new InvalidArgumentException("No user patch for legacy key [{$key}]");
            }

            $this->cache[$key] = null;

            return null;
        }

        if (array_key_exists('match', $patch)) {
            $match = $patch['match'];
            if ($match === null) {
                $this->cache[$key] = null;

                return null;
            }

            $userId = $this->resolveMatch((string) $match);
            $this->cache[$key] = $userId;

            return $userId;
        }

        if (isset($patch['create_inactive']) && is_array($patch['create_inactive'])) {
            $userId = $this->createInactiveUser($patch['create_inactive']);
            $this->cache[$key] = $userId;

            return $userId;
        }

        throw new InvalidArgumentException("Invalid user patch for [{$key}]");
    }

    /**
     * @return list<string>
     */
    public function unresolved(): array
    {
        return array_values(array_unique($this->unresolved));
    }

    /**
     * @return list<string>
     */
    public function keysNeedingPatch(LegacyDumpReader $dump): array
    {
        $keys = [];

        foreach (['users' => 'username'] as $table => $column) {
            if (! $dump->hasTable($table)) {
                continue;
            }

            foreach ($dump->rows($table) as $row) {
                $value = $row[$column] ?? null;
                if ($value !== null && trim((string) $value) !== '') {
                    $keys[(string) $value] = true;
                }
            }
        }

        $stringColumns = [
            'testing_results' => 'completed_by',
            'repair_diagnoses' => 'created_by',
            'suggestions' => 'completed_by',
            'user_actions' => 'username',
            'truck_items' => 'triage_tech_id',
        ];

        foreach ($stringColumns as $table => $column) {
            if (! $dump->hasTable($table)) {
                continue;
            }

            foreach ($dump->rows($table) as $row) {
                $value = $row[$column] ?? null;
                if ($value !== null && trim((string) $value) !== '') {
                    $keys[(string) $value] = true;
                }
            }
        }

        if ($dump->hasTable('inventory_status_history')) {
            $legacyUsersById = [];
            if ($dump->hasTable('users')) {
                foreach ($dump->rows('users') as $userRow) {
                    $legacyUsersById[(int) $userRow['id']] = (string) $userRow['username'];
                }
            }

            foreach ($dump->rows('inventory_status_history') as $row) {
                $legacyUserId = LegacyCopyValue::int($row['user_id'] ?? null);
                if ($legacyUserId !== null && isset($legacyUsersById[$legacyUserId])) {
                    $keys[$legacyUsersById[$legacyUserId]] = true;
                }
            }
        }

        return array_keys($keys);
    }

    private function resolveMatch(string $match): ?int
    {
        if (str_starts_with($match, 'email:')) {
            $email = substr($match, 6);
            $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

            return $user?->id;
        }

        if (str_starts_with($match, 'id:')) {
            return (int) substr($match, 3);
        }

        throw new InvalidArgumentException("Unknown user match format: {$match}");
    }

    /**
     * @param  array{name?: string, email?: string, role?: string}  $data
     */
    private function createInactiveUser(array $data): int
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($email === '') {
            throw new InvalidArgumentException('create_inactive requires email');
        }

        $existing = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existing !== null) {
            return (int) $existing->id;
        }

        $now = now();

        return (int) DB::table('users')->insertGetId([
            'name' => $data['name'] ?? $email,
            'email' => $email,
            'password' => Hash::make(Str::random(64)),
            'role' => $data['role'] ?? 'user',
            'status' => 'inactive',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
