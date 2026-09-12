<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OfferTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfferTemplatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OfferTemplate');
    }

    public function view(AuthUser $authUser, OfferTemplate $offerTemplate): bool
    {
        return $authUser->can('View:OfferTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OfferTemplate');
    }

    public function update(AuthUser $authUser, OfferTemplate $offerTemplate): bool
    {
        return $authUser->can('Update:OfferTemplate');
    }

    public function delete(AuthUser $authUser, OfferTemplate $offerTemplate): bool
    {
        return $authUser->can('Delete:OfferTemplate');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OfferTemplate');
    }

    public function restore(AuthUser $authUser, OfferTemplate $offerTemplate): bool
    {
        return $authUser->can('Restore:OfferTemplate');
    }

    public function forceDelete(AuthUser $authUser, OfferTemplate $offerTemplate): bool
    {
        return $authUser->can('ForceDelete:OfferTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OfferTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OfferTemplate');
    }

    public function replicate(AuthUser $authUser, OfferTemplate $offerTemplate): bool
    {
        return $authUser->can('Replicate:OfferTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OfferTemplate');
    }

    public function manageCrmId(AuthUser $authUser, OfferTemplate $offerTemplate): bool
    {
        return $authUser->can('ManageCrmId:OfferTemplate');
    }

}