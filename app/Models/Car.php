<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;


class Car extends Model
{
    use LogsActivity;

    public $incrementing = true;

    protected $fillable = [
        'crm_id',
        'brand_id',
        'model_name',
        'category',
        'year',
        'model_sales_code',
        'official_price',
        'execution_price',
        'crm_hold_status',
        'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'official_price' => 'decimal:2',
            'execution_price' => 'decimal:2',
            'year'           => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('enterprise_audit');
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The brand this car belongs to.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * The single price entry record for this car (Single Source of Truth).
     */
    public function priceEntry(): HasOne
    {
        return $this->hasOne(PriceEntry::class);
    }
}
