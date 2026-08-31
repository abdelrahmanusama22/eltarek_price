<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;


class PriceEntry extends Model
{
    use LogsActivity;

    protected $fillable = [
        'car_id',
        'brand_id',
        'official_price',
        'execution_price',
        'pricing_strategy',
        'max_selling_price',
        'protection_3m_price',
        'ignored_crm_updates',
        'model_name',
        'model_sales_code',
        'year',
        'offers',
        'hold_status',
        'additional_info',
        'available_colors',
        'warranty_info',
        'brochure_pdf',
        'last_updated_by',
    ];

    protected function casts(): array
    {
        return [
            // JSON offers column is auto-cast to/from PHP array
            'offers'              => 'array',
            'available_colors'    => 'array',
            'ignored_crm_updates' => 'array',
            'year'                => 'integer',
            'official_price'      => 'decimal:2',
            'execution_price'     => 'decimal:2',
            'max_selling_price'   => 'decimal:2',
            'protection_3m_price' => 'decimal:2',
        ];
    }

    protected static function booted()
    {
        static::updating(function ($priceEntry) {
            if (auth()->check()) {
                $priceEntry->last_updated_by = auth()->id();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Spatie Activitylog Configuration (PRD Section 6.1)
    // Tracks all mutations, stores old vs. new values for causer/subject audit.
    // -------------------------------------------------------------------------

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()                   // Log ALL attributes, not just fillable
            ->logOnlyDirty()             // Only emit a log entry when something actually changed
            ->dontLogEmptyChanges()      // Skip if the dirty set is empty after comparison
            ->useLogName('enterprise_audit');
    }

    // -------------------------------------------------------------------------
    // Business Logic Accessor: Computed Total Price for Sales Portal
    // Formula: max_selling_price + protection_3m_price  (PRD Section 5.2)
    // -------------------------------------------------------------------------

    /**
     * Returns the final total price displayed to Sales Representatives.
     */
    public function getTotalPriceAttribute(): float
    {
        return (float) $this->max_selling_price + (float) $this->protection_3m_price;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The car this price entry belongs to.
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * The brand this price entry belongs to (denormalized for scoping performance).
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * The user who last updated this price entry.
     */
    public function lastUpdater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}
