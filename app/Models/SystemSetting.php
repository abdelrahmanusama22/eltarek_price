<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SystemSetting extends Model
{
    use LogsActivity;

    protected $fillable = [
        'key',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public static function getSalesPortalSettings(): array
    {
        return cache()->rememberForever('sales_portal_settings', function () {
            return self::firstOrCreate(
                ['key' => 'sales_portal_settings'],
                ['payload' => [
                    'show_official_price'        => true,
                    'show_max_selling_price'     => true,
                    'show_execution_price'       => true,
                    'show_3m_protection_price'   => true,
                    'show_total_calculation'     => true,
                    'show_availability_status'   => true,
                    'show_special_offers'        => true,
                ]]
            )->payload;
        });
    }

    public static function clearSalesPortalSettingsCache(): void
    {
        cache()->forget('sales_portal_settings');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
