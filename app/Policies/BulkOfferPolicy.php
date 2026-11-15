<?php

namespace App\Policies;

use App\Models\BulkOffer;
use App\Models\User;

class BulkOfferPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin') || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('Brand Manager') || (method_exists($user, 'isBrandManager') && $user->isBrandManager()) || $user->can('ViewAny:BulkOffer');
    }

    public function view(User $user, BulkOffer $bulkOffer): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('Brand Manager') || (method_exists($user, 'isBrandManager') && $user->isBrandManager()) || $user->can('View:BulkOffer');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('Brand Manager') || (method_exists($user, 'isBrandManager') && $user->isBrandManager()) || $user->can('Create:BulkOffer');
    }

    public function update(User $user, BulkOffer $bulkOffer): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('Brand Manager') || (method_exists($user, 'isBrandManager') && $user->isBrandManager()) || $user->can('Update:BulkOffer');
    }

    public function delete(User $user, BulkOffer $bulkOffer): bool
    {
        return $user->hasRole('super_admin') || $user->hasRole('Brand Manager') || (method_exists($user, 'isBrandManager') && $user->isBrandManager()) || $user->can('Delete:BulkOffer');
    }
}
