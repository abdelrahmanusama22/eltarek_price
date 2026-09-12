<?php

namespace App\Observers;

use App\Models\Car;

class CarObserver
{
    /**
     * Handle the Car "saved" event.
     */
    public function saved(Car $car): void
    {
        \App\Jobs\NotifyCatalogUpdateJob::dispatch();
    }

    /**
     * Handle the Car "deleted" event.
     */
    public function deleted(Car $car): void
    {
        \App\Jobs\NotifyCatalogUpdateJob::dispatch();
    }
}
