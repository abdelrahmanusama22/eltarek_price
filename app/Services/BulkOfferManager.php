<?php

namespace App\Services;

use App\Models\BulkOffer;
use App\Models\PriceEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BulkOfferManager
{
    /**
     * Applies a BulkOffer to all vehicles matched by its target scope and exclusion rules.
     */
    public static function apply(BulkOffer $bulkOffer): int
    {
        set_time_limit(0);

        $offerType = in_array($bulkOffer->offer_type, ['amount', 'fixed'], true) ? 'fixed' : 'percentage';

        $offerPayload = [
            'id'          => $bulkOffer->offer_id,
            'title'       => $bulkOffer->title,
            'offer_type'  => $offerType,
            'value'       => (float) $bulkOffer->value,
            'price_basis' => $bulkOffer->price_basis,
            'is_active'   => (bool) $bulkOffer->is_active,
            'note'        => $bulkOffer->note,
        ];

        $entries = $bulkOffer->getTargetQuery()->get();
        $count = 0;

        foreach ($entries as $entry) {
            $offers = $entry->offers ?? [];
            $found = false;

            foreach ($offers as &$existingOffer) {
                if (
                    (isset($existingOffer['id']) && $existingOffer['id'] === $bulkOffer->offer_id) ||
                    (isset($existingOffer['title']) && strtolower(trim($existingOffer['title'])) === strtolower(trim($bulkOffer->title)))
                ) {
                    $existingOffer = $offerPayload;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $offers[] = $offerPayload;
            }

            $entry->updateQuietly(['offers' => array_values($offers)]);
            $count++;
        }

        $bulkOffer->updateQuietly(['vehicles_count' => $count]);

        return $count;
    }

    /**
     * Safely reverts and removes a BulkOffer from all vehicles currently carrying it.
     */
    public static function revert(BulkOffer $bulkOffer): int
    {
        set_time_limit(0);

        $boundEntries = $bulkOffer->getBoundVehiclesQuery()->get();
        $count = 0;

        foreach ($boundEntries as $entry) {
            $offers = $entry->offers ?? [];
            $initialCount = count($offers);

            $filteredOffers = array_filter($offers, function ($offer) use ($bulkOffer) {
                if (isset($offer['id']) && $offer['id'] === $bulkOffer->offer_id) {
                    return false;
                }
                if (isset($offer['title']) && strtolower(trim($offer['title'])) === strtolower(trim($bulkOffer->title))) {
                    return false;
                }
                return true;
            });

            if (count($filteredOffers) < $initialCount) {
                $entry->updateQuietly(['offers' => array_values($filteredOffers)]);
                $count++;
            }
        }

        $bulkOffer->updateQuietly(['vehicles_count' => 0]);

        return $count;
    }

    /**
     * Synchronizes a modified BulkOffer: removes it from vehicles no longer in scope,
     * and updates/adds it to vehicles currently within the target scope.
     */
    public static function sync(BulkOffer $bulkOffer): int
    {
        set_time_limit(0);

        // 1. Revert from vehicles that should no longer have it
        $targetIds = $bulkOffer->getTargetQuery()->pluck('price_entries.id')->toArray();
        $currentlyBound = $bulkOffer->getBoundVehiclesQuery()->get();

        foreach ($currentlyBound as $entry) {
            if (!in_array($entry->id, $targetIds, true)) {
                $offers = $entry->offers ?? [];
                $filtered = array_filter($offers, function ($offer) use ($bulkOffer) {
                    if (isset($offer['id']) && $offer['id'] === $bulkOffer->offer_id) {
                        return false;
                    }
                    if (isset($offer['title']) && strtolower(trim($offer['title'])) === strtolower(trim($bulkOffer->title))) {
                        return false;
                    }
                    return true;
                });
                $entry->updateQuietly(['offers' => array_values($filtered)]);
            }
        }

        // 2. Apply/update onto all eligible vehicles
        return static::apply($bulkOffer);
    }

    /**
     * Creates or updates a BulkOffer record from modal array data and applies it to vehicles.
     */
    public static function applyFromData(array $data, ?User $user = null): BulkOffer
    {
        $offerType = ($data['offer_type'] ?? 'amount') === 'percentage' ? 'percentage' : 'fixed';

        $bulkOffer = BulkOffer::updateOrCreate(
            ['offer_id' => $data['offer_id']],
            [
                'title'          => $data['title'],
                'price_basis'    => $data['price_basis'] ?? 'max_selling_price',
                'offer_type'     => $offerType,
                'value'          => (float) ($data['price'] ?? $data['value'] ?? 0),
                'scope'          => $data['scope'] ?? 'all',
                'brands'         => $data['brands'] ?? null,
                'models'         => $data['models'] ?? null,
                'exclude_brands' => $data['exclude_brands'] ?? null,
                'exclude_models' => $data['exclude_models'] ?? null,
                'note'           => $data['note'] ?? null,
                'is_active'      => $data['is_active'] ?? true,
                'created_by'     => $user?->id ?? auth()->id(),
            ]
        );

        static::apply($bulkOffer);

        return $bulkOffer;
    }
}
