<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CatalogItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class CatalogItemPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CatalogItem');
    }

    public function view(AuthUser $authUser, CatalogItem $catalogItem): bool
    {
        return $authUser->can('View:CatalogItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CatalogItem');
    }

    public function update(AuthUser $authUser, CatalogItem $catalogItem): bool
    {
        return $authUser->can('Update:CatalogItem');
    }

    public function delete(AuthUser $authUser, CatalogItem $catalogItem): bool
    {
        return $authUser->can('Delete:CatalogItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CatalogItem');
    }

    public function restore(AuthUser $authUser, CatalogItem $catalogItem): bool
    {
        return $authUser->can('Restore:CatalogItem');
    }

    public function forceDelete(AuthUser $authUser, CatalogItem $catalogItem): bool
    {
        return $authUser->can('ForceDelete:CatalogItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CatalogItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CatalogItem');
    }

    public function replicate(AuthUser $authUser, CatalogItem $catalogItem): bool
    {
        return $authUser->can('Replicate:CatalogItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CatalogItem');
    }

}