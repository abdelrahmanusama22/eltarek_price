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
            $table->decimal('ignored_crm_price', 12, 2)->nullable()->after('official_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_entries', function (Blueprint $table) {
            $table->dropColumn('ignored_crm_price');
        });
    }
};
