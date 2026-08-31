<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PriceEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

class PriceEntryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PriceEntry');
    }

    public function view(AuthUser $authUser, PriceEntry $priceEntry): bool
    {
        return $authUser->can('View:PriceEntry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PriceEntry');
    }

    public function update(AuthUser $authUser, PriceEntry $priceEntry): bool
    {
        return $authUser->can('Update:PriceEntry');
    }

    public function delete(AuthUser $authUser, PriceEntry $priceEntry): bool
    {
        return $authUser->can('Delete:PriceEntry');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PriceEntry');
    }

    public function restore(AuthUser $authUser, PriceEntry $priceEntry): bool
    {
        return $authUser->can('Restore:PriceEntry');
    }

    public function forceDelete(AuthUser $authUser, PriceEntry $priceEntry): bool
    {
        return $authUser->can('ForceDelete:PriceEntry');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PriceEntry');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PriceEntry');
    }

    public function replicate(AuthUser $authUser, PriceEntry $priceEntry): bool
    {
        return $authUser->can('Replicate:PriceEntry');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PriceEntry');
    }

}