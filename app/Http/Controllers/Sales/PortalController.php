<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{
    /**
     * Step 1: List all Brands.
     */
    public function index()
    {
        // Business Logic: Sales users see ALL brands.
        $brands = Brand::orderBy('name', 'asc')->get();

        return view('sales.portal.brands', compact('brands'));
    }

    /**
     * Step 2: Show unique Model Names under the selected Brand.
     */
    public function showBrand(Brand $brand)
    {
        // Fetch unique model names for this brand
        $models = Car::where('brand_id', $brand->id)
            ->whereHas('priceEntry', function ($query) {
                $query->whereIn('hold_status', ['NO', 'Wishing List']);
            })
            ->select('model_name')
            ->distinct()
            ->orderBy('model_name')
            ->get()
            ->pluck('model_name');

        return view('sales.portal.models', compact('brand', 'models'));
    }

    /**
     * Step 3: Show Model Sales Codes for a specific Model Name under the Brand.
     */
    public function showModels(Brand $brand, string $model_name)
    {
        $priceEntries = \App\Models\PriceEntry::where('brand_id', $brand->id)
            ->whereIn('hold_status', ['NO', 'Wishing List'])
            ->whereHas('car', function ($query) use ($model_name) {
                $query->where('model_name', $model_name);
            })
            ->with(['car', 'brand'])
            ->get()
            ->sortBy(function ($entry) {
                return $entry->car->model_sales_code ?? '';
            })
            ->values();

        return view('sales.portal.sales_codes', compact('brand', 'model_name', 'priceEntries'));
    }



    /**
     * Show all active special offers for the assigned brands.
     */
    public function offers()
    {
        $priceEntries = \App\Models\PriceEntry::whereNotNull('offers')
            ->where('offers', '!=', '[]')
            ->whereIn('hold_status', ['NO', 'Wishing List'])
            ->with(['car', 'brand'])
            ->get()
            ->filter(function ($entry) {
                // Filter down to entries that have at least one active offer
                $activeOffers = array_filter($entry->offers ?? [], function ($offer) {
                    return isset($offer['is_active']) && $offer['is_active'] == true;
                });
                return count($activeOffers) > 0;
            });

        return view('sales.portal.offers', compact('priceEntries'));
    }
}
