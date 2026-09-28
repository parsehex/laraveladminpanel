<?php

namespace App\Support;

use App\Models\ModuleNotificationSubscriber;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ModuleNotifier
{
    /**
     * Modules that support staff notification subscribers.
     *
     * @return array<string, array{label: string, description: string}>
     */
    public static function modules(): array
    {
        return [
            'deliveries' => [
                'label' => 'Deliveries',
                'description' => 'Notify when a new delivery is created.',
            ],
            'kits' => [
                'label' => 'Kits',
                'description' => 'Notify when a kit is assigned. The person assigned the kit is always included.',
            ],
            'suggestions' => [
                'label' => 'Suggestions',
                'description' => 'Notify when a staff member submits a suggestion.',
            ],
        ];
    }

    public static function isValidModule(string $module): bool
    {
        return array_key_exists($module, self::modules());
    }

    /**
     * @return EloquentCollection<int, User>
     */
    public static function recipients(string $module): EloquentCollection
    {
        $userIds = ModuleNotificationSubscriber::query()
            ->where('module', $module)
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return new EloquentCollection;
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->where('status', 'active')
            ->get();
    }

    /**
     * @return array<string, list<int>>
     */
    public static function subscriberIdsByModule(): array
    {
        $grouped = ModuleNotificationSubscriber::query()
            ->get(['module', 'user_id'])
            ->groupBy('module')
            ->map(fn ($rows) => $rows->pluck('user_id')->map(fn ($id) => (int) $id)->values()->all())
            ->all();

        $result = [];
        foreach (array_keys(self::modules()) as $module) {
            $result[$module] = $grouped[$module] ?? [];
        }

        return $result;
    }

    /**
     * @param  list<int|string>  $userIds
     */
    public static function sync(string $module, array $userIds): void
    {
        if (! self::isValidModule($module)) {
            return;
        }

        $normalized = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($module, $normalized) {
            ModuleNotificationSubscriber::query()
                ->where('module', $module)
                ->whereNotIn('user_id', $normalized ?: [0])
                ->delete();

            foreach ($normalized as $userId) {
                ModuleNotificationSubscriber::query()->firstOrCreate([
                    'module' => $module,
                    'user_id' => $userId,
                ]);
            }
        });
    }

    /**
     * @param  iterable<int, User|null>  $additionalRecipients
     */
    public static function notify(string $module, Notification $notification, ?int $exceptUserId = null, iterable $additionalRecipients = []): void
    {
        $recipients = self::recipients($module);

        foreach ($additionalRecipients as $user) {
            if (! $user instanceof User || ! $user->isActive()) {
                continue;
            }

            $alreadyIncluded = $recipients->contains(
                fn (User $existing) => (int) $existing->id === (int) $user->id
            );

            if ($alreadyIncluded) {
                continue;
            }

            $recipients->push($user);
        }

        if ($exceptUserId !== null) {
            $recipients = $recipients->reject(
                fn (User $user) => (int) $user->id === $exceptUserId
            )->values();
        }

        if ($recipients->isEmpty()) {
            return;
        }

        NotificationFacade::send($recipients, $notification);
    }
}
