<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalesUserPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SalesUser');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:SalesUser');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SalesUser');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:SalesUser');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:SalesUser');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SalesUser');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:SalesUser');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:SalesUser');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SalesUser');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SalesUser');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:SalesUser');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SalesUser');
    }

}