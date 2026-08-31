<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cars table acts as the CRM Feeder. The primary key is NOT auto-incremented —
     * it preserves the original CRM Car ID to ensure referential integrity.
     */
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('crm_id')->nullable()->unique();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('model_name');
            $table->string('category')->nullable();
            $table->smallInteger('year')->unsigned();
            $table->string('model_sales_code', 500)->unique();
            $table->decimal('official_price', 12, 2)->default(0.00);
            $table->decimal('execution_price', 12, 2)->default(0.00);
            $table->enum('crm_hold_status', ['NO', 'YES', 'Wishing List', 'STOP'])->default('NO');
            $table->string('sync_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
