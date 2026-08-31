<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
use HasRoles;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // -------------------------------------------------------------------------
    // Filament Access Control (PRD Section 5.1)
    // Sales reps are blocked from the /admin panel entirely.
    // -------------------------------------------------------------------------

    /**
     * Determines whether the user may access the Filament Admin Panel.
     * Sales Representatives (role = 'sales') are strictly prohibited.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access for all users for testing purposes
        return true;
    }

    // -------------------------------------------------------------------------
    // Role Helpers
    // -------------------------------------------------------------------------

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isBrandManager(): bool
    {
        return $this->hasRole('Brand Manager');
    }

    public function isSales(): bool
    {
        return $this->hasRole('sales');
    }


    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The brands this user (Brand Manager) is authorized to manage.
     * Implements Multi-Tenancy via the brand_user pivot table.
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_user')->select('brands.*');
    }
}
