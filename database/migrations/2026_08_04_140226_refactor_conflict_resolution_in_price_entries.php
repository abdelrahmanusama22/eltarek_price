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
        Schema::table('price_entries', function (Blueprint $table) {
            $table->dropColumn('ignored_crm_price');
            $table->json('ignored_crm_updates')->nullable()->after('official_price');
            $table->string('model_name')->nullable()->after('car_id');
            $table->string('model_sales_code')->nullable()->after('model_name');
            $table->integer('year')->nullable()->after('model_sales_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_entries', function (Blueprint $table) {
            $table->decimal('ignored_crm_price', 12, 2)->nullable();
            $table->dropColumn(['ignored_crm_updates', 'model_name', 'model_sales_code', 'year']);
        });
    }
};
