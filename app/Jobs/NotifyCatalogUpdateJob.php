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

    public $payload;

    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $url = env('NEW_SYSTEM_WEBHOOK_URL', 'http://127.0.0.1:8000/api/webhook/trigger-catalog-sync');
        
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $this->payload);

            if ($response->failed()) {
                Log::error('Failed to notify new system of catalog update.', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $this->payload,
                ]);
            } else {
                Log::info('Successfully notified new system of catalog update.', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while notifying new system of catalog update: ' . $e->getMessage(), [
                'url' => $url,
                'payload' => $this->payload,
            ]);
        }
    }
}
