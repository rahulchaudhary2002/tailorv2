<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, int>  $excludeIds
     */
    public function notifyPermission(string $permissionKey, ?int $outletId, array $payload, array $excludeIds = []): void
    {
        $recipients = $this->usersForPermission($permissionKey, $outletId, $excludeIds);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new SystemNotification($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyUsers(iterable $users, array $payload): void
    {
        $recipients = collect($users)
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new SystemNotification($payload));
    }

    /**
     * @param  array<int, int>  $excludeIds
     * @return Collection<int, User>
     */
    public function usersForPermission(string $permissionKey, ?int $outletId, array $excludeIds = []): Collection
    {
        $users = User::query()
            ->where(function ($query) use ($outletId): void {
                $query->where('is_super_admin', true);

                if ($outletId > 0) {
                    $query->orWhereHas('roles', function ($roleQuery) use ($outletId): void {
                        $roleQuery->where('user_role.outlet_id', $outletId);
                    })->orWhereHas('permissionOverrides', function ($overrideQuery) use ($outletId): void {
                        $overrideQuery->where('user_permission.outlet_id', $outletId);
                    });
                } else {
                    $query->orWhereHas('roles')
                        ->orWhereHas('permissionOverrides');
                }
            })
            ->when($excludeIds !== [], fn ($query) => $query->whereNotIn('id', $excludeIds))
            ->get()
            ->filter(fn (User $user) => $user->hasPermission($permissionKey, $outletId))
            ->values();

        return $users;
    }
}
