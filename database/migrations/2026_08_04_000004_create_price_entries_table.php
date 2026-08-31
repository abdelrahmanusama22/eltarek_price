<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Single Source of Truth for all Sales View pricing data.
     * The `max_selling_price` and `protection_3m_price` columns are
     * automatically computed by the PriceEntryObserver on the `saving` event.
     */
    public function up(): void
    {
        Schema::create('price_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('car_id')
                  ->constrained('cars')
                  ->cascadeOnDelete();

            $table->foreignId('brand_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Raw prices entered by the Brand Manager
            $table->decimal('official_price', 12, 2)->default(0.00);
            $table->decimal('execution_price', 12, 2)->default(0.00);

            // Computed by Pricing Engine (Observer on `saving` event)
            // max_selling_price = MIN(execution_price, official_price * 1.05)
            $table->decimal('max_selling_price', 12, 2)->default(0.00);

            // protection_3m_price = MAX(0, execution_price - official_price * 1.05)
            $table->decimal('protection_3m_price', 12, 2)->default(0.00);

            // Dynamic JSON offers array — see PRD Section 3.2 for exact structure
            $table->json('offers')->nullable();

            $table->enum('hold_status', ['NO', 'YES', 'Wishing List', 'STOP'])->default('NO');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_entries');
    }
};
