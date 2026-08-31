<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;


class Brand extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'is_active',
        'order',
        'logo',
    ];

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
     * The Brand Managers who are authorized to manage this brand.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'brand_user');
    }

    /**
     * All cars belonging to this brand (imported from CRM).
     */
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    /**
     * All price entries associated with this brand.
     */
    public function priceEntries(): HasMany
    {
        return $this->hasMany(PriceEntry::class);
    }
}
