<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

/**
 * حماية صفحات المشروع المخصصة (ViewBom, ViewShortage, …) عبر صلاحيات Shield.
 */
trait AuthorizesCrownPage
{
    protected static function crownPagePermission(): ?string
    {
        return null;
    }

    public static function canAccess(array $parameters = []): bool
    {
        $permission = static::crownPagePermission();

        if (! $permission) {
            return parent::canAccess($parameters);
        }

        $user = Filament::auth()->user();

        return $user?->can($permission) ?? false;
    }
}
