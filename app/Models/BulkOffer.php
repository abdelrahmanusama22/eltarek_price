<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class BulkOffer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'offer_id',
        'title',
        'price_basis',
        'offer_type',
        'value',
        'scope',
        'brands',
        'models',
        'exclude_brands',
        'exclude_models',
        'note',
        'is_active',
        'vehicles_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'brands'          => 'array',
            'models'          => 'array',
            'exclude_brands'  => 'array',
            'exclude_models'  => 'array',
            'is_active'       => 'boolean',
            'value'           => 'decimal:2',
            'vehicles_count'  => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('bulk_offers_audit');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Builds the Eloquent query targeting vehicles eligible for this offer.
     */
    public function getTargetQuery(): Builder
    {
        $query = PriceEntry::query()->with(['car', 'brand']);

        // 1. Positive Scope Selection
        if ($this->scope === 'brands' && !empty($this->brands)) {
            $query->whereIn('brand_id', (array) $this->brands);
        } elseif ($this->scope === 'models' && !empty($this->models)) {
            $query->whereHas('car', function ($q) {
                $q->whereIn('model_name', (array) $this->models);
            });
        }

        // 2. Strict Exclusions: Brands
        if (!empty($this->exclude_brands)) {
            $excludeBrands = array_filter((array) $this->exclude_brands);
            if (!empty($excludeBrands)) {
                $query->whereNotIn('brand_id', $excludeBrands);
            }
        }

        // 3. Strict Exclusions: Models
        if (!empty($this->exclude_models)) {
            $excludeModels = array_filter((array) $this->exclude_models);
            if (!empty($excludeModels)) {
                $query->where(function ($q) use ($excludeModels) {
                    $q->whereNull('model_name')
                      ->orWhereNotIn('model_name', $excludeModels);
                })->whereDoesntHave('car', function ($carQuery) use ($excludeModels) {
                    $carQuery->whereIn('model_name', $excludeModels);
                });
            }
        }

        return $query;
    }

    /**
     * Builds the query for vehicles that currently carry this offer in their JSON `offers` array.
     */
    public function getBoundVehiclesQuery(): Builder
    {
        $offerId = $this->offer_id;
        $title = $this->title;

        return PriceEntry::query()
            ->with(['car', 'brand'])
            ->whereNotNull('offers')
            ->where('offers', '!=', '[]')
            ->where(function ($q) use ($offerId, $title) {
                $q->whereJsonContains('offers', [['id' => $offerId]])
                  ->orWhere('offers', 'like', "%\"id\":\"{$offerId}\"%")
                  ->orWhere('offers', 'like', "%\"title\":\"{$title}\"%");
            });
    }

    /**
     * Returns a human-friendly representation of the positive scope.
     */
    public function getFormattedScopeAttribute(): string
    {
        if ($this->scope === 'all') {
            return 'All Cars';
        }

        if ($this->scope === 'brands') {
            $brandCount = count($this->brands ?? []);
            if ($brandCount === 0) return 'All Brands';
            $names = Brand::whereIn('id', (array) $this->brands)->pluck('name')->take(3)->implode(', ');
            return $brandCount > 3 ? "{$brandCount} Brands ({$names}...)" : "{$brandCount} Brands ({$names})";
        }

        if ($this->scope === 'models') {
            $modelCount = count($this->models ?? []);
            if ($modelCount === 0) return 'All Models';
            $names = collect($this->models)->take(3)->implode(', ');
            return $modelCount > 3 ? "{$modelCount} Models ({$names}...)" : "{$modelCount} Models ({$names})";
        }

        return ucfirst($this->scope);
    }

    /**
     * Returns a human-friendly summary of any active exclusions.
     */
    public function getFormattedExclusionsAttribute(): string
    {
        $parts = [];

        if (!empty($this->exclude_brands)) {
            $brandCount = count($this->exclude_brands);
            $names = Brand::whereIn('id', (array) $this->exclude_brands)->pluck('name')->take(2)->implode(', ');
            $parts[] = $brandCount > 2 ? "{$brandCount} Brands ({$names}...)" : "{$brandCount} Brands ({$names})";
        }

        if (!empty($this->exclude_models)) {
            $modelCount = count($this->exclude_models);
            $names = collect($this->exclude_models)->take(2)->implode(', ');
            $parts[] = $modelCount > 2 ? "{$modelCount} Models ({$names}...)" : "{$modelCount} Models ({$names})";
        }

        return empty($parts) ? 'None' : implode(' | ', $parts);
    }
}
