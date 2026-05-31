<?php

namespace App\Support;

use App\Models\User;

class CrownAuthorization
{
    public static function isAdmin(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user?->hasRole('admin') ?? false;
    }

    public static function canManageInventory(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user && ($user->hasRole('admin') || $user->hasRole('warehouse_manager'));
    }

    public static function canManagePurchasing(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user && ($user->hasRole('admin') || $user->hasRole('purchasing'));
    }
}
