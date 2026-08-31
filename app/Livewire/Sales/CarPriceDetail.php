<?php

namespace App\Livewire\Sales;

use App\Models\Car;
use Livewire\Component;

class CarPriceDetail extends Component
{
    public $carId;

    public function mount(Car $car)
    {
        $this->carId = $car->id;
    }

    public function render()
    {
        $car = Car::with(['brand', 'priceEntry'])->findOrFail($this->carId);
        $portalSettings = \App\Models\SystemSetting::getSalesPortalSettings();

        return view('livewire.sales.car-price-detail', [
            'car' => $car,
            'portalSettings' => $portalSettings
        ])->layout('layouts.sales');
    }
}
