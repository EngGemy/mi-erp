<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\InventoryDatabaseNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class InventoryNotifier
{
    public function notifyRole(string $role, string $title, string $body, ?string $url = null, ?Model $subject = null): void
    {
        $users = User::role($role)->where('is_active', true)->get();

        $this->notifyUsers($users, $title, $body, $url, $subject);
    }

    public function notifyUser(?User $user, string $title, string $body, ?string $url = null, ?Model $subject = null): void
    {
        if (! $user) {
            return;
        }

        $this->notifyUsers(collect([$user]), $title, $body, $url, $subject);
    }

    /**
     * @param  Collection<int, User>  $users
     */
    public function notifyUsers(Collection $users, string $title, string $body, ?string $url = null, ?Model $subject = null): void
    {
        foreach ($users as $user) {
            $user->notify(new InventoryDatabaseNotification($title, $body, $url, $subject));
        }
    }
}
