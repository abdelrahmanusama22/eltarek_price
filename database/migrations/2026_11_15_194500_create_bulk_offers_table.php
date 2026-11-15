<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bulk_offers', function (Blueprint $table) {
            $table->id();
            $table->string('offer_id')->unique();
            $table->string('title');
            $table->string('price_basis')->default('max_selling_price');
            $table->string('offer_type')->default('fixed'); // fixed (amount) or percentage
            $table->decimal('value', 12, 2)->default(0);
            $table->string('scope')->default('all'); // all, brands, models
            $table->json('brands')->nullable();
            $table->json('models')->nullable();
            $table->json('exclude_brands')->nullable();
            $table->json('exclude_models')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('vehicles_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_offers');
    }
};
