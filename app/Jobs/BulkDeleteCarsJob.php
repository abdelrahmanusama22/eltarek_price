<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Car;

class BulkDeleteCarsJob implements ShouldQueue
{
    use Queueable;

    public $ids;

    /**
     * Create a new job instance.
     */
    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        collect($this->ids)->chunk(100)->each(function ($chunkIds) {
            Car::whereIn('id', $chunkIds)->get()->each->delete();
        });
    }
}
