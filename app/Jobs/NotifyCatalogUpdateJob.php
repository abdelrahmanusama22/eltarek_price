<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifyCatalogUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $url = env('NEW_SYSTEM_WEBHOOK_URL', 'http://127.0.0.1:8000/api/webhook/trigger-catalog-sync');
        
        try {
            Http::timeout(10)->post($url);
        } catch (\Exception $e) {
            Log::error('Failed to notify new system of catalog update: ' . $e->getMessage());
        }
    }
}
