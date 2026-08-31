<?php

namespace App\Livewire\Sales;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PriceEntry;
use Livewire\Component;

class QuickFilter extends Component
{
    public $brands;
    
    public $selectedBrand = null;
    public $selectedModelName = null;

    public function updatedSelectedBrand($value)
    {
        $this->selectedModelName = null;
    }

    public function updatedSelectedModelName($value)
    {
        if ($value) {
            $entry = PriceEntry::whereHas('car', function ($query) use ($value) {
                $query->where('model_name', $value);
            })->first();
            
            if ($entry) {
                $this->selectedBrand = $entry->brand_id;
            }
        }
    }

    public function render()
    {
        $modelsQuery = PriceEntry::whereIn('hold_status', ['NO', 'Wishing List'])->with('car');
        
        if ($this->selectedBrand) {
            $modelsQuery->where('brand_id', $this->selectedBrand);
        }

        $availableModelNames = $modelsQuery->get()
            ->pluck('car.model_name')
            ->filter() // remove nulls
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $results = [];
        $displayBrands = collect($this->brands);

        if ($this->selectedModelName) {
            $resultsQuery = PriceEntry::whereIn('hold_status', ['NO', 'Wishing List'])
                ->whereHas('car', function ($query) {
                    $query->where('model_name', $this->selectedModelName);
                });
                
            if ($this->selectedBrand) {
                $resultsQuery->where('brand_id', $this->selectedBrand);
            }

            $results = $resultsQuery->with(['car', 'brand'])
                ->get()
                ->sortBy(function ($entry) {
                    return $entry->car->model_sales_code ?? '';
                })
                ->values();
        }

        return view('livewire.sales.quick-filter', [
            'availableModelNames' => $availableModelNames,
            'results' => $results,
            'displayBrands' => $displayBrands,
        ]);
    }
}
