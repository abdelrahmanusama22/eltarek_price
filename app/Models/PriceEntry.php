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
        'crm_id',
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
        static::creating(function ($entry) {
            if (empty($entry->crm_id) && !empty($entry->model_sales_code)) {
                $car = \App\Models\Car::where('model_sales_code', $entry->model_sales_code)->first();
                if ($car && !empty($car->crm_id)) {
                    $entry->crm_id = $car->crm_id;
                }
            }
        });

        static::updating(function ($priceEntry) {
            // Check if it already has a CRM ID and user is NOT super_admin
            $hasCrmId = !empty($priceEntry->getOriginal('crm_id'));
            $isNotSuperAdmin = auth()->check() && !auth()->user()->hasRole('super_admin');

            if ($hasCrmId && $isNotSuperAdmin) {
                // Fields to lock/ignore changes for
                $protectedFields = ['brand_id', 'model_name', 'model_sales_code', 'year', 'crm_id'];

                foreach ($protectedFields as $field) {
                    if ($priceEntry->isDirty($field)) {
                        // Silently revert the change (ignore it)
                        $priceEntry->{$field} = $priceEntry->getOriginal($field);
                    }
                }
            }

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

    /**
     * Spatie Activitylog v5 hook (this was `tapActivity()` in v4).
     *
     * Stamps the immutable business keys onto every activity's `properties`,
     * so the history stays linked to the vehicle even if this row is hard-deleted
     * and re-created (new auto-increment id) by an Excel re-upload.
     */
    public function beforeActivityLogged(\Spatie\Activitylog\Models\Activity $activity, string $event): void
    {
        $activity->properties = collect($activity->properties ?? [])->merge(array_filter([
            'model_sales_code' => $this->model_sales_code,
            'crm_id'           => $this->crm_id,
        ], fn ($v) => $v !== null && $v !== ''));
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
        return $this->belongsTo(Car::class, 'model_sales_code', 'model_sales_code');
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

    /**
     * Compare this PriceEntry with a Car model and return an array of normalized mismatches.
     * Prevents false positives from type casting, trailing spaces, or minor decimal differences.
     */
    public function getConflictsWithCar($car): array
    {
        if (!$car) return [];
        
        $columnMap = [
            'official_price'   => 'official_price',
            'model_name'       => 'model_name',
            'model_sales_code' => 'model_sales_code',
            'year'             => 'year',
            'brand_id'         => 'brand_id',
            'crm_hold_status'  => 'hold_status',
        ];
        
        $ignored = $this->ignored_crm_updates ?? [];
        $mismatches = [];

        foreach ($columnMap as $crmField => $priceField) {
            $carVal = $car->$crmField;
            $entryVal = $this->$priceField;
            $ignoredVal = $ignored[$priceField] ?? null;

            // 1. Normalize Prices (Float comparison)
            if ($priceField === 'official_price') {
                $carVal = round((float)($carVal ?: 0), 2);
                $entryVal = round((float)($entryVal ?: 0), 2);
                if ($ignoredVal !== null) {
                    $ignoredVal = round((float)($ignoredVal ?: 0), 2);
                }
            } 
            // 2. Normalize Strings (Trim, Lowercase)
            elseif (in_array($priceField, ['model_name', 'model_sales_code', 'hold_status'])) {
                $carVal = trim(strtolower((string)$carVal));
                $entryVal = trim(strtolower((string)$entryVal));
                if ($ignoredVal !== null) {
                    $ignoredVal = trim(strtolower((string)$ignoredVal));
                }
            }
            // 3. Normalize Integers/Relations
            else {
                $carVal = (int)($carVal ?: 0);
                $entryVal = (int)($entryVal ?: 0);
                if ($ignoredVal !== null) {
                    $ignoredVal = (int)($ignoredVal ?: 0);
                }
            }

            // If they don't match, and the car's current value isn't explicitly ignored
            if ($carVal !== $entryVal && $ignoredVal !== $carVal) {
                $mismatches[$priceField] = [
                    'car_value' => $carVal,
                    'entry_value' => $entryVal,
                    'original_car_value' => $car->$crmField,
                    'original_entry_value' => $this->$priceField,
                ];
            }
        }
        
        return $mismatches;
    }
}
