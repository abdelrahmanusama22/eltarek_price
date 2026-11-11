<?php

namespace App\Observers;

use App\Models\PriceEntry;

class PriceEntryObserver
{
    /**
     * Handle the PriceEntry "saving" event.
     *
     * This is the core Pricing Engine for the system (PRD Section 3.1).
     * It fires on BOTH create AND update, covering manual web inputs AND
     * bulk Excel imports — guaranteeing zero code duplication.
     *
     * The 5% & 3M Protection Waterfall Allocation Rule:
     * ─────────────────────────────────────────────────
     *  Max Allowed Selling Price = official_price × 1.05
     *
     *  IF execution_price <= Max Allowed Selling Price:
     *      max_selling_price    = execution_price
     *      protection_3m_price  = 0
     *
     *  ELSE IF execution_price > Max Allowed Selling Price:
     *      max_selling_price    = Max Allowed Selling Price
     *      protection_3m_price  = execution_price - Max Allowed Selling Price
     */
    public function saving(PriceEntry $priceEntry): void
    {
        // Skip automatic calculation if Manual Override is enabled for this vehicle
        if ($priceEntry->is_manual_override) {
            return;
        }

        $officialPrice   = (float) $priceEntry->official_price;
        $executionPrice  = (float) $priceEntry->execution_price;
        $strategy        = $priceEntry->pricing_strategy ?? 'standard';

        $maxAllowedSellingPrice = ($strategy === 'all_3m')
            ? min($executionPrice, $officialPrice)
            : ($officialPrice * 1.05);

        if ($executionPrice <= $maxAllowedSellingPrice) {
            $priceEntry->max_selling_price   = $executionPrice;
            $priceEntry->protection_3m_price = 0.00;
        } else {
            $priceEntry->max_selling_price   = $maxAllowedSellingPrice;
            $priceEntry->protection_3m_price = max(0.00, $executionPrice - $maxAllowedSellingPrice);
        }
    }

    /**
     * Handle the PriceEntry "saved" event.
     * Fires immediately after creation or update to notify backend_eltarek.
     */
    public function saved(PriceEntry $priceEntry): void
    {
        \App\Jobs\NotifyCatalogUpdateJob::dispatchSync([
            'crm_id' => $priceEntry->crm_id,
            'sales_code' => $priceEntry->model_sales_code,
            'price' => $priceEntry->max_selling_price,
            'source' => 'price_entry_updated',
        ]);
    }

    /**
     * Handle the PriceEntry "deleted" event.
     */
    public function deleted(PriceEntry $priceEntry): void
    {
        \App\Jobs\NotifyCatalogUpdateJob::dispatchSync([
            'crm_id' => $priceEntry->crm_id,
            'sales_code' => $priceEntry->model_sales_code,
            'source' => 'price_entry_deleted',
        ]);
    }
}
