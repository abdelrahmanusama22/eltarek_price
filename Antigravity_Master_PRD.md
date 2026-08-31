# Comprehensive System Requirements & Execution Blueprint (PRD)

**Project Name:** El Tareq Cars - Real-Time Price List & CRM Integration System  
**Architecture Pattern:** Monolithic Laravel App with Decoupled Frontend Interfaces (Sales Portal & Admin Dashboard)  
**Primary Tech Stack:** Laravel 11, Filament v3, Livewire 3, Laravel Reverb (WebSockets), MySQL / PostgreSQL, Maatwebsite Excel (`maatwebsite/excel`), Spatie Activitylog (`spatie/laravel-activitylog`).

---

## 1. System Roles & Security Matrix (RBAC & Multi-Tenancy)

### Roles Definition
1. **Super Admin:**
   - Full system access.
   - Manages Users (Creates Brand Managers & Sales accounts).
   - Assigns Brands to Brand Managers.
   - Full CRUD over all Brands, Cars, Price Entries, and Global System Settings.
   - Can trigger manual data syncs or export/import for any brand.

2. **Brand Manager:**
   - Scoped access restricted strictly to their assigned brands via a Pivot table (`brand_user`).
   - Can view, edit, import, export, and manage prices for cars within their assigned brands.
   - Can build dynamic JSON offers for their cars.
   - Accesses the Filament Admin Dashboard (`/admin`).
   - **Restriction:** Strictly prohibited from viewing or altering records belonging to brands outside their authorization list.

3. **Sales Representative:**
   - Accesses a dedicated, read-only external web portal (e.g., `/sales` or `sales.eltareq.com`).
   - **Zero access to the Filament Admin Panel (`/admin`).**
   - Account created exclusively by Super Admin.
   - Views real-time processed pricing, active dynamic offers, and vehicle availability.

---

## 2. Complete Database Schema & Migrations Blueprint

```
                     +-------------------+
                     |       users       |
                     +-------------------+
                               |
                               | (Pivot: brand_user)
                               v
                     +-------------------+
                     |      brands       |
                     +-------------------+
                       /                                     /                                      v                   v
           +-------------------+   +-------------------+
           |       cars        |   |   price_entries   |
           |   (CRM Feeder)    |   |  (Single Source   |
           +-------------------+   |     of Truth)     |
                     |             +-------------------+
                     +-------FK-------^
```

### Table Specifications & Constraints

```sql
-- 1. Users Table
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'brand_manager', 'sales') NOT NULL DEFAULT 'sales',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 2. Brands Table
CREATE TABLE brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 3. Brand User Pivot Table (Multi-Tenancy Authorizations)
CREATE TABLE brand_user (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    brand_id BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
    UNIQUE(user_id, brand_id)
);

-- 4. Cars Table (Feeder Table from CRM System)
CREATE TABLE cars (
    id BIGINT UNSIGNED PRIMARY KEY, -- Preserves original CRM Car ID
    brand_id BIGINT UNSIGNED NOT NULL,
    model_name VARCHAR(255) NOT NULL,
    category VARCHAR(255) NULL,
    year SMALLINT UNSIGNED NOT NULL,
    model_sales_code VARCHAR(500) NOT NULL,
    official_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    crm_hold_status ENUM('YES', 'NO') NOT NULL DEFAULT 'NO',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
);

-- 5. Price Entries Table (Single Source of Truth for Sales View)
CREATE TABLE price_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    car_id BIGINT UNSIGNED NOT NULL,
    brand_id BIGINT UNSIGNED NOT NULL,
    official_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    execution_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    max_selling_price DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- (Official Price * 1.05)
    protection_3m_price DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- Excess over 5%
    offers JSON NULL, -- Dynamic key-value offers
    hold_status ENUM('NO', 'YES', 'Wishing List', 'STOP') NOT NULL DEFAULT 'NO',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
);

-- 6. System Settings Table (Global Visibility Toggles)
CREATE TABLE system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) NOT NULL UNIQUE,
    payload JSON NOT NULL, -- e.g., {"show_3m": true, "show_offers": true}
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## 3. Core Business Logic & Pricing Engine Specifications

### 3.1 The 5% & 3M Protection Waterfall Allocation Rule
The pricing engine must automatically parse and divide the entered `execution_price` upon creation or update (via Model Observer or Mutator).

- **Formula:**
  $$	ext{Max Allowed Selling Price} = 	ext{Official Price} 	imes 1.05$$

- **Execution Flow:**
  ```text
  IF execution_price <= Max Allowed Selling Price:
      max_selling_price = execution_price
      protection_3m_price = 0
  ELSE IF execution_price > Max Allowed Selling Price:
      max_selling_price = Max Allowed Selling Price
      protection_3m_price = execution_price - Max Allowed Selling Price
  ```

- **Implementation Requirement:** This logic must be attached to the Eloquent `saving` event of `PriceEntry`. This guarantees that manual web inputs AND bulk Excel imports automatically execute this exact calculation without code duplication.

### 3.2 Dynamic JSON Offers Format
The `offers` JSON column in `price_entries` must conform to the following JSON structure:
```json
[
  {
    "id": "offer_9_percent",
    "title": "9% Installment Offer",
    "price": 1250000.00,
    "is_active": true,
    "note": "Requires 30% down payment"
  },
  {
    "id": "offer_zero_interest",
    "title": "Zero Interest Offer",
    "price": 1300000.00,
    "is_active": false,
    "note": "Expired"
  }
]
```

### 3.3 Conflict Resolution Logic (CRM vs. Price)
When the background task or CRM webhook updates `cars.official_price`:
1. System checks if `cars.official_price != price_entries.official_price`.
2. Do NOT automatically recalculate `price_entries.execution_price` to avoid disrupting active sales campaigns.
3. Flag the record and push it to the "Conflict Resolution Dashboard" in Filament for the Brand Manager to inspect and approve/ignore.

---

## 4. Filament Admin Panel Specifications (Phase 3)

1. **Brand Manager Tenant Scope:**
   Every Filament Resource (`CarResource`, `PriceEntryResource`) MUST override `getEloquentQuery()`:
   ```php
   public static function getEloquentQuery(): Builder
   {
       $query = parent::getEloquentQuery();
       if (auth()->user()->hasRole('brand_manager')) {
           $query->whereIn('brand_id', auth()->user()->brands->pluck('id'));
       }
       return $query;
   }
   ```

2. **Bulk Import/Export (Excel & CSV):**
   - **Export:** Integrated using `Filament ExportAction`. Exports tables with active tenant filters applied.
   - **Import:** Integrated using `Filament ImportAction`. Must validate imported rows:
     - Check if `car_id` exists.
     - Check if `car_id.brand_id` matches the uploading Brand Manager's authorized brands.
     - Automatically triggers the Pricing Engine Observer for calculated fields.

3. **Global Visibility Settings Page:**
   - Super Admin & Authorized Brand Managers can toggle global UI elements:
     - `show_3m_protection` (Boolean)
     - `show_offers` (Boolean)
     - `show_booking_deposit` (Boolean)
   - Stored in `system_settings` table under key `sales_portal_settings`.

---

## 5. Real-time Sales Portal Specifications (Phase 4)

1. **Authentication & Access Rules:**
   - Dedicated Route Group: `/sales/*`.
   - Middleware: `auth`, `role:sales`.
   - User Model enforces Filament panel restriction:
     ```php
     public function canAccessPanel(Panel $panel): bool
     {
         return $this->role !== 'sales';
     }
     ```

2. **UI Component Architecture (Livewire 3):**
   - Read-only dashboard with responsive layout.
   - List/Grid view displaying:
     - Car Model Code & Details
     - Active Colors
     - Calculated Final Price: $	ext{Total} = 	ext{max\_selling\_price} + 	ext{protection\_3m\_price}$
     - Dynamic Offers (Rendered as tags/cards if `is_active == true`).
   - Respects Global Toggles:
     - `@if($globalSettings['show_3m_protection'])` -> Renders 3M breakdown.

3. **Laravel Reverb (WebSocket) Real-Time Architecture:**
   - **Broadcasting Event:** `PriceEntryUpdated` implements `ShouldBroadcastNow`.
   - **Trigger:** Fired inside `PriceEntryObserver` when updates occur.
   - **Livewire Listener:** The Livewire component on the Sales Portal listens on the private/presence channel and updates the relevant card DOM node via Livewire's wire:key without full-page reloads.

---

## 6. Audit & Logging Specifications (Phase 5)

1. **Spatie Activitylog Integration:**
   - Tracks all mutations on `price_entries` and `system_settings`.
   - Stores: `causer_id` (User who edited), `subject_id` (Price entry ID), `properties` (Old vs. New values).

2. **Activity Log Viewer:**
   - Embedded directly in Filament as a Relation Manager under `PriceEntryResource`.

---

## 7. Step-by-Step Execution Plan for Antigravity AI Engine

### Step 1: Framework Setup & Packages
```bash
composer create-project laravel/laravel eltareq-pricing
composer require filament/filament:"^3.2" -W
php artisan filament:install --panels
composer require pavel-maneshin/filament-settings
composer require spatie/laravel-activitylog
composer require pnpm/laravel-reverb
php artisan reverb:install
```

### Step 2: Database & Migrations
Create and execute migrations strictly adhering to the SQL schema in Section 2.

### Step 3: Models & Relationships
Define Eloquent models (`User`, `Brand`, `Car`, `PriceEntry`, `SystemSetting`) with relationships as specified in Section 2.

### Step 4: Observers & Pricing Engine
Create `PriceEntryObserver` and attach the 5% & 3M calculation logic on the `saving` event. Register in `AppServiceProvider`.

### Step 5: Filament Resources & Multi-Tenancy
Generate Filament resources for Brands, Cars, Price Entries, Users, and Settings. Implement query scoping based on `auth()->user()->brands`. Add Filament Excel Import/Export actions.

### Step 6: Livewire Sales Portal & Reverb Setup
Build the Livewire sales view and Controller. Setup WebSocket event broadcasting using Laravel Reverb and register Livewire listeners.

### Step 7: Verification & Testing
Test Tenant isolation, Excel Imports with unauthorized brand IDs, Reverb broadcasting, and Pricing Observer calculations.
