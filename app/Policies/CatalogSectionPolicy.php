<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CatalogSection;
use Illuminate\Auth\Access\HandlesAuthorization;

class CatalogSectionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CatalogSection');
    }

    public function view(AuthUser $authUser, CatalogSection $catalogSection): bool
    {
        return $authUser->can('View:CatalogSection');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CatalogSection');
    }

    public function update(AuthUser $authUser, CatalogSection $catalogSection): bool
    {
        return $authUser->can('Update:CatalogSection');
    }

    public function delete(AuthUser $authUser, CatalogSection $catalogSection): bool
    {
        return $authUser->can('Delete:CatalogSection');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CatalogSection');
    }

    public function restore(AuthUser $authUser, CatalogSection $catalogSection): bool
    {
        return $authUser->can('Restore:CatalogSection');
    }

    public function forceDelete(AuthUser $authUser, CatalogSection $catalogSection): bool
    {
        return $authUser->can('ForceDelete:CatalogSection');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CatalogSection');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CatalogSection');
    }

    public function replicate(AuthUser $authUser, CatalogSection $catalogSection): bool
    {
        return $authUser->can('Replicate:CatalogSection');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CatalogSection');
    }

}