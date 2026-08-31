<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Car;
use Illuminate\Auth\Access\HandlesAuthorization;

class CarPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Car');
    }

    public function view(AuthUser $authUser, Car $car): bool
    {
        return $authUser->can('View:Car');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Car');
    }

    public function update(AuthUser $authUser, Car $car): bool
    {
        return $authUser->can('Update:Car');
    }

    public function delete(AuthUser $authUser, Car $car): bool
    {
        return $authUser->can('Delete:Car');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Car');
    }

    public function restore(AuthUser $authUser, Car $car): bool
    {
        return $authUser->can('Restore:Car');
    }

    public function forceDelete(AuthUser $authUser, Car $car): bool
    {
        return $authUser->can('ForceDelete:Car');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Car');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Car');
    }

    public function replicate(AuthUser $authUser, Car $car): bool
    {
        return $authUser->can('Replicate:Car');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Car');
    }

}